<?php

namespace App\Models;

use App\Enums\ReviewSource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Testimonial extends Model
{
    protected $fillable = [
        'author_name', 'author_role', 'company', 'body', 'rating', 'avatar_url',
        'source', 'source_url', 'is_verified', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'source' => ReviewSource::class,
            'is_verified' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** Always resolves to a ReviewSource, defaulting to manual for legacy rows. */
    public function source(): ReviewSource
    {
        return $this->source instanceof ReviewSource ? $this->source : ReviewSource::Manual;
    }

    /** The trust label, e.g. "Verified Trustpilot review" — honest per source. */
    public function sourceBadgeLabel(): string
    {
        return $this->source()->badgeLabel((bool) $this->is_verified);
    }

    /** Initials for the avatar fallback, e.g. "Jane Doe" -> "JD". */
    public function initials(): string
    {
        return collect(explode(' ', trim($this->author_name)))
            ->filter()
            ->take(2)
            ->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))
            ->implode('');
    }
}
