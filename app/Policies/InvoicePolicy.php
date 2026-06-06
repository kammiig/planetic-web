<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy extends BasePolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isBilling($user) || $this->isSupport($user);
    }

    public function view(User $user, Invoice $invoice): bool
    {
        return $this->owns($user, $invoice)
            || $this->isBilling($user)
            || $this->isSupport($user);
    }

    public function download(User $user, Invoice $invoice): bool
    {
        return $this->owns($user, $invoice) || $this->isBilling($user);
    }

    /** Marking invoices paid is Super Admin only (handled by before()). */
    public function update(User $user, Invoice $invoice): bool
    {
        return false;
    }

    public function delete(User $user, Invoice $invoice): bool
    {
        return false;
    }
}
