<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isBilling($user) || $this->isSupport($user);
    }

    public function view(User $user, Payment $payment): bool
    {
        return $this->owns($user, $payment)
            || $this->isBilling($user)
            || $this->isSupport($user);
    }

    public function update(User $user, Payment $payment): bool
    {
        return false;
    }

    public function delete(User $user, Payment $payment): bool
    {
        return false;
    }
}
