<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ServerPlan;

class ServerPlanPolicy
{
    public function viewAny(User $user): bool { return $user->hasStaffPermission('manage_server_plans') || $user->isAdmin() || $user->isManager(); }
    public function view(User $user, ServerPlan $plan): bool { return $this->viewAny($user); }
    public function create(User $user): bool { return $this->viewAny($user); }
    public function update(User $user, ServerPlan $plan): bool { return $this->viewAny($user); }
    public function delete(User $user, ServerPlan $plan): bool { return $user->isAdmin(); }
}
