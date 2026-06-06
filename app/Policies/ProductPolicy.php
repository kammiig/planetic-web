<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

/** Catalogue is viewable by staff; only Super Admins may edit (via before()). */
class ProductPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    public function view(User $user, Product $product): bool
    {
        return $user->isStaff();
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Product $product): bool
    {
        return false;
    }

    public function delete(User $user, Product $product): bool
    {
        return false;
    }
}
