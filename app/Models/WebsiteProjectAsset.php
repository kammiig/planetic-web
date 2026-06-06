<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebsiteProjectAsset extends Model
{
    use BelongsToUser;

    protected $fillable = [
        'website_project_id', 'user_id', 'file_type', 'original_filename',
        'stored_path', 'mime_type', 'file_size',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
        ];
    }

    /** The internal storage path is never exposed; files are served via an auth route. */
    protected $hidden = ['stored_path'];

    public function websiteProject(): BelongsTo
    {
        return $this->belongsTo(WebsiteProject::class);
    }

    public function humanFileSize(): string
    {
        $bytes = (int) $this->file_size;
        if ($bytes <= 0) {
            return '—';
        }
        $units = ['B', 'KB', 'MB', 'GB'];
        $power = min((int) floor(log($bytes, 1024)), count($units) - 1);

        return round($bytes / (1024 ** $power), 1).' '.$units[$power];
    }
}
