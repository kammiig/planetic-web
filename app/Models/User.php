<?php

namespace App\Models;

use App\Enums\RoleName;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;

class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'company_name',
        'billing_address_line_1',
        'billing_address_line_2',
        'billing_city',
        'billing_state',
        'billing_postcode',
        'billing_country',
        'stripe_customer_id',
        'is_admin',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Roles & Access
    |--------------------------------------------------------------------------
    */

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)->withTimestamps();
    }

    public function hasRole(RoleName|string $role): bool
    {
        $name = $role instanceof RoleName ? $role->value : $role;

        return $this->roles->contains('name', $name);
    }

    /** @param array<int, RoleName|string> $roles */
    public function hasAnyRole(array $roles): bool
    {
        $names = array_map(fn ($r) => $r instanceof RoleName ? $r->value : $r, $roles);

        return $this->roles->whereIn('name', $names)->isNotEmpty();
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(RoleName::SuperAdmin);
    }

    /** Any staff/admin role (or the legacy is_admin flag) grants panel access. */
    public function isStaff(): bool
    {
        return $this->is_admin || $this->hasAnyRole(RoleName::staffRoles());
    }

    public function isCustomer(): bool
    {
        return ! $this->isStaff();
    }

    public function canAccessAdminPanel(): bool
    {
        return $this->isStaff() && $this->status === 'active';
    }

    /** Filament panel access — only active staff/admin users. */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->canAccessAdminPanel();
    }

    public function assignRole(RoleName|string $role): void
    {
        $name = $role instanceof RoleName ? $role->value : $role;
        $roleModel = Role::where('name', $name)->firstOrFail();
        $this->roles()->syncWithoutDetaching([$roleModel->id]);
        $this->unsetRelation('roles');
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships (customer-owned records)
    |--------------------------------------------------------------------------
    */

    public function carts(): HasMany
    {
        return $this->hasMany(Cart::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function domains(): HasMany
    {
        return $this->hasMany(Domain::class);
    }

    public function hostingAccounts(): HasMany
    {
        return $this->hasMany(HostingAccount::class);
    }

    public function cloudflareZones(): HasMany
    {
        return $this->hasMany(CloudflareZone::class);
    }

    public function websiteProjects(): HasMany
    {
        return $this->hasMany(WebsiteProject::class);
    }

    public function supportTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class);
    }

    /** Tickets assigned to this user as a staff member. */
    public function assignedTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class, 'assigned_admin_id');
    }

    /** Active (non-cancelled/expired) subscriptions. */
    public function activeSubscriptions(): Collection
    {
        return $this->subscriptions->filter(fn (Subscription $s) => $s->status?->isActive());
    }
}
