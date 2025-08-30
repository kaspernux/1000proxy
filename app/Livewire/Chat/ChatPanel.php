<?php

namespace App\Livewire\Chat;

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Customer;
use App\Models\Message;
use App\Models\MessageAttachment;
use App\Models\MessageReaction;
use App\Models\MessageRead;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

class ChatPanel extends Component
{
    use WithFileUploads;

    public ?int $activeConversationId = null;
    public string $message = '';
    public array $uploads = [];

    public bool $showNewChatModal = false;
    public string $newChatType = 'direct'; // direct | group
    public string $newChatPrivacy = 'private'; // for groups: private | public | internal
    public string $newChatName = '';
    public array $newChatParticipants = []; // array of [type,id]

    #[Url]
    public string $q = '';

    public bool $minimal = false; // if rendered in widget mode

    // Pagination & in-chat search
    public int $messagesPage = 1;
    public int $messagesPerPage = 50;
    public int $messagesMax = 500; // cap total messages rendered
    public string $inChatQ = '';

    // Invite modal
    public bool $showInviteModal = false;

    public function mount(?int $conversation = null, bool $minimal = false): void
    {
        $this->minimal = $minimal;
        // For public widget guests, ensure a per-session guest key to link their messages
        if ($this->minimal && $this->isGuest()) {
            $this->ensureGuestKey();
        }
        if ($conversation) {
            $this->activeConversationId = $conversation;
        } else {
            // In minimal guest mode there is no list; wait until first send to create
            if (!($this->minimal && $this->isGuest())) {
                $first = $this->conversationsQuery()->latest('updated_at')->first();
                $this->activeConversationId = $first?->id;
            }
        }
        $this->messagesPage = 1;
    }

    // Typing indicators: receive browser events from Echo to toggle state
    public array $typing = [];
    public array $online = [];

    #[On('presence-update')]
    public function presenceUpdate($payload = null, $online = null, $typing = null): void
    {
        if (is_array($payload)) {
            $this->online = $payload['online'] ?? $this->online;
            $this->typing = $payload['typing'] ?? $this->typing;
            return;
        }
        if (is_array($online) || is_array($typing)) {
            if (is_array($online)) $this->online = $online;
            if (is_array($typing)) $this->typing = $typing;
        }
    }

    public function emitTyping(): void
    {
        // Frontend handles emitting via Echo directly for lower latency.
        // This is a noop reserved for possible server-origin typing.
    }

    private function actor(): User|Customer
    {
        $user = auth()->user();
        if ($user instanceof User) return $user;
        $customer = auth('customer')->user();
        if ($customer instanceof Customer) return $customer;
        abort(403);
    }

    private function conversationsQuery(): Builder
    {
        // In minimal guest mode, restrict to active conversation only (no sidebar listing)
        if ($this->minimal && $this->isGuest()) {
            return Conversation::query()->whereNull('archived_at')
                ->when($this->activeConversationId, fn($q) => $q->where('id', $this->activeConversationId));
        }

        $actor = $this->actor();
        $query = Conversation::query()
            ->with(['participants.participant','messages' => function($q){ $q->latest()->limit(1); }])
            ->whereNull('archived_at')
            ->when(!($actor instanceof User && $this->userIsAdmin($actor)), function ($q) use ($actor) {
                $q->whereHas('participants', function ($qq) use ($actor) {
                    $qq->where('participant_type', get_class($actor))
                       ->where('participant_id', $actor->getKey());
                });
            })
            ->when($this->q, function ($q) {
                $q->where(function ($qq) {
                    $qq->where('name', 'like', '%' . addcslashes($this->q, '%_') . '%')
                       ->orWhereHas('participants.participant', function ($qp) {
                           $qp->where('name', 'like', '%' . addcslashes($this->q, '%_') . '%');
                       });
                });
            });
        return $query;
    }

    // removed legacy ensureGuestCustomerSession()

