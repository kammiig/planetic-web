<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebsiteProjectMeeting extends Model
{
    protected $fillable = [
        'website_project_id', 'requested_by', 'status', 'topic',
        'proposed_at', 'scheduled_at', 'duration_minutes', 'meeting_url', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'proposed_at' => 'datetime',
            'scheduled_at' => 'datetime',
            'duration_minutes' => 'integer',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(WebsiteProject::class, 'website_project_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /** The agreed time if confirmed, otherwise the proposed time. */
    public function effectiveTime(): \Illuminate\Support\Carbon
    {
        return $this->scheduled_at ?? $this->proposed_at;
    }

    public function isConfirmed(): bool
    {
        return $this->status === 'confirmed' && $this->scheduled_at !== null;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'requested' => 'Awaiting confirmation',
            'confirmed' => 'Confirmed',
            'rescheduled' => 'Rescheduled — please review',
            'cancelled' => 'Cancelled',
            'completed' => 'Completed',
            default => ucfirst((string) $this->status),
        };
    }
}
