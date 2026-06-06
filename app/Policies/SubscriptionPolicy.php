<?php

namespace App\Policies;

use App\Models\Subscription;
use App\Models\User;

class SubscriptionPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isBilling($user) || $this->isSupport($user);
    }

    public function view(User $user, Subscription $subscription): bool
    {
        return $this->owns($user, $subscription)
            || $this->isBilling($user)
            || $this->isSupport($user);
    }

    /** Customers may request cancellation of their own subscription. */
    public function requestCancellation(User $user, Subscription $subscription): bool
    {
        return $this->owns($user, $subscription) || $this->isBilling($user);
    }

    public function update(User $user, Subscription $subscription): bool
    {
        return $this->isBilling($user);
    }
}
