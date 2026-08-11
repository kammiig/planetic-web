<?php

namespace App\Models;

use App\Support\Copy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

/**
 * Admin-managed FAQ entries. question/answer support the storefront tokens
 * documented in App\Support\Copy (":price", ":currency", ":symbol") so one
 * admin-written answer reads correctly in every regional storefront.
 */
class Faq extends Model
{
    protected $fillable = ['page', 'question', 'answer', 'is_active', 'sort_order'];

    protected function question(): Attribute
    {
        return Attribute::get(fn (?string $value) => Copy::localise($value));
    }

    protected function answer(): Attribute
    {
        return Attribute::get(fn (?string $value) => Copy::localise($value));
    }

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'sort_order' => 'integer'];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForPage(Builder $query, string $page): Builder
    {
        return $query->where('page', $page);
    }
}
