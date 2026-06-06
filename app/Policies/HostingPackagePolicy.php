<?php

namespace App\Policies;

use App\Models\HostingPackage;
use App\Models\User;

/** WHM package mappings: staff view; Super Admin edits (via before()). */
class HostingPackagePolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    public function view(User $user, HostingPackage $package): bool
    {
        return $user->isStaff();
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, HostingPackage $package): bool
    {
        return false;
    }

    public function delete(User $user, HostingPackage $package): bool
    {
        return false;
    }
}
