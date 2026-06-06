<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isBilling($user) || $this->isSupport($user) || $this->isTechnical($user);
    }

    public function view(User $user, Order $order): bool
    {
        return $this->owns($user, $order)
            || $this->isBilling($user)
            || $this->isSupport($user)
            || $this->isTechnical($user);
    }

    /** Add internal notes / cancel where allowed — billing or technical staff. */
    public function update(User $user, Order $order): bool
    {
        return $this->isBilling($user) || $this->isTechnical($user);
    }

    public function delete(User $user, Order $order): bool
    {
        return false;
    }
}
