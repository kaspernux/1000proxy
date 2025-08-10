<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Invoice;

class InvoicePolicy
{
    public function viewAny(User $user): bool { return $user->hasStaffPermission('view_orders') || $user->isAdmin(); }
    public function view(User $user, Invoice $invoice): bool { return $this->viewAny($user); }
    public function create(User $user): bool { return $user->isAdmin(); }
    public function update(User $user, Invoice $invoice): bool { return $user->isAdmin(); }
    public function delete(User $user, Invoice $invoice): bool { return $user->isAdmin(); }
    public function export(User $user): bool { return $user->hasStaffPermission('export_data') || $user->isAdmin(); }
}
