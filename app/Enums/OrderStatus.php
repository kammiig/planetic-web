<?php

namespace App\Enums;

enum OrderStatus: string implements \Filament\Support\Contracts\HasColor, \Filament\Support\Contracts\HasLabel
{
    use \App\Enums\Concerns\FilamentEnum;

    case Pending = 'pending';
    case Paid = 'paid';
    case Provisioning = 'provisioning';
    case Completed = 'completed';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';
    case ManualReview = 'manual_review';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Paid => 'Paid',
            self::Provisioning => 'Provisioning',
            self::Completed => 'Completed',
            self::Failed => 'Failed',
            self::Cancelled => 'Cancelled',
            self::Refunded => 'Refunded',
            self::ManualReview => 'Manual Review',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Paid, self::Completed => 'badge-success',
            self::Pending => 'badge-warning',
            self::Provisioning => 'badge-primary',
            self::Failed, self::Cancelled => 'badge-danger',
            self::ManualReview, self::Refunded => 'badge-info',
        };
    }

    /** A customer-safe label for the order's overall state. */
    public function customerLabel(): string
    {
        return match ($this) {
            self::Provisioning => 'Setting up your services',
            self::ManualReview => 'Being reviewed by our team',
            default => $this->label(),
        };
    }
}
