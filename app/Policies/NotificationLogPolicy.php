<?php

namespace App\Policies;

use App\Models\NotificationLog;
use App\Models\User;

class NotificationLogPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    public function view(User $user, NotificationLog $log): bool
    {
        return $user->isStaff();
    }

    public function resend(User $user, NotificationLog $log): bool
    {
        return $this->isSupport($user) || $this->isBilling($user) || $this->isTechnical($user);
    }

    // No create/update/delete from the admin UI.
}
