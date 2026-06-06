<?php

namespace App\Policies;

use App\Models\ProvisioningJob;
use App\Models\User;

class ProvisioningJobPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isTechnical($user) || $this->isSupport($user) || $this->isBilling($user);
    }

    public function view(User $user, ProvisioningJob $job): bool
    {
        return $this->isTechnical($user) || $this->isSupport($user) || $this->isBilling($user);
    }

    /** Retrying a provisioning step is a technical action. */
    public function retry(User $user, ProvisioningJob $job): bool
    {
        return $this->isTechnical($user);
    }

    public function update(User $user, ProvisioningJob $job): bool
    {
        return $this->isTechnical($user);
    }

    // No create/delete — provisioning jobs are system-managed.
}
