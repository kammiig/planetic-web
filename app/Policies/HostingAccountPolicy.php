<?php

namespace App\Policies;

use App\Models\HostingAccount;
use App\Models\User;

class HostingAccountPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isTechnical($user) || $this->isSupport($user) || $this->isBilling($user);
    }

    public function view(User $user, HostingAccount $account): bool
    {
        return $this->owns($user, $account)
            || $this->isTechnical($user)
            || $this->isSupport($user)
            || $this->isBilling($user);
    }

    /** Suspend / unsuspend / retry / sync — technical admins only. */
    public function manage(User $user, HostingAccount $account): bool
    {
        return $this->isTechnical($user);
    }

    public function update(User $user, HostingAccount $account): bool
    {
        return $this->isTechnical($user);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function delete(User $user, HostingAccount $account): bool
    {
        return false;
    }
}
