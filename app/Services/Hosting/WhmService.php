<?php

namespace App\Services\Hosting;

use App\Exceptions\WhmException;
use App\Support\Secrets;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Namecheap reseller WHM/cPanel API wrapper (Ticket 30). All calls run from
 * the backend only; the API token lives solely in config (env) and is never
 * exposed to the frontend.
 */
class WhmService
{
    /**
     * Create a cPanel account.
     *
     * @param  array{username: string, domain: string, contactemail: string, plan: string, password: string}  $data
     * @return array{success: bool, ip: ?string, nameserver: ?string, package: ?string}
     */
    public function createAccount(array $data): array
    {
        $params = array_merge([
            'username' => $data['username'],
            'domain' => $data['domain'],
            'contactemail' => $data['contactemail'],
            'plan' => $data['plan'],
            'password' => $data['password'],
        ], config('hosting.account_defaults', []));

        try {
            // Sent as a POST body so the password never appears in the request
            // URL (and therefore never in a transport error message or log).
            $response = $this->request('createacct', $params, 'create account', allowFailure: true);
        } catch (WhmException $e) {
            // A timeout (cURL 28) does NOT mean failure — WHM may have created
            // the account anyway. Reconcile against the live account list
            // before deciding, so we never duplicate or wrongly fail.
            if ($existing = $this->findAccountByDomain($data['domain'])) {
                Log::channel('stack')->warning('WHM createacct transport error, but the account exists — adopting it.', [
                    'domain' => $data['domain'],
                    'username' => $existing['user'] ?? $data['username'],
                ]);

                return $this->accountResult($existing, $data['plan']);
            }

            throw $e;
        }

        $meta = $response['metadata'] ?? [];

        if ((int) ($meta['result'] ?? 0) !== 1) {
            $reason = (string) ($meta['reason'] ?? 'unknown error');

            // The account may already exist from a previous (timed-out) attempt.
            if (str_contains(strtolower($reason), 'already exists')) {
                if ($existing = $this->findAccountByDomain($data['domain'])) {
                    return $this->accountResult($existing, $data['plan']);
                }

                // Exists but we cannot read it back — needs a human, never retry.
                throw new WhmException('WHM createacct: '.Secrets::redact($reason), manualReview: true, context: $this->safeContext($meta));
            }

            throw new WhmException('WHM createacct failed: '.Secrets::redact($reason), context: $this->safeContext($meta));
        }

        return $this->accountResult($response['data'] ?? [], $data['plan']);
    }

