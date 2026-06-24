<?php

namespace App\Policies;

use App\Models\WebsitePackage;
use App\Models\User;

/** Catalogue/content managed by Super Admin (via before()); staff may view. */
class WebsitePackagePolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    public function view(User $user, WebsitePackage $model): bool
    {
        return $user->isStaff();
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, WebsitePackage $model): bool
    {
        return false;
    }

    public function delete(User $user, WebsitePackage $model): bool
    {
        return false;
    }
}
