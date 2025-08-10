<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ServerClient;

class ServerClientPolicy
{
    public function viewAny(User $user): bool { return $user->canManageServers() || $user->canManageCustomers(); }
    public function view(User $user, ServerClient $client): bool { return $this->viewAny($user); }
    public function create(User $user): bool { return $user->canManageServers(); }
    public function update(User $user, ServerClient $client): bool { return $user->canManageServers(); }
    public function delete(User $user, ServerClient $client): bool { return $user->canManageServers(); }
}
