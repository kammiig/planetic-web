<?php

namespace App\Enums;

enum SupportTicketStatus: string
{
    case Open = 'open';
    case Pending = 'pending';
    case WaitingCustomer = 'waiting_customer';
    case Resolved = 'resolved';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Open',
            self::Pending => 'Pending',
            self::WaitingCustomer => 'Waiting on Customer',
            self::Resolved => 'Resolved',
            self::Closed => 'Closed',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Open => 'badge-primary',
            self::Pending, self::WaitingCustomer => 'badge-warning',
            self::Resolved => 'badge-success',
            self::Closed => 'badge-neutral',
        };
    }

    public function isClosed(): bool
    {
        return in_array($this, [self::Resolved, self::Closed], true);
    }
}
