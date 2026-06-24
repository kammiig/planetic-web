<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Key/value store powering all admin-editable marketing copy, contact details,
 * social links, CTA labels and feature toggles. Values are cached as a single
 * keyed map so reads are free on the hot path; the cache is flushed whenever a
 * setting is saved or deleted.
 */
class SiteSetting extends Model
{
    public const CACHE_KEY = 'site_settings.map';

    protected $fillable = ['group', 'key', 'value', 'type', 'label', 'help', 'sort_order'];

    protected function casts(): array
    {
        return ['sort_order' => 'integer'];
    }

    protected static function booted(): void
    {
        $flush = fn () => Cache::forget(self::CACHE_KEY);
        static::saved($flush);
        static::deleted($flush);
    }

    /** Cached map of key => type-cast value. */
    public static function map(): Collection
    {
        // Cache a plain array (not a Collection/model) — the database cache
        // store cannot round-trip objects without corrupting them.
        $data = Cache::rememberForever(self::CACHE_KEY, fn () => static::all()
            ->mapWithKeys(fn (self $s) => [$s->key => $s->castValue()])
            ->all());

        return collect($data);
    }

    /** Read a single setting (type-cast), with a fallback default. */
    public static function get(string $key, mixed $default = null): mixed
    {
        $value = static::map()->get($key, $default);

        // Treat blank strings as "use the default" so an empty admin field
        // gracefully falls back to the built-in copy rather than rendering nothing.
        return ($value === null || $value === '') ? $default : $value;
    }

    /** Create or update a setting by key and flush the cache. */
    public static function set(string $key, mixed $value, string $group = 'general', string $type = 'text'): self
    {
        $row = static::firstOrNew(['key' => $key]);
        $row->fill([
            'group' => $row->group ?? $group,
            'type' => $row->type ?? $type,
            'value' => is_array($value) ? json_encode($value) : $value,
        ]);
        $row->save();

        return $row;
    }

    public function castValue(): mixed
    {
        return match ($this->type) {
            'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOL),
            'json' => json_decode($this->value ?? '[]', true) ?: [],
            default => $this->value,
        };
    }
}