    public function getConversationsProperty()
    {
        return $this->conversationsQuery()->orderByDesc('updated_at')->limit(50)->get();
    }

    public function getActiveConversationProperty(): ?Conversation
    {
        if (!$this->activeConversationId) return null;
        // Keep participants eager-loaded; messages will be fetched separately via computed property for pagination/search
        return Conversation::with(['participants.participant'])
            ->find($this->activeConversationId);
    }

    public function getActiveMessagesProperty()
    {
        $conv = $this->getActiveConversationProperty();
    if (!$conv) return collect();

        $q = $conv->messages()->with(['sender','attachments','reactions'])->orderBy('id');
        if ($this->inChatQ) {
            $needle = addcslashes($this->inChatQ, '%_');
            $q->where(function($qq) use ($needle){
                $qq->where('body', 'like', "%{$needle}%");
            })->limit(300); // cap results when searching
        } else {
            $q->limit(min($this->messagesPerPage * $this->messagesPage, $this->messagesMax));
        }
        return $q->get();
    }

    public function loadMoreMessages(): void
    {
        $maxPages = (int) ceil($this->messagesMax / $this->messagesPerPage);
        if ($this->messagesPage < $maxPages) {
            $this->messagesPage++;
        }
    // Front will keep scroll position via JS on this event
    $this->dispatch('messages-prepended');
    }

    public function updatedActiveConversationId(): void
    {
        // Reset per-conversation state
        $this->messagesPage = 1;
        $this->inChatQ = '';
    }

    public function createDirectChat(string $targetType, int $targetId)
    {
        $actor = $this->actor();

        // Enforce: Customers cannot chat with Customers
        if ($actor instanceof Customer && $targetType === Customer::class) {
            $this->alert('error', 'Customers cannot chat with other customers.', ['toast' => true, 'position' => 'top-end']);
            return;
        }

        // Resolve target model
        $target = $targetType === User::class ? User::findOrFail($targetId) : Customer::findOrFail($targetId);

        // Prevent internal-only groups via direct
        $conversation = $this->findOrCreateDirect([$actor, $target]);
        $this->activeConversationId = $conversation->id;
        $this->showNewChatModal = false;
    }

    private function findOrCreateDirect(array $participants): Conversation
    {
        // Find existing
        $query = Conversation::query()->where('type', 'direct');
        foreach ($participants as $p) {
            $query->whereHas('participants', function ($q) use ($p) {
                $q->where('participant_type', get_class($p))->where('participant_id', $p->getKey());
            });
        }
        $existing = $query->first();
        if ($existing) return $existing;

        // Create new
        $creator = $participants[0];
        $conv = Conversation::create([
            'type' => 'direct',
            'privacy' => 'private',
            'created_by_type' => get_class($creator),
            'created_by_id' => $creator->getKey(),
        ]);
        foreach ($participants as $p) {
            ConversationParticipant::create([
                'conversation_id' => $conv->id,
                'participant_type' => get_class($p),
                'participant_id' => $p->getKey(),
                'role' => $p instanceof User ? 'member' : 'member',
                'can_post' => true,
                'can_edit' => true,
                'can_delete' => $p instanceof User && $p->hasRole('admin'),
                'joined_at' => now(),
            ]);
        }
        return $conv;
    }

    public function createGroup(): void
    {
        $actor = $this->actor();
        $this->validate([
            'newChatName' => ['required','string','max:120'],
            'newChatPrivacy' => ['required', Rule::in(['private','public','internal'])],
        ]);

        // internal groups restricted to Users only
        if ($this->newChatPrivacy === 'internal' && $actor instanceof Customer) {
            $this->alert('error', 'Only staff can create internal groups.', ['toast' => true, 'position' => 'top-end']);
            return;
        }

        $conv = Conversation::create([
            'type' => 'group',
            'name' => $this->newChatName,
            'privacy' => $this->newChatPrivacy,
            'created_by_type' => get_class($actor),
            'created_by_id' => $actor->getKey(),
        ]);

        // Add creator as owner
        ConversationParticipant::create([
            'conversation_id' => $conv->id,
            'participant_type' => get_class($actor),
            'participant_id' => $actor->getKey(),
            'role' => 'owner',
            'can_post' => true,
            'can_edit' => true,
            'can_delete' => $actor instanceof User && $actor->hasRole('admin'),
            'joined_at' => now(),
        ]);

        $this->activeConversationId = $conv->id;
        $this->showNewChatModal = false;
        $this->newChatName = '';
    }

