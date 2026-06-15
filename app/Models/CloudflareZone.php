<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CloudflareZone extends Model
{
    use BelongsToUser;

    protected $fillable = [
        'user_id', 'domain_id', 'zone_id', 'zone_name', 'status',
        'name_servers', 'ssl_mode', 'always_use_https',
        'created_on_cloudflare_at', 'last_synced_at',
    ];

    protected function casts(): array
    {
        return [
            'name_servers' => 'array',
            'always_use_https' => 'boolean',
            'created_on_cloudflare_at' => 'datetime',
            'last_synced_at' => 'datetime',
        ];
    }

    /** The Cloudflare zone id is an internal identifier — hide from customers. */
    protected $hidden = ['zone_id'];

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    public function dnsRecords(): HasMany
    {
        return $this->hasMany(DnsRecord::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /** Customer-facing DNS status. */
    public function dnsStatusLabel(): string
    {
        return $this->isActive()
            ? 'Active'
            : 'Waiting for nameserver verification';
    }

    /**
     * Customer-facing SSL status. Cloudflare cannot issue the edge certificate
     * until the domain's nameservers point at Cloudflare, so an unverified zone
     * shows "waiting" rather than a scary failed state.
     */
    public function sslStatusLabel(): string
    {
        if (! $this->isActive()) {
            return 'Waiting for nameserver verification';
        }

        $mode = ucfirst((string) ($this->ssl_mode ?: 'full'));

        return "Active ({$mode})";
    }
}
