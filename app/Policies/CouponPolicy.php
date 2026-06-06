<?php

namespace App\Policies;

use App\Models\Coupon;
use App\Models\User;

class CouponPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isBilling($user);
    }

    public function view(User $user, Coupon $coupon): bool
    {
        return $this->isBilling($user);
    }

    /** Coupon management is Super Admin / billing oversight. */
    public function create(User $user): bool
    {
        return $this->isBilling($user);
    }

    public function update(User $user, Coupon $coupon): bool
    {
        return $this->isBilling($user);
    }

    public function delete(User $user, Coupon $coupon): bool
    {
        return false;
    }
}
