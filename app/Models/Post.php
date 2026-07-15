<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Post extends Model
{
    protected $fillable = [
        'title', 'slug', 'excerpt', 'body', 'meta_title', 'meta_description',
        'is_published', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // Generate the slug from the title when left blank in the admin form.
        static::saving(function (self $post) {
            if (blank($post->slug)) {
                $post->slug = Str::slug($post->title);
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** Published posts only, newest first. */
    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('is_published', true)
            ->where(fn (Builder $q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()))
            ->orderByDesc('published_at');
    }

    /** Markdown body rendered to safe HTML (raw HTML in the source is stripped). */
    public function bodyHtml(): string
    {
        return Str::markdown($this->body, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
    }

    /** Excerpt for cards and meta description fallbacks. */
    public function excerptText(): string
    {
        return $this->excerpt ?: Str::limit(trim(strip_tags($this->bodyHtml())), 160);
    }

    /** Approximate reading time in minutes (200 wpm). */
    public function readingMinutes(): int
    {
        return max(1, (int) round(str_word_count(strip_tags($this->body)) / 200));
    }
}
