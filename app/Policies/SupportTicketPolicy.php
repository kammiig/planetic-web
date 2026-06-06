<?php

namespace App\Policies;

use App\Models\SupportTicket;
use App\Models\User;

class SupportTicketPolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStaff();
    }

    public function view(User $user, SupportTicket $ticket): bool
    {
        return $this->owns($user, $ticket) || $user->isStaff();
    }

    /** Any authenticated customer may open their own ticket. */
    public function create(User $user): bool
    {
        return true;
    }

    /** Owner replies to own ticket; staff reply publicly. */
    public function reply(User $user, SupportTicket $ticket): bool
    {
        return $this->owns($user, $ticket) || $user->isStaff();
    }

    /** Internal notes, status changes, assignment — staff only. */
    public function manage(User $user, SupportTicket $ticket): bool
    {
        return $this->isSupport($user) || $this->isTechnical($user) || $this->isBilling($user);
    }

    public function update(User $user, SupportTicket $ticket): bool
    {
        return $user->isStaff();
    }
}
