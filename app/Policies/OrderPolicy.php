<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Order;

class OrderPolicy
{
    public function viewAny(User $user): bool { return $user->hasStaffPermission('view_orders') || $user->hasStaffPermission('manage_orders') || $user->isAdmin() || $user->isManager(); }
    public function view(User $user, Order $order): bool { return $this->viewAny($user); }
    // Staff users must not create orders manually; orders are placed by customers via storefront/checkout.
    public function create(User $user): bool { return false; }
    public function update(User $user, Order $order): bool { return $user->isAdmin() || $user->isManager(); }
    public function delete(User $user, Order $order): bool { return $user->isAdmin(); }
    public function export(User $user): bool { return $user->hasStaffPermission('export_data') || $user->isAdmin() || $user->isManager(); }
}
