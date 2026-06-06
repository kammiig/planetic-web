<?php

namespace App\Enums;

enum ProvisioningStatus: string implements \Filament\Support\Contracts\HasColor, \Filament\Support\Contracts\HasLabel
{
    use \App\Enums\Concerns\FilamentEnum;

    case Pending = 'pending';
    case Running = 'running';
    case Completed = 'completed';
    case Failed = 'failed';
    case Retrying = 'retrying';
    case Cancelled = 'cancelled';
    case ManualReview = 'manual_review';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Running => 'Running',
            self::Completed => 'Completed',
            self::Failed => 'Failed',
            self::Retrying => 'Retrying',
            self::Cancelled => 'Cancelled',
            self::ManualReview => 'Manual Review',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Completed => 'badge-success',
            self::Pending, self::Running, self::Retrying => 'badge-primary',
            self::Failed => 'badge-danger',
            self::ManualReview => 'badge-info',
            self::Cancelled => 'badge-neutral',
        };
    }

    public function isRetryable(): bool
    {
        return in_array($this, [self::Failed, self::Retrying, self::ManualReview], true);
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Completed, self::Cancelled], true);
    }
}
