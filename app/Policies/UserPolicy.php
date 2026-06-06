<?php

namespace App\Policies;

use App\Models\User;

/**
 * Customer/staff records in the admin panel. Staff may view customers
 * (Ticket 44); only Super Admins may create users, change roles, or
 * activate/deactivate accounts.
 */
class UserPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    public function view(User $user, User $model): bool
    {
        return $user->isStaff();
    }

    /** Create / update / role changes are Super Admin only (via before()). */
    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, User $model): bool
    {
        return false;
    }

    public function delete(User $user, User $model): bool
    {
        return false;
    }
}
