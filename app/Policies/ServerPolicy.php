<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Server;

class ServerPolicy
{
    public function viewAny(User $user): bool { return $user->canManageServers() || $user->canViewReports(); }
    public function view(User $user, Server $server): bool { return $this->viewAny($user); }
    // Only admin can create new remote servers
    public function create(User $user): bool { return $user->isAdmin(); }
    // Managers can update/delete existing servers
    public function update(User $user, Server $server): bool { return $user->isAdmin() || $user->isManager(); }
    public function delete(User $user, Server $server): bool { return $user->isAdmin() || $user->isManager(); }
    public function metrics(User $user, Server $server): bool { return $this->viewAny($user); }
}
