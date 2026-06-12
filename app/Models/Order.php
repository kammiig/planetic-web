<?php

namespace App\Models;

use App\Enums\ItemType;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use BelongsToUser, SoftDeletes;

    protected $fillable = [
        'user_id', 'order_number', 'status', 'payment_status', 'currency',
        'subtotal', 'discount_total', 'tax_total', 'total',
        'stripe_checkout_session_id', 'stripe_payment_intent_id',
        'stripe_subscription_id', 'admin_notes', 'paid_at', 'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'payment_status' => PaymentStatus::class,
            'subtotal' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'total' => 'decimal:2',
            'paid_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    public function provisioningJobs(): HasMany
    {
        return $this->hasMany(ProvisioningJob::class);
    }

    public function domain(): HasOne
    {
        return $this->hasOne(Domain::class);
    }

    public function hostingAccount(): HasOne
    {
        return $this->hasOne(HostingAccount::class);
    }

    public function websiteProject(): HasOne
    {
        return $this->hasOne(WebsiteProject::class);
    }

    public function isPaid(): bool
    {
        return $this->payment_status === PaymentStatus::Succeeded || $this->paid_at !== null;
    }

    public function containsWebsitePackage(): bool
    {
        return $this->items->contains('item_type', ItemType::WebsitePackage);
    }

    /** First domain name attached to any line item, if any. */
    public function primaryDomainName(): ?string
    {
        return $this->items->firstWhere(fn (OrderItem $i) => filled($i->domain_name))?->domain_name;
    }

    public function needsHosting(): bool
    {
        return $this->items->contains(
            fn (OrderItem $i) => in_array($i->item_type, [ItemType::WebsitePackage, ItemType::Hosting], true)
        );
    }

    /**
     * The customer's domain decision for this order, as captured at checkout:
     * 'new' (register via us), 'existing' (they already own it — never
     * registered or charged by us), or 'later' (website package only).
     * Falls back to deriving 'new' from any domain-carrying line for legacy
     * orders placed before domain choice existed.
     *
     * @return array{source: ?string, domain: ?string}
     */
    public function domainChoice(): array
    {
        $this->loadMissing('items');

        $carrier = $this->items->first(fn (OrderItem $i) => filled($i->metadata['domain_source'] ?? null));

        if ($carrier) {
            return [
                'source' => $carrier->metadata['domain_source'],
                'domain' => $carrier->domain_name ?: $this->primaryDomainName(),
            ];
        }

        $domain = $this->primaryDomainName();

        return ['source' => $domain ? 'new' : null, 'domain' => $domain];
    }
}
