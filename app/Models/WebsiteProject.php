<?php

namespace App\Models;

use App\Enums\WebsiteProjectStatus;
use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WebsiteProject extends Model
{
    use BelongsToUser;

    protected $fillable = [
        'user_id', 'order_id', 'domain_id', 'hosting_account_id',
        'assigned_developer_id', 'project_number', 'status', 'business_name',
        'business_description', 'industry', 'pages_required', 'brand_colours',
        'reference_websites', 'special_requirements', 'internal_notes',
        'content_received', 'logo_received', 'target_launch_date', 'launched_at',
        'delivered_at', 'revision_days', 'revisions_used', 'revisions_reopened_until',
    ];

    protected function casts(): array
    {
        return [
            'status' => WebsiteProjectStatus::class,
            'pages_required' => 'array',
            'reference_websites' => 'array',
            'content_received' => 'boolean',
            'logo_received' => 'boolean',
            'target_launch_date' => 'date',
            'launched_at' => 'datetime',
            'delivered_at' => 'datetime',
            'revision_days' => 'integer',
            'revisions_used' => 'integer',
            'revisions_reopened_until' => 'datetime',
        ];
    }

    /** Internal notes must never be exposed to the customer. */
    protected $hidden = ['internal_notes'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    public function hostingAccount(): BelongsTo
    {
        return $this->belongsTo(HostingAccount::class);
    }

    public function assignedDeveloper(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_developer_id');
    }

    public function assets(): HasMany
    {
        return $this->hasMany(WebsiteProjectAsset::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(WebsiteProjectMessage::class);
    }

    /** Customer-visible messages only — internal notes never reach the customer. */
    public function publicMessages(): HasMany
    {
        return $this->messages()->where('is_internal_note', false);
    }

    public function meetings(): HasMany
    {
        return $this->hasMany(WebsiteProjectMeeting::class);
    }

    /** When the current revision window closes (delivered_at + revision_days, or an admin re-open). */
    public function revisionWindowEndsAt(): ?\Illuminate\Support\Carbon
    {
        if ($this->revisions_reopened_until && $this->revisions_reopened_until->isFuture()) {
            return $this->revisions_reopened_until;
        }

        return $this->delivered_at?->copy()->addDays($this->revision_days ?: 14);
    }

    /** Whether the customer may request a revision right now. */
    public function canRequestRevision(): bool
    {
        if (! in_array($this->status, [
            \App\Enums\WebsiteProjectStatus::Delivered,
            \App\Enums\WebsiteProjectStatus::ReviewRequired,
        ], true)) {
            return false;
        }

        $endsAt = $this->revisionWindowEndsAt();

        return $endsAt !== null && $endsAt->isFuture();
    }

    public function revisionWindowHasEnded(): bool
    {
        $endsAt = $this->revisionWindowEndsAt();

        return $this->delivered_at !== null && $endsAt !== null && $endsAt->isPast();
    }
}
