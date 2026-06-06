<?php

namespace App\Enums;

enum DomainStatus: string implements \Filament\Support\Contracts\HasColor, \Filament\Support\Contracts\HasLabel
{
    use \App\Enums\Concerns\FilamentEnum;

    case Pending = 'pending';
    case AvailableChecked = 'available_checked';
    case RegistrationPending = 'registration_pending';
    case Active = 'active';
    case Expired = 'expired';
    case RenewalDue = 'renewal_due';
    case RenewalFailed = 'renewal_failed';
    case Suspended = 'suspended';
    case Cancelled = 'cancelled';
    case Failed = 'failed';
    case ManualReview = 'manual_review';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::AvailableChecked => 'Available',
            self::RegistrationPending => 'Registration Pending',
            self::Active => 'Active',
            self::Expired => 'Expired',
            self::RenewalDue => 'Renewal Due',
            self::RenewalFailed => 'Renewal Failed',
            self::Suspended => 'Suspended',
            self::Cancelled => 'Cancelled',
            self::Failed => 'Failed',
            self::ManualReview => 'Manual Review',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Active => 'badge-success',
            self::Pending, self::RegistrationPending, self::RenewalDue => 'badge-warning',
            self::Failed, self::RenewalFailed, self::Suspended, self::Expired => 'badge-danger',
            self::ManualReview, self::AvailableChecked => 'badge-info',
            self::Cancelled => 'badge-neutral',
        };
    }

    /** Customer-safe status text — never expose raw failure internals. */
    public function customerLabel(): string
    {
        return match ($this) {
            self::RegistrationPending, self::Pending => 'Setting up your domain',
            self::Failed, self::ManualReview => 'Being reviewed by our team',
            self::RenewalFailed => 'Renewal needs attention',
            default => $this->label(),
        };
    }
}
