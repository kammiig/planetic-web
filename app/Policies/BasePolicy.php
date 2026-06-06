<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Shared policy behaviour:
 *  - Super Admins bypass all checks (handled in before()).
 *  - owns() centralises the row-level ownership test every customer query
 *    must satisfy (Security & Access §7).
 *  - Role helpers map the staff roles to the permission groups described in
 *    the Role Permission Summary.
 */
abstract class BasePolicy
{
    public function before(User $user, string $ability): ?bool
    {
        // Note: even Super Admins are never granted audit-log deletion — no
        // such ability/route exists anywhere in the app.
        return $user->isSuperAdmin() ? true : null;
    }

    protected function owns(User $user, Model $model): bool
    {
        if (method_exists($model, 'isOwnedBy')) {
            return $model->isOwnedBy($user);
        }

        return isset($model->user_id) && (int) $model->user_id === (int) $user->getKey();
    }

    protected function isTechnical(User $user): bool
    {
        return $user->hasRole(RoleName::TechnicalAdmin);
    }

    protected function isBilling(User $user): bool
    {
        return $user->hasRole(RoleName::BillingManager);
    }

    protected function isSupport(User $user): bool
    {
        return $user->hasRole(RoleName::SupportStaff);
    }

    protected function isDeveloper(User $user): bool
    {
        return $user->hasRole(RoleName::WebsiteDeveloper);
    }
}
