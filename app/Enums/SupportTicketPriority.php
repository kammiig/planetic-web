<?php

namespace App\Enums;

enum SupportTicketPriority: string implements \Filament\Support\Contracts\HasColor, \Filament\Support\Contracts\HasLabel
{
    use \App\Enums\Concerns\FilamentEnum;

    case Low = 'low';
    case Normal = 'normal';
    case High = 'high';
    case Urgent = 'urgent';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Low => 'badge-neutral',
            self::Normal => 'badge-info',
            self::High => 'badge-warning',
            self::Urgent => 'badge-danger',
        };
    }
}
