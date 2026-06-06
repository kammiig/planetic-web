<?php

namespace App\Services\Hosting;

use App\Exceptions\WhmException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
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

        $response = $this->request('createacct', $params, 'create account', allowFailure: true);
        $meta = $response['metadata'] ?? [];

        if ((int) ($meta['result'] ?? 0) !== 1) {
            $reason = (string) ($meta['reason'] ?? 'unknown error');
            // "Account already exists" / domain collisions carry duplicate
            // risk and must go to manual review, never blind retry.
            $duplicate = str_contains(strtolower($reason), 'already exists');
            throw new WhmException("WHM createacct failed: {$reason}", manualReview: $duplicate, context: $meta);
        }

        $accountData = $response['data'] ?? [];

        return [
            'success' => true,
            'ip' => $accountData['ip'] ?? null,
            'nameserver' => $accountData['nameserver'] ?? null,
            'package' => $accountData['package'] ?? $data['plan'],
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
            $response = $this->httpClient($config)
                ->get($url, array_merge(['api.version' => 1], $params));
        } catch (Throwable $e) {
            throw new WhmException("WHM {$label} request error: {$e->getMessage()}", previous: $e);
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

    private function httpClient(array $config): PendingRequest
    {
        $client = Http::withHeaders([
            'Authorization' => 'whm '.$config['username'].':'.$config['token'],
        ])->timeout((int) ($config['request_timeout'] ?? 30));

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
