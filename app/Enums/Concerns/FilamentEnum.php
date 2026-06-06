<?php

namespace App\Enums\Concerns;

/**
 * Bridges our domain enums to Filament's HasLabel/HasColor contracts so the
 * admin panel shows friendly labels and coloured badges, reusing the same
 * label()/badgeClass() the customer-facing UI uses.
 */
trait FilamentEnum
{
    public function getLabel(): ?string
    {
        return method_exists($this, 'label') ? $this->label() : $this->name;
    }

    public function getColor(): string|array|null
    {
        if (! method_exists($this, 'badgeClass')) {
            return 'gray';
        }

        return match ($this->badgeClass()) {
            'badge-success' => 'success',
            'badge-warning' => 'warning',
            'badge-danger' => 'danger',
            'badge-info' => 'info',
            'badge-primary' => 'primary',
            default => 'gray',
        };
    }
}
