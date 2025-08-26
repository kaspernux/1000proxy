<?php

namespace App\Policies;

use App\Models\Conversation;
use App\Models\Customer;
use App\Models\User;

class ConversationPolicy
{
    public function view(User|Customer $actor, Conversation $conversation): bool
    {
        // Admin users can view any conversation
        if ($actor instanceof User && ((method_exists($actor, 'hasRole') && $actor->hasRole('admin')) || (property_exists($actor, 'role') && $actor->role === 'admin'))) {
            return true;
        }

        return $conversation->participants()
            ->where('participant_type', get_class($actor))
            ->where('participant_id', $actor->getKey())
            ->exists();
    }
}
