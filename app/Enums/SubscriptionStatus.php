<?php

namespace App\Enums;

enum SubscriptionStatus: string implements \Filament\Support\Contracts\HasColor, \Filament\Support\Contracts\HasLabel
{
    use \App\Enums\Concerns\FilamentEnum;

    case Active = 'active';
    case PastDue = 'past_due';
    case Cancelled = 'cancelled';
    case Unpaid = 'unpaid';
    case Paused = 'paused';
    case Trialing = 'trialing';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::PastDue => 'Past Due',
            self::Cancelled => 'Cancelled',
            self::Unpaid => 'Unpaid',
            self::Paused => 'Paused',
            self::Trialing => 'Trialing',
            self::Expired => 'Expired',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Active, self::Trialing => 'badge-success',
            self::PastDue, self::Paused => 'badge-warning',
            self::Unpaid, self::Expired => 'badge-danger',
            self::Cancelled => 'badge-neutral',
        };
    }

    public function isActive(): bool
    {
        return in_array($this, [self::Active, self::Trialing], true);
    }
}
