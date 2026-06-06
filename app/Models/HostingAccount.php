<?php

namespace App\Models;

use App\Enums\HostingStatus;
use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class HostingAccount extends Model
{
    use BelongsToUser, SoftDeletes;

    protected $fillable = [
        'user_id', 'order_id', 'domain_id', 'hosting_package_id', 'domain_name',
        'whm_username', 'whm_account_id', 'server_hostname', 'server_ip',
        'cpanel_url', 'status', 'disk_limit_mb', 'bandwidth_limit_mb',
        'created_on_whm_at', 'suspended_at', 'suspension_reason',
        'renewal_date', 'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => HostingStatus::class,
            'disk_limit_mb' => 'integer',
            'bandwidth_limit_mb' => 'integer',
            'created_on_whm_at' => 'datetime',
            'suspended_at' => 'datetime',
            'renewal_date' => 'date',
            'last_synced_at' => 'datetime',
        ];
    }

    /** Internal WHM identifier — never expose to customers. */
    protected $hidden = ['whm_account_id'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    public function hostingPackage(): BelongsTo
    {
        return $this->belongsTo(HostingPackage::class);
    }

    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class);
    }

    public function isActive(): bool
    {
        return $this->status === HostingStatus::Active;
    }

    public function isSuspended(): bool
    {
        return $this->status === HostingStatus::Suspended;
    }
}
