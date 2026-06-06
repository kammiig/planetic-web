<?php

namespace App\Enums;

enum WebsiteProjectStatus: string implements \Filament\Support\Contracts\HasColor, \Filament\Support\Contracts\HasLabel
{
    use \App\Enums\Concerns\FilamentEnum;

    case OrderReceived = 'order_received';
    case InformationRequired = 'information_required';
    case ContentReceived = 'content_received';
    case DesignInProgress = 'design_in_progress';
    case ReviewRequired = 'review_required';
    case RevisionsInProgress = 'revisions_in_progress';
    case Approved = 'approved';
    case Launched = 'launched';
    case Completed = 'completed';
    case OnHold = 'on_hold';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::OrderReceived => 'Order Received',
            self::InformationRequired => 'Information Required',
            self::ContentReceived => 'Content Received',
            self::DesignInProgress => 'Design In Progress',
            self::ReviewRequired => 'Review Required',
            self::RevisionsInProgress => 'Revisions In Progress',
            self::Approved => 'Approved',
            self::Launched => 'Launched',
            self::Completed => 'Completed',
            self::OnHold => 'On Hold',
            self::Cancelled => 'Cancelled',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Completed, self::Launched, self::Approved => 'badge-success',
            self::InformationRequired, self::ReviewRequired => 'badge-warning',
            self::OnHold, self::Cancelled => 'badge-neutral',
            default => 'badge-primary',
        };
    }

    /** The next action the customer needs to take, if any. */
    public function customerNextAction(): ?string
    {
        return match ($this) {
            self::OrderReceived, self::InformationRequired => 'Please complete your website project intake form so our team can begin.',
            self::ReviewRequired => 'Your design is ready to review. Please check your email and dashboard.',
            self::RevisionsInProgress => 'We are working on your requested revisions.',
            default => null,
        };
    }
}
