<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Adds the owning-user relationship and an `ownedBy` query scope to any
 * customer-owned model. Every customer-facing query must constrain by the
 * authenticated user (Security & Access §7) — use Model::ownedBy($user).
 */
trait BelongsToUser
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeOwnedBy(Builder $query, User|int $user): Builder
    {
        return $query->where(
            $this->getTable().'.user_id',
            $user instanceof User ? $user->getKey() : $user
        );
    }

    /** True if the given user owns this record. */
    public function isOwnedBy(?User $user): bool
    {
        return $user !== null && (int) $this->user_id === (int) $user->getKey();
    }
}
