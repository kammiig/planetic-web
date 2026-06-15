<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebsiteProjectMessageAttachment extends Model
{
    protected $fillable = [
        'website_project_message_id', 'website_project_id', 'user_id',
        'original_name', 'path', 'mime_type', 'size_bytes',
    ];

    protected $hidden = ['path'];

    public function message(): BelongsTo
    {
        return $this->belongsTo(WebsiteProjectMessage::class, 'website_project_message_id');
    }

    public function humanSize(): string
    {
        $bytes = (int) $this->size_bytes;

        return match (true) {
            $bytes >= 1048576 => number_format($bytes / 1048576, 1).' MB',
            $bytes >= 1024 => number_format($bytes / 1024, 0).' KB',
            default => $bytes.' B',
        };
    }
}
