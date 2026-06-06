<?php

namespace App\Enums;

enum HostingStatus: string implements \Filament\Support\Contracts\HasColor, \Filament\Support\Contracts\HasLabel
{
    use \App\Enums\Concerns\FilamentEnum;

    case Pending = 'pending';
    case Creating = 'creating';
    case Active = 'active';
    case Suspended = 'suspended';
    case Terminated = 'terminated';
    case Failed = 'failed';
    case ManualReview = 'manual_review';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Creating => 'Creating',
            self::Active => 'Active',
            self::Suspended => 'Suspended',
            self::Terminated => 'Terminated',
            self::Failed => 'Failed',
            self::ManualReview => 'Manual Review',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Active => 'badge-success',
            self::Pending, self::Creating => 'badge-warning',
            self::Suspended, self::Failed, self::Terminated => 'badge-danger',
            self::ManualReview => 'badge-info',
        };
    }

    public function customerLabel(): string
    {
        return match ($this) {
            self::Pending, self::Creating => 'Creating hosting account',
            self::Failed, self::ManualReview => 'Being reviewed by our team',
            default => $this->label(),
        };
    }
}
