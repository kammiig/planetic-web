<?php

namespace App\Enums;

/**
 * Line-item type used on both cart_items and order_items.
 */
enum ItemType: string implements \Filament\Support\Contracts\HasLabel
{
    use \App\Enums\Concerns\FilamentEnum;

    case WebsitePackage = 'website_package';
    case Hosting = 'hosting';
    case DomainRegistration = 'domain_registration';
    case DomainRenewal = 'domain_renewal';
    case Addon = 'addon';
    case Maintenance = 'maintenance';

    public function label(): string
    {
        return match ($this) {
            self::WebsitePackage => 'Website Package',
            self::Hosting => 'Hosting',
            self::DomainRegistration => 'Domain Registration',
            self::DomainRenewal => 'Domain Renewal',
            self::Addon => 'Add-on',
            self::Maintenance => 'Maintenance',
        };
    }

    public function requiresDomain(): bool
    {
        return in_array($this, [
            self::WebsitePackage,
            self::DomainRegistration,
            self::DomainRenewal,
        ], true);
    }
}
