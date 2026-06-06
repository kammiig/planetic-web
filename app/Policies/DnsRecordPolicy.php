<?php

namespace App\Policies;

use App\Models\DnsRecord;
use App\Models\User;

class DnsRecordPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isTechnical($user);
    }

    public function view(User $user, DnsRecord $record): bool
    {
        return $this->owns($user, $record) || $this->isTechnical($user);
    }

    /** Create / update / delete DNS records — technical admins only. */
    public function create(User $user): bool
    {
        return $this->isTechnical($user);
    }

    public function update(User $user, DnsRecord $record): bool
    {
        return $this->isTechnical($user);
    }

    public function delete(User $user, DnsRecord $record): bool
    {
        return $this->isTechnical($user);
    }
}
