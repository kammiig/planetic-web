<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WebsiteProject;

class WebsiteProjectPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isDeveloper($user) || $this->isSupport($user);
    }

    public function view(User $user, WebsiteProject $project): bool
    {
        return $this->owns($user, $project)
            || $this->isDeveloper($user)
            || $this->isSupport($user);
    }

    /** Customer submits their own intake form. */
    public function submitIntake(User $user, WebsiteProject $project): bool
    {
        return $this->owns($user, $project);
    }

    /** Update status / internal notes — website developers. */
    public function update(User $user, WebsiteProject $project): bool
    {
        return $this->isDeveloper($user);
    }
}
