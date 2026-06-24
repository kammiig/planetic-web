<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HostingPackage extends Model
{
    protected $fillable = [
        'product_id', 'name', 'tagline', 'whm_package_name', 'disk_limit_mb',
        'bandwidth_limit_mb', 'email_accounts_limit', 'database_limit',
        'domain_limit', 'features', 'ssl_included', 'is_popular', 'sort_order',
        'includes_free_domain', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'disk_limit_mb' => 'integer',
            'bandwidth_limit_mb' => 'integer',
            'email_accounts_limit' => 'integer',
            'database_limit' => 'integer',
            'domain_limit' => 'integer',
            'features' => 'array',
            'ssl_included' => 'boolean',
            'is_popular' => 'boolean',
            'sort_order' => 'integer',
            'includes_free_domain' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function hostingAccounts(): HasMany
    {
        return $this->hasMany(HostingAccount::class);
    }

    /** Human-friendly disk display, e.g. "10 GB" or "5000 MB". */
    public function diskLabel(): string
    {
        if ($this->disk_limit_mb === null) {
            return 'Unlimited';
        }

        return $this->disk_limit_mb >= 1024
            ? round($this->disk_limit_mb / 1024, 1).' GB'
            : $this->disk_limit_mb.' MB';
    }

    /**
     * The bullet-point feature list shown on the plan card. Uses the admin's
     * custom list when provided, otherwise derives sensible bullets from the
     * configured limits so a plan always shows something meaningful.
     *
     * @return array<int, string>
     */
    public function featureList(): array
    {
        if (is_array($this->features) && count(array_filter($this->features))) {
            return array_values(array_filter($this->features));
        }

        return array_values(array_filter([
            $this->diskLabel().' SSD storage',
            $this->bandwidth_limit_mb ? round($this->bandwidth_limit_mb / 1024).' GB bandwidth' : 'Unmetered bandwidth',
            $this->email_accounts_limit ? $this->email_accounts_limit.' email accounts' : 'Unlimited email accounts',
            $this->database_limit ? $this->database_limit.' databases' : 'Unlimited databases',
            $this->ssl_included ? 'Free SSL certificate' : null,
            $this->includes_free_domain ? 'Free domain for the first year' : null,
            'cPanel control panel',
            'Cloudflare DNS & CDN',
        ]));
    }
}
