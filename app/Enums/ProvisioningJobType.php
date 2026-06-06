<?php

namespace App\Enums;

enum ProvisioningJobType: string implements \Filament\Support\Contracts\HasLabel
{
    use \App\Enums\Concerns\FilamentEnum;

    case RegisterDomain = 'register_domain';
    case CreateCloudflareZone = 'create_cloudflare_zone';
    case UpdateNameservers = 'update_nameservers';
    case CreateHostingAccount = 'create_hosting_account';
    case CreateDnsRecords = 'create_dns_records';
    case SendWelcomeEmail = 'send_welcome_email';

    public function label(): string
    {
        return match ($this) {
            self::RegisterDomain => 'Register Domain',
            self::CreateCloudflareZone => 'Create Cloudflare Zone',
            self::UpdateNameservers => 'Update Nameservers',
            self::CreateHostingAccount => 'Create Hosting Account',
            self::CreateDnsRecords => 'Create DNS Records',
            self::SendWelcomeEmail => 'Send Welcome Email',
        };
    }

    /** Simplified, customer-safe progress text (never expose internals). */
    public function customerLabel(): string
    {
        return match ($this) {
            self::RegisterDomain => 'Setting up your domain',
            self::CreateCloudflareZone, self::UpdateNameservers, self::CreateDnsRecords => 'DNS setup in progress',
            self::CreateHostingAccount => 'Creating hosting account',
            self::SendWelcomeEmail => 'Finishing up',
        };
    }
}