    public function sendMessage(): void
    {
        $conv = $this->getActiveConversationProperty();

        // Guest flow in public widget: create a support conversation without registering
        if (!$conv && $this->minimal && $this->isGuest()) {
            $this->ensureGuestKey();
            $support = $this->resolveSupportUser();
            if (!$support) {
                $this->alert('error', 'Support is currently unavailable. Please try again later.', ['toast' => true, 'position' => 'top-end']);
                return;
            }
            // Create a direct conversation with only support as a participant
            $conv = Conversation::create([
                'type' => 'direct',
                'privacy' => 'private',
                'created_by_type' => null,
                'created_by_id' => null,
            ]);
            ConversationParticipant::create([
                'conversation_id' => $conv->id,
                'participant_type' => User::class,
                'participant_id' => $support->getKey(),
                'role' => 'member',
                'can_post' => true,
                'can_edit' => true,
                'can_delete' => $support->hasRole('admin'),
                'joined_at' => now(),
            ]);
            $this->activeConversationId = $conv->id;
        }

        // Authenticated actor flow (staff or customer)
        $actor = (auth()->check() || auth('customer')->check()) ? $this->actor() : null;
        if (!$conv) return;

        // Permission: if authenticated, must be a participant and can_post
        if ($actor) {
            $participant = ConversationParticipant::query()
                ->where('conversation_id', $conv->id)
                ->where('participant_type', get_class($actor))
                ->where('participant_id', $actor->getKey())
                ->first();
            if (!$participant || !$participant->can_post) {
                $this->alert('error', 'You cannot post in this conversation.', ['toast' => true, 'position' => 'top-end']);
                return;
            }
        }

        $this->validate([
            'message' => ['nullable','string','max:4000'],
            'uploads.*' => ['file','max:20480'], // 20MB each
        ]);

        if (trim($this->message) === '' && empty($this->uploads)) {
            return; // nothing to send
        }

        $isFirstMessage = $conv->messages()->count() === 0;
        $meta = [];
        if ($this->minimal && $this->isGuest()) {
            $meta['guest'] = true;
            $meta['guest_key'] = session('chat_guest_key');
        }

        $msg = Message::create([
            'conversation_id' => $conv->id,
            // For guests, store a neutral sender reference; for authed, use actual actor
            'sender_type' => $actor ? get_class($actor) : User::class,
            'sender_id' => $actor ? $actor->getKey() : 0,
            'body' => trim($this->message) !== '' ? $this->message : null,
            'meta' => empty($meta) ? null : $meta,
            'delivered_at' => now(),
        ]);

        foreach ($this->uploads as $file) {
            $path = $file->store('chat_attachments', 'public');
            MessageAttachment::create([
                'message_id' => $msg->id,
                'disk' => 'public',
                'path' => $path,
                'filename' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
            ]);
        }

    $this->message = '';
        $this->uploads = [];

        // Touch conversation for sorting
        $conv->touch();
    // Broadcast event
    event(new \App\Events\Chat\MessageCreated($msg->id));
    $this->dispatch('message-sent');

        // Welcome + auto-response for customers and guests
        if (($actor instanceof \App\Models\Customer) || ($this->minimal && $this->isGuest())) {
            // Welcome message on first user message
            if ($isFirstMessage) {
                $welcome = Message::create([
                    'conversation_id' => $conv->id,
                    'sender_type' => \App\Models\User::class,
                    'sender_id' => 0,
                    'body' => 'Welcome! How can we help you today? You can ask about pricing, API, rotation, or refunds.',
                    'delivered_at' => now(),
                ]);
                event(new \App\Events\Chat\MessageCreated($welcome->id));
            }
            // Auto-responder
            $auto = app(\App\Services\Chat\AutoResponder::class)->answer($msg->body ?? '');
            if ($auto) {
                $bot = Message::create([
                    'conversation_id' => $conv->id,
                    'sender_type' => \App\Models\User::class,
                    'sender_id' => 0,
                    'body' => $auto,
                    'delivered_at' => now(),
                ]);
                event(new \App\Events\Chat\MessageCreated($bot->id));
            } else {
                // escalate to support manager (notify admins)
                try { \Log::info('Chat escalation required', ['conversation_id' => $conv->id]); } catch (\Throwable $e) {}
            }
        }
    }

