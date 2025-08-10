<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Customer;

class CustomerPolicy
{
    public function viewAny(User $user): bool { return $user->canManageCustomers() || $user->hasStaffPermission('view_customers'); }
    public function view(User $user, Customer $customer): bool { return $this->viewAny($user); }
    public function create(User $user): bool { return $user->canManageCustomers(); }
    public function update(User $user, Customer $customer): bool { return $user->canManageCustomers(); }
    public function delete(User $user, Customer $customer): bool { return $user->isAdmin(); }
    public function export(User $user): bool { return $user->hasStaffPermission('export_customer_data') || $user->isAdmin(); }
}
