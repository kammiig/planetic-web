<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HostingPackage extends Model
{
    protected $fillable = [
        'product_id', 'name', 'whm_package_name', 'disk_limit_mb',
        'bandwidth_limit_mb', 'email_accounts_limit', 'database_limit',
        'domain_limit', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'disk_limit_mb' => 'integer',
            'bandwidth_limit_mb' => 'integer',
            'email_accounts_limit' => 'integer',
            'database_limit' => 'integer',
            'domain_limit' => 'integer',
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
}
