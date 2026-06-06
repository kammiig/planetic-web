<?php

namespace App\Enums;

/**
 * Platform roles (Security & Access §4). Guest is the absence of any role.
 */
enum RoleName: string
{
    case SuperAdmin = 'super_admin';
    case TechnicalAdmin = 'technical_admin';
    case BillingManager = 'billing_manager';
    case SupportStaff = 'support_staff';
    case WebsiteDeveloper = 'website_developer';
    case Customer = 'customer';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::TechnicalAdmin => 'Technical Admin',
            self::BillingManager => 'Billing Manager',
            self::SupportStaff => 'Support Staff',
            self::WebsiteDeveloper => 'Website Developer',
            self::Customer => 'Customer',
        };
    }

    /** Roles that may access the staff/admin panel. */
    public static function staffRoles(): array
    {
        return [
            self::SuperAdmin,
            self::TechnicalAdmin,
            self::BillingManager,
            self::SupportStaff,
            self::WebsiteDeveloper,
        ];
    }

    public function isStaff(): bool
    {
        return $this !== self::Customer;
    }
}
