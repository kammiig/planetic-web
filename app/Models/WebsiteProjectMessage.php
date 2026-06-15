<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WebsiteProjectMessage extends Model
{
    protected $fillable = [
        'website_project_id', 'user_id', 'is_from_staff', 'is_internal_note', 'body',
    ];

    protected function casts(): array
    {
        return [
            'is_from_staff' => 'boolean',
            'is_internal_note' => 'boolean',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(WebsiteProject::class, 'website_project_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(WebsiteProjectMessageAttachment::class);
    }
}
