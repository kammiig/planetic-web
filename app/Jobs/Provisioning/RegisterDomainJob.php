<?php

namespace App\Jobs\Provisioning;

use App\Enums\DomainStatus;
use App\Enums\ProvisioningJobType;
use App\Exceptions\RegistrarException;
use App\Exceptions\ProvisioningException;
use App\Models\Domain;
use App\Models\Order;
use App\Models\ProvisioningJob;
use App\Services\Registrar\RegistrarInterface;
use App\Support\DomainName;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Registers the order's domain (Ticket 27). Idempotent: if the domain is
 * already registered it is skipped. A registrar failure marks the step for
 * manual review — the payment is kept, the order is not silently completed.
 */
class RegisterDomainJob extends ProvisioningStepJob
{
    protected function type(): ProvisioningJobType
    {
        return ProvisioningJobType::RegisterDomain;
    }

    protected function perform(Order $order, ProvisioningJob $step): array
    {
        $domainName = $order->primaryDomainName();

        if (blank($domainName)) {
            throw new ProvisioningException('No domain on the order to register.');
        }

        $parsed = DomainName::parse($domainName);

        /** @var Domain $domain */
        $domain = Domain::firstOrNew(['domain_name' => $domainName]);

        // Idempotency — already registered.
        if ($domain->exists && filled($domain->registrar_domain_id)) {
            return ['skipped' => true, 'reason' => 'already_registered'];
        }

        $domain->fill([
            'user_id' => $order->user_id,
            'order_id' => $order->id,
            'sld' => $parsed->sld,
            'tld' => $parsed->tld,
            'registrar' => config('domain.default_registrar'),
            'status' => DomainStatus::RegistrationPending->value,
            'auto_renew' => config('domain.defaults.auto_renew', true),
            'whois_privacy' => config('domain.defaults.whois_privacy', true),
            'registrar_lock' => config('domain.defaults.registrar_lock', true),
        ])->save();

        $this->ensureRegistrantContact($domain, $order);

        $registrar = app(RegistrarInterface::class);

        try {
            $result = $registrar->registerDomain([
                'domain' => $domainName,
                'years' => config('domain.defaults.years', 1),
                'whois_privacy' => $domain->whois_privacy,
                'auto_renew' => $domain->auto_renew,
                'contact' => $this->contactData($order),
            ]);
        } catch (RegistrarException $e) {
            // Payment succeeded but registration failed → manual review.
            $domain->update(['status' => DomainStatus::Failed->value]);
            throw new ProvisioningException($e->getMessage(), manualReview: true, previous: $e);
        }

        $expiry = $result['expiry_date'] ? Carbon::parse($result['expiry_date']) : now()->addYear();

        $domain->update([
            'status' => DomainStatus::Active->value,
            'registrar' => $registrar->name(),
            'registrar_domain_id' => $result['registrar_domain_id'] ?? $result['domain'] ?? $domainName,
            'registrar_order_id' => $result['registrar_order_id'] ?? null,
            'registration_date' => now()->toDateString(),
            'expiry_date' => $expiry->toDateString(),
            'last_synced_at' => now(),
        ]);

        return $result;
    }

    private function ensureRegistrantContact(Domain $domain, Order $order): void
    {
        if ($domain->contacts()->where('contact_type', 'registrant')->exists()) {
            return;
        }

        $contact = $this->contactData($order);
        $domain->contacts()->create(array_merge($contact, ['contact_type' => 'registrant']));
    }

    /** @return array<string, mixed> */
    private function contactData(Order $order): array
    {
        $user = $order->user;
        $first = Str::before($user->name, ' ');
        $last = Str::contains($user->name, ' ') ? Str::after($user->name, ' ') : $first;

        return [
            'first_name' => $first ?: 'Customer',
            'last_name' => $last ?: 'Account',
            'company_name' => $user->company_name,
            'email' => $user->email,
            'phone' => $user->phone ?: '+44.0000000000',
            'address_line_1' => $user->billing_address_line_1 ?: 'N/A',
            'address_line_2' => $user->billing_address_line_2,
            'city' => $user->billing_city ?: 'N/A',
            'state' => $user->billing_state,
            'postcode' => $user->billing_postcode ?: '00000',
            'country' => $user->billing_country ?: 'GB',
        ];
    }
}