    private function resolveSupportUser(): ?User
    {
        // Try to find an admin user using roles or a role column
        try {
            $candidates = User::query()->orderBy('id')->limit(50)->get();
        } catch (\Throwable $e) {
            return null;
        }
        foreach ($candidates as $u) {
            if (method_exists($u, 'hasRole') && $u->hasRole('admin')) return $u;
            if (isset($u->role) && $u->role === 'admin') return $u;
        }
        // fallback to first staff user if any
        return $candidates->first();
    }

    public function deleteMessage(int $messageId): void
    {
        $actor = $this->actor();
        $msg = Message::findOrFail($messageId);
        // Only admins can delete
        if (!($actor instanceof User && $actor->hasRole('admin'))) {
            $this->alert('error', 'Only admins can delete messages.', ['toast' => true, 'position' => 'top-end']);
            return;
        }
        $msg->update(['is_deleted' => true, 'body' => '[deleted]']);
        // Optionally delete attachments files
        foreach ($msg->attachments as $att) {
            try { Storage::disk($att->disk)->delete($att->path); } catch (\Throwable $e) {}
            $att->delete();
        }
        event(new \App\Events\Chat\MessageDeleted($msg->id));
        $this->dispatch('message-deleted', id: $msg->id);
    }

    // Admin-only conversation actions
    private function ensureAdmin(): ?User
    {
        $u = auth()->user();
        if ($u instanceof User && method_exists($u, 'hasRole') && $u->hasRole('admin')) return $u;
        if ($u instanceof User && property_exists($u, 'role') && $u->role === 'admin') return $u;
    $this->alert('error', 'Only admins can perform this action.', ['toast' => true, 'position' => 'top-end']);
        return null;
    }

    public function archiveConversation(): void
    {
        if (!$this->activeConversationId) return;
        if (!$this->ensureAdmin()) return;
        $conv = Conversation::find($this->activeConversationId);
        if (!$conv) return;
    event(new \App\Events\Chat\SessionTerminated($conv->id));
        $conv->archived_at = now();
        $conv->save();
    $this->alert('success', 'Conversation archived.', ['toast' => true, 'position' => 'top-end']);
        $this->activeConversationId = null;
    }

    public function unarchiveConversation(int $conversationId): void
    {
        if (!$this->ensureAdmin()) return;
        $conv = Conversation::withTrashed()->find($conversationId);
        if (!$conv) return;
        $conv->archived_at = null;
        $conv->save();
    $this->alert('success', 'Conversation unarchived.', ['toast' => true, 'position' => 'top-end']);
    }

    public function deleteConversation(): void
    {
        if (!$this->activeConversationId) return;
        if (!$this->ensureAdmin()) return;
        $conv = Conversation::find($this->activeConversationId);
        if (!$conv) return;
    event(new \App\Events\Chat\SessionTerminated($conv->id));
        $conv->delete(); // soft delete
    $this->alert('success', 'Conversation deleted.', ['toast' => true, 'position' => 'top-end']);
        $this->activeConversationId = null;
    }

