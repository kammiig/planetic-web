<?php

namespace App\Models;

use App\Enums\SupportTicketPriority;
use App\Enums\SupportTicketStatus;
use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportTicket extends Model
{
    use BelongsToUser;

    protected $fillable = [
        'user_id', 'assigned_admin_id', 'ticket_number', 'subject', 'category',
        'priority', 'status', 'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'priority' => SupportTicketPriority::class,
            'status' => SupportTicketStatus::class,
            'closed_at' => 'datetime',
        ];
    }

    public function assignedAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_admin_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SupportTicketMessage::class);
    }

    /** Customer-visible messages only — never internal notes. */
    public function publicMessages(): HasMany
    {
        return $this->messages()->where('is_internal_note', false);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(SupportTicketAttachment::class);
    }
}
