<?php

namespace App\Enums;

enum InvoiceStatus: string implements \Filament\Support\Contracts\HasColor, \Filament\Support\Contracts\HasLabel
{
    use \App\Enums\Concerns\FilamentEnum;

    case Draft = 'draft';
    case Open = 'open';
    case Paid = 'paid';
    case Void = 'void';
    case Uncollectible = 'uncollectible';
    case Failed = 'failed';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Open => 'Open',
            self::Paid => 'Paid',
            self::Void => 'Void',
            self::Uncollectible => 'Uncollectible',
            self::Failed => 'Failed',
            self::Refunded => 'Refunded',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Paid => 'badge-success',
            self::Open, self::Draft => 'badge-warning',
            self::Failed, self::Uncollectible => 'badge-danger',
            self::Void, self::Refunded => 'badge-info',
        };
    }
}
