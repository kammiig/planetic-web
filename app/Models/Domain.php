<?php

namespace App\Models;

use App\Enums\DomainStatus;
use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Domain extends Model
{
    use BelongsToUser, SoftDeletes;

    protected $fillable = [
        'user_id', 'order_id', 'domain_name', 'sld', 'tld', 'registrar',
        'registrar_domain_id', 'registrar_order_id', 'status',
        'registration_date', 'expiry_date', 'auto_renew', 'whois_privacy',
        'registrar_lock', 'cloudflare_zone_id', 'nameservers', 'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => DomainStatus::class,
            'registration_date' => 'date',
            'expiry_date' => 'date',
            'auto_renew' => 'boolean',
            'whois_privacy' => 'boolean',
            'registrar_lock' => 'boolean',
            'nameservers' => 'array',
            'last_synced_at' => 'datetime',
        ];
    }

    /**
     * Registrar identifiers are internal — keep them out of any JSON the
     * customer might receive.
     */
    protected $hidden = ['registrar_domain_id', 'registrar_order_id'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(DomainContact::class);
    }

    public function cloudflareZone(): BelongsTo
    {
        return $this->belongsTo(CloudflareZone::class);
    }

    public function dnsRecords(): HasMany
    {
        return $this->hasMany(DnsRecord::class);
    }

    public function hostingAccount(): HasOne
    {
        return $this->hasOne(HostingAccount::class);
    }

    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class);
    }

    public function isActive(): bool
    {
        return $this->status === DomainStatus::Active;
    }
}
