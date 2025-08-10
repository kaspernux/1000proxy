<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Order;

class OrderPolicy
{
    public function viewAny(User $user): bool { return $user->hasStaffPermission('view_orders') || $user->isAdmin(); }
    public function view(User $user, Order $order): bool { return $this->viewAny($user); }
    public function create(User $user): bool { return $user->isAdmin(); }
    public function update(User $user, Order $order): bool { return $user->isAdmin(); }
    public function delete(User $user, Order $order): bool { return $user->isAdmin(); }
    public function export(User $user): bool { return $user->hasStaffPermission('export_data') || $user->isAdmin(); }
}
