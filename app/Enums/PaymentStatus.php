<?php

namespace App\Enums;

enum PaymentStatus: string implements \Filament\Support\Contracts\HasColor, \Filament\Support\Contracts\HasLabel
{
    use \App\Enums\Concerns\FilamentEnum;

    case Pending = 'pending';
    case Succeeded = 'succeeded';
    case Failed = 'failed';
    case Refunded = 'refunded';
    case PartiallyRefunded = 'partially_refunded';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Succeeded => 'Succeeded',
            self::Failed => 'Failed',
            self::Refunded => 'Refunded',
            self::PartiallyRefunded => 'Partially Refunded',
            self::Cancelled => 'Cancelled',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Succeeded => 'badge-success',
            self::Pending => 'badge-warning',
            self::Failed, self::Cancelled => 'badge-danger',
            self::Refunded, self::PartiallyRefunded => 'badge-info',
        };
    }
}
