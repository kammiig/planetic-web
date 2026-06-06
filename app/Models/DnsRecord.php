<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DnsRecord extends Model
{
    use BelongsToUser;

    protected $fillable = [
        'user_id', 'domain_id', 'cloudflare_zone_id', 'cloudflare_record_id',
        'type', 'name', 'content', 'ttl', 'proxied', 'priority', 'status',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'ttl' => 'integer',
            'proxied' => 'boolean',
            'priority' => 'integer',
            'metadata' => 'array',
        ];
    }

    /** Internal Cloudflare record id — hidden from customer-facing output. */
    protected $hidden = ['cloudflare_record_id'];

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    public function cloudflareZone(): BelongsTo
    {
        return $this->belongsTo(CloudflareZone::class);
    }

    /** Records that must never be proxied (email/control-panel/service). */
    public function isProxySafe(): bool
    {
        return in_array($this->name, config('cloudflare.proxyable_names', ['@', 'www']), true);
    }
}
