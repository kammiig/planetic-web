<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\Registrar\PorkbunRegistrar;
use App\Services\Registrar\RegistrarInterface;
use App\Support\DomainName;
use App\Support\RegistrantValidator;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Diagnoses why a domain registration failed, without charging anything:
 *
 *   php artisan porkbun:debug-register ORD-10033
 *
 * Prints the domain, TLD, registrar, the customer's registrant contact and any
 * missing/invalid fields, then runs a no-charge Porkbun dry-run and dumps the
 * sanitized request payload, endpoint, HTTP status, raw response body, the exact
 * reason, and the TLD's registration requirements. No API secrets are shown.
 */
class PorkbunDebugRegisterCommand extends Command
{
    protected $signature = 'porkbun:debug-register {order : Order number (e.g. ORD-10033) or numeric id}';

    protected $description = 'Show the exact Porkbun request/response and reason for a domain registration (no charge).';

    public function handle(): int
    {
        $arg = (string) $this->argument('order');

        $order = Order::query()
            ->where('order_number', $arg)
            ->orWhere('id', is_numeric($arg) ? (int) $arg : 0)
            ->with('user', 'items')
            ->first();

        if (! $order) {
            $this->error("Order {$arg} not found.");

            return self::FAILURE;
        }

        $domain = $order->primaryDomainName();

        if (blank($domain)) {
            $this->error("Order {$order->order_number} has no domain to register.");

            return self::FAILURE;
        }

        $parsed = DomainName::parse($domain);
        $registrar = app(RegistrarInterface::class);

        $this->newLine();
        $this->info("Domain registration debug — {$order->order_number}");
        $this->table(['Field', 'Value'], [
            ['Domain', $domain],
            ['TLD', $parsed->tld],
            ['Registrar', $registrar->name()],
            ['Customer', $order->user?->email],
        ]);

        // ---- Registrant contact validation ----
        $contact = $this->contactData($order);
        $missing = RegistrantValidator::missing($contact);
        $format = RegistrantValidator::formatIssues($contact, $parsed->tld);

        $this->newLine();
        $this->info('Registrant contact (from the customer billing profile):');
        $this->table(['Field', 'Value'], collect($contact)->map(fn ($v, $k) => [$k, $v === '' ? '(empty)' : $v])->values()->all());

        if ($missing) {
            $this->warn('  ⚠ Missing/placeholder required fields: '.implode('; ', $missing));
        }
        if ($format) {
            $this->warn('  ⚠ Format issues: '.implode('; ', $format));
        }
        if (! $missing && ! $format) {
            $this->info('  ✓ Contact data looks complete and well-formed.');
        }

        // ---- Live (no-charge) Porkbun dry-run ----
        if (! $registrar instanceof PorkbunRegistrar) {
            $this->newLine();
            $this->warn('The active registrar is not Porkbun — the live payload dump is Porkbun-specific.');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->info('Running a no-charge dry-run registration against Porkbun…');
        $report = $registrar->debugRegister($domain, (bool) config('domain.defaults.whois_privacy', true));

        $this->table(['Field', 'Value'], [
            ['Endpoint', $report['endpoint'] ?? '—'],
            ['Availability', json_encode($report['availability'] ?? null, JSON_UNESCAPED_SLASHES)],
            ['Cost sent (pennies)', (string) ($report['cost_pennies_sent'] ?? '—')],
            ['HTTP status', (string) ($report['http_status'] ?? '—')],
        ]);

        $this->newLine();
        $this->info('Request payload sent to Porkbun (credentials omitted):');
        $this->line('  '.json_encode($report['request_payload'] ?? null, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $this->newLine();
        $this->info('► Porkbun reason:');
        $this->line('  '.($report['reason'] ?? '—'));

        $this->newLine();
        $this->info('► Porkbun response body:');
        $this->line('  '.json_encode($report['response_body'] ?? null, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $this->newLine();
        $this->info('► TLD registration requirements:');
        $this->line('  '.json_encode($report['registration_requirements'] ?? null, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $this->newLine();
        $this->comment('Note: Porkbun registers using your account\'s default WHOIS contact. If the reason above mentions registrant/contact or a .uk eligibility field, fix the default contact in your Porkbun account, then run: php artisan orders:provision '.$order->order_number);

        return self::SUCCESS;
    }

    /** Mirrors RegisterDomainJob's contact mapping (blank where the profile is empty). */
    private function contactData(Order $order): array
    {
        $user = $order->user;
        $name = (string) ($user?->name ?? '');
        $first = Str::before($name, ' ');
        $last = Str::contains($name, ' ') ? Str::after($name, ' ') : $first;

        return [
            'first_name' => $first,
            'last_name' => $last,
            'email' => (string) ($user?->email ?? ''),
            'phone' => (string) ($user?->phone ?? ''),
            'address_line_1' => (string) ($user?->billing_address_line_1 ?? ''),
            'city' => (string) ($user?->billing_city ?? ''),
            'state' => (string) ($user?->billing_state ?? ''),
            'postcode' => (string) ($user?->billing_postcode ?? ''),
            'country' => (string) ($user?->billing_country ?? ''),
        ];
    }
}
