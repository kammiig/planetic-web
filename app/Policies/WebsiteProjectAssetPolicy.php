<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WebsiteProjectAsset;

class WebsiteProjectAssetPolicy extends BasePolicy
{
    public function view(User $user, WebsiteProjectAsset $asset): bool
    {
        return $this->owns($user, $asset)
            || $this->isDeveloper($user)
            || $this->isSupport($user);
    }

    /** Authenticated owner may upload assets to their own project. */
    public function create(User $user): bool
    {
        return true;
    }

    public function delete(User $user, WebsiteProjectAsset $asset): bool
    {
        return $this->owns($user, $asset) || $this->isDeveloper($user);
    }
}
