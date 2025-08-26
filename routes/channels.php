<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Auth;

Broadcast::channel('conversations.{conversationId}', function ($user, int $conversationId) {
    // Authorize if the current actor (user or customer) is a participant
    $actor = Auth::user() ?: Auth::guard('customer')->user();
    if (!$actor) return false;

    $type = get_class($actor);

    // Admin users may access any conversation
    if ($actor instanceof \App\Models\User && ((method_exists($actor, 'hasRole') && $actor->hasRole('admin')) || (property_exists($actor, 'role') && $actor->role === 'admin'))) {
        return true;
    }

    return \App\Models\Conversation::query()
        ->where('id', $conversationId)
        ->whereHas('participants', function ($q) use ($actor, $type) {
            $q->where('participant_type', $type)->where('participant_id', $actor->getKey());
        })
        ->exists();
});

// Presence channel for typing/presence
Broadcast::channel('presence-conversations.{conversationId}', function ($user, int $conversationId) {
    $actor = Auth::user() ?: Auth::guard('customer')->user();
    if (!$actor) return false;

    $type = get_class($actor);
    $conversation = \App\Models\Conversation::query()
        ->where('id', $conversationId)
        ->first();

    if (!$conversation) return false;
    // Internal rooms are staff-only for presence
    if (($conversation->privacy ?? 'private') === 'internal' && $type !== \App\Models\User::class) {
        return false;
    }

    // Admin users may observe presence even if not explicitly listed, but we keep presence limited to participants for privacy.
    $isParticipant = $conversation->participants()
        ->where('participant_type', $type)
        ->where('participant_id', $actor->getKey())
        ->exists();
    if (!$isParticipant) return false;

    // Return member info for presence lists
    return [
        'id' => $actor->getKey(),
        'name' => $actor->name ?? 'Member',
        'type' => class_basename($type),
    ];
});
