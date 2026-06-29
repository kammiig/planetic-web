<?php

namespace App\Enums;

/**
 * Where a testimonial/review originated. Drives the source logo and the
 * "Verified Trustpilot review" / "Google review" labels shown on the public
 * site. Branding is ONLY shown for the source the admin actually selected —
 * we never imply a manual review came from Trustpilot or Google.
 */
enum ReviewSource: string implements \Filament\Support\Contracts\HasLabel
{
    use \App\Enums\Concerns\FilamentEnum;

    case Manual = 'manual';
    case Trustpilot = 'trustpilot';
    case Google = 'google';

    public function label(): string
    {
        return match ($this) {
            self::Manual => 'Website / manual review',
            self::Trustpilot => 'Trustpilot',
            self::Google => 'Google',
        };
    }

    /** True when this source has third-party branding (logo) we can display. */
    public function isBranded(): bool
    {
        return $this !== self::Manual;
    }

    /**
     * The badge label shown next to a review. Verified sources get a stronger
     * "Verified …" wording; everything else stays neutral and honest.
     */
    public function badgeLabel(bool $verified = false): string
    {
        return match ($this) {
            self::Trustpilot => $verified ? 'Verified Trustpilot review' : 'Trustpilot review',
            self::Google => $verified ? 'Verified Google review' : 'Google review',
            self::Manual => 'Verified customer',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Trustpilot => 'badge-success',
            self::Google => 'badge-info',
            self::Manual => 'badge-neutral',
        };
    }
}
