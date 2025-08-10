<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Server;

class ServerPolicy
{
    public function viewAny(User $user): bool { return $user->canManageServers() || $user->canViewReports(); }
    public function view(User $user, Server $server): bool { return $this->viewAny($user); }
    public function create(User $user): bool { return $user->canManageServers(); }
    public function update(User $user, Server $server): bool { return $user->canManageServers(); }
    public function delete(User $user, Server $server): bool { return $user->canManageServers(); }
    public function metrics(User $user, Server $server): bool { return $this->viewAny($user); }
}