    public function toggleReaction(int $messageId, string $emoji): void
    {
        $actor = $this->actor();
        $existing = MessageReaction::query()
            ->where('message_id', $messageId)
            ->where('reactor_type', get_class($actor))
            ->where('reactor_id', $actor->getKey())
            ->where('emoji', $emoji)
            ->first();
        if ($existing) {
            $existing->delete();
        } else {
            MessageReaction::create([
                'message_id' => $messageId,
                'reactor_type' => get_class($actor),
                'reactor_id' => $actor->getKey(),
                'emoji' => $emoji,
            ]);
        }
        event(new \App\Events\Chat\ReactionToggled($messageId));
        $this->dispatch('reaction-toggled', id: $messageId);
    }

    public function updateConversationPrivacy(string $privacy): void
    {
        $conv = $this->getActiveConversationProperty();
        if (!$conv || $conv->type !== 'group') return;
        $this->validate(['privacy' => [Rule::in(['private','public','internal'])]]);
        $conv->privacy = $privacy;
        $conv->save();
    $this->alert('success', 'Privacy updated.', ['toast' => true, 'position' => 'top-end']);
    }

    public function inviteParticipant(string $type, int $id): void
    {
        $conv = $this->getActiveConversationProperty();
        if (!$conv || $conv->type !== 'group') return;

        // internal groups: only Users can be invited
        if ($conv->privacy === 'internal' && $type !== User::class) {
            $this->alert('error', 'Internal groups are staff-only.', ['toast' => true, 'position' => 'top-end']);
            return;
        }

        $exists = ConversationParticipant::query()
            ->where('conversation_id', $conv->id)
            ->where('participant_type', $type)
            ->where('participant_id', $id)
            ->exists();
        if ($exists) {
            $this->alert('warning', 'Already a participant.', ['toast' => true, 'position' => 'top-end']);
            return;
        }

        ConversationParticipant::create([
            'conversation_id' => $conv->id,
            'participant_type' => $type,
            'participant_id' => $id,
            'role' => 'member',
            'can_post' => true,
            'can_edit' => true,
            'can_delete' => false,
            'joined_at' => now(),
        ]);
    $this->alert('success', 'Participant invited.', ['toast' => true, 'position' => 'top-end']);
    }

    #[On('message-list-visible')]
    public function markVisibleAsRead(): void
    {
        if ($this->isGuest()) {
            // Guests don't have read receipts; skip to avoid 403 via actor()
            return;
        }
        $actor = $this->actor();
        $conv = $this->getActiveConversationProperty();
        if (!$conv) return;
        $ids = $conv->messages()->pluck('id');
        foreach ($ids as $id) {
            MessageRead::firstOrCreate([
                'message_id' => $id,
                'reader_type' => get_class($actor),
                'reader_id' => $actor->getKey(),
            ], ['read_at' => now()]);
        }
    }

    public function render()
    {
        $staffOptions = User::query()->active()->orderBy('name')->limit(50)->get(['id','name']);
        $customerOptions = collect();
        // Only show customer options to authenticated staff; avoid calling actor() when guest
        if (auth()->check() && auth()->user() instanceof User) {
            $customerOptions = Customer::query()->orderBy('name')->limit(50)->get(['id','name']);
        }

        return view('livewire.chat.chat-panel', [
            'conversations' => $this->conversations,
            'activeConversation' => $this->activeConversation,
            'staffOptions' => $staffOptions,
            'customerOptions' => $customerOptions,
        ]);
    }

    private function userIsAdmin(User $user): bool
    {
        return (method_exists($user, 'hasRole') && $user->hasRole('admin'))
            || (property_exists($user, 'role') && $user->role === 'admin');
    }

    private function isGuest(): bool
    {
        return !auth()->check() && !auth('customer')->check();
    }

    private function ensureGuestKey(): void
    {
        if (!session()->has('chat_guest_key')) {
            session()->put('chat_guest_key', (string) Str::uuid());
        }
    }
}