    /**
     * Look up a cPanel account by its primary domain. Used to reconcile after a
     * timeout and to make creation idempotent on retry.
     *
     * @return array<string, mixed>|null
     */
    public function findAccountByDomain(string $domain): ?array
    {
        $domain = strtolower($domain);

        try {
            // WHM supports server-side search; fall back to scanning the list.
            $response = $this->request('listaccts', ['search' => $domain, 'searchtype' => 'domain'], 'list accounts');
        } catch (Throwable $e) {
            Log::channel('stack')->warning('WHM account lookup failed.', ['error' => Secrets::redact($e->getMessage())]);

            return null;
        }

        $accounts = $response['data']['acct'] ?? $response['acct'] ?? [];

        foreach ($accounts as $account) {
            if (strtolower((string) ($account['domain'] ?? '')) === $domain) {
                return $account;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $account
     * @return array{success: bool, ip: ?string, nameserver: ?string, package: ?string}
     */
    private function accountResult(array $account, string $fallbackPlan): array
    {
        return [
            'success' => true,
            'ip' => $account['ip'] ?? null,
            'nameserver' => $account['nameserver'] ?? ($account['nameservers'][0] ?? null),
            'package' => $account['package'] ?? $account['plan'] ?? $fallbackPlan,
        ];
    }

    public function suspendAccount(string $username, string $reason = 'Payment overdue'): array
    {
        return $this->assertOk($this->request('suspendacct', ['user' => $username, 'reason' => $reason], 'suspend account'));
    }

    public function unsuspendAccount(string $username): array
    {
        return $this->assertOk($this->request('unsuspendacct', ['user' => $username], 'unsuspend account'));
    }

    public function terminateAccount(string $username): array
    {
        return $this->assertOk($this->request('removeacct', ['user' => $username], 'terminate account'));
    }

    public function changePackage(string $username, string $package): array
    {
        return $this->assertOk($this->request('changepackage', ['user' => $username, 'pkg' => $package], 'change package'));
    }

    /**
     * Create a one-time cPanel login session for single sign-on. Returns a
     * short-lived URL the browser is redirected to — it carries a one-time
     * token, never the account password. (WHM API: create_user_session.)
     *
     * @throws WhmException
     */
    public function createUserSession(string $username, string $service = 'cpaneld'): string
    {
        $response = $this->request('create_user_session', [
            'user' => $username,
            'service' => $service,
        ], 'create cPanel session');

        $url = $response['data']['url'] ?? $response['url'] ?? null;

        if (blank($url)) {
            throw new WhmException('WHM create_user_session returned no URL.', context: $this->safeContext($response['metadata'] ?? []));
        }

        return (string) $url;
    }

    /** @return array<int, array<string, mixed>> */
    public function listAccounts(): array
    {
        $response = $this->request('listaccts', [], 'list accounts');

        return $response['data']['acct'] ?? $response['acct'] ?? [];
    }

    /** @return array<string, mixed> */
    public function getAccountInfo(string $username): array
    {
        $response = $this->request('accountsummary', ['user' => $username], 'account info');

        return $response['data']['acct'][0] ?? [];
    }

    /**
     * Issue a WHM API request and return the decoded JSON.
     *
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    private function request(string $function, array $params, string $label, bool $allowFailure = false): array
    {
        $config = config('whm');

        foreach (['host', 'username', 'token'] as $required) {
            if (blank($config[$required] ?? null)) {
                throw new WhmException("WHM config '{$required}' is not set.");
            }
        }

        $url = sprintf('https://%s:%s/json-api/%s', $config['host'], $config['port'], $function);

        try {
            // POST form body: secrets (the cPanel password) travel in the body,
            // never the URL, so a transport error message can't leak them.
            $response = $this->httpClient($config)
                ->asForm()
                ->post($url, array_merge(['api.version' => 1], $params));
        } catch (Throwable $e) {
            // Defence in depth: redact in case any client still surfaces a URL.
            throw new WhmException('WHM '.$label.' request error: '.Secrets::redact($e->getMessage()), previous: $e);
        }

        if ($response->failed()) {
            throw new WhmException("WHM {$label} HTTP {$response->status()}.", context: $response->status());
        }

        $json = $response->json();
        if (! is_array($json)) {
            throw new WhmException("WHM {$label}: invalid response.");
        }

        return $json;
    }

    /**
     * Strip any sensitive keys from a context array before it is stored.
     *
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function safeContext(array $context): array
    {
        return Secrets::redactArray($context);
    }

    private function httpClient(array $config): PendingRequest
    {
        $client = Http::withHeaders([
            'Authorization' => 'whm '.$config['username'].':'.$config['token'],
        ])->timeout((int) ($config['request_timeout'] ?? 120));

        if (! ($config['verify_ssl'] ?? true)) {
            $client = $client->withoutVerifying();
        }

        return $client;
    }

    /**
     * @param  array<string, mixed>  $response
     * @return array<string, mixed>
     */
    private function assertOk(array $response): array
    {
        $meta = $response['metadata'] ?? [];

        if ((int) ($meta['result'] ?? 0) !== 1) {
            throw new WhmException('WHM operation failed: '.($meta['reason'] ?? 'unknown error'), context: $meta);
        }

        return $response;
    }
}
