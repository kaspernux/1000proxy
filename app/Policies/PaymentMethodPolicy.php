<?php

namespace App\Policies;

use App\Models\User;
use App\Models\PaymentMethod;

class PaymentMethodPolicy
{
    public function viewAny(User $user): bool { return $user->hasStaffPermission('manage_payments') || $user->isAdmin(); }
    public function view(User $user, PaymentMethod $pm): bool { return $this->viewAny($user); }
    public function create(User $user): bool { return $user->hasStaffPermission('manage_payments'); }
    public function update(User $user, PaymentMethod $pm): bool { return $user->hasStaffPermission('manage_payments'); }
    public function delete(User $user, PaymentMethod $pm): bool { return $user->isAdmin(); }
}
