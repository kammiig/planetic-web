<?php

namespace App\Policies;

use App\Models\SeoMeta;
use App\Models\User;

/** Catalogue/content managed by Super Admin (via before()); staff may view. */
class SeoMetaPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    public function view(User $user, SeoMeta $model): bool
    {
        return $user->isStaff();
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, SeoMeta $model): bool
    {
        return false;
    }

    public function delete(User $user, SeoMeta $model): bool
    {
        return false;
    }
}
