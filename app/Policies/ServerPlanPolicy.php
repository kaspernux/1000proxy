<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ServerPlan;

class ServerPlanPolicy
{
    public function viewAny(User $user): bool { return $user->canManageServers(); }
    public function view(User $user, ServerPlan $plan): bool { return $this->viewAny($user); }
    public function create(User $user): bool { return $user->canManageServers(); }
    public function update(User $user, ServerPlan $plan): bool { return $user->canManageServers(); }
    public function delete(User $user, ServerPlan $plan): bool { return $user->isAdmin(); }
}
