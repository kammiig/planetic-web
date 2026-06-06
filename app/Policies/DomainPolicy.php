<?php

namespace App\Policies;

use App\Models\Domain;
use App\Models\User;

class DomainPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isTechnical($user) || $this->isSupport($user) || $this->isBilling($user);
    }

    public function view(User $user, Domain $domain): bool
    {
        return $this->owns($user, $domain)
            || $this->isTechnical($user)
            || $this->isSupport($user)
            || $this->isBilling($user);
    }

    /** Retry registrar actions, sync, mark manual review — technical only. */
    public function update(User $user, Domain $domain): bool
    {
        return $this->isTechnical($user);
    }

    public function create(User $user): bool
    {
        return false; // domains are created by the provisioning pipeline only
    }

    public function delete(User $user, Domain $domain): bool
    {
        return false; // soft-delete via Super Admin only (before())
    }
}
