<?php

namespace App\Enums;

enum ProductType: string implements \Filament\Support\Contracts\HasLabel
{
    use \App\Enums\Concerns\FilamentEnum;

    case WebsitePackage = 'website_package';
    case Hosting = 'hosting';
    case Domain = 'domain';
    case Maintenance = 'maintenance';
    case Addon = 'addon';

    public function label(): string
    {
        return match ($this) {
            self::WebsitePackage => 'Website Package',
            self::Hosting => 'Hosting',
            self::Domain => 'Domain',
            self::Maintenance => 'Maintenance',
            self::Addon => 'Add-on',
        };
    }
}
