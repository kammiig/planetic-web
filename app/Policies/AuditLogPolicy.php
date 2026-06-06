<?php

namespace App\Policies;

use App\Models\AuditLog;
use App\Models\User;

/**
 * Audit logs are visible to Super Admins only and are APPEND-ONLY: there is
 * deliberately no create/update/delete ability, so they can never be altered
 * or deleted from the admin UI (Security & Access §7, §14). This policy does
 * NOT extend BasePolicy, so not even a Super Admin's before() can grant
 * destructive abilities.
 */
class AuditLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function view(User $user, AuditLog $log): bool
    {
        return $user->isSuperAdmin();
    }
}
