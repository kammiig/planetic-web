<?php

namespace App\Policies;

use App\Models\CloudflareZone;
use App\Models\User;

class CloudflareZonePolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isTechnical($user);
    }

    public function view(User $user, CloudflareZone $zone): bool
    {
        return $this->owns($user, $zone) || $this->isTechnical($user);
    }

    public function update(User $user, CloudflareZone $zone): bool
    {
        return $this->isTechnical($user);
    }
}
