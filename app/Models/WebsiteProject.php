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
}
