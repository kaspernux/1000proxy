<?php

namespace Database\Seeders;

use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\Customer;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Seeder;

class ChatDemoSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first();
        $customer = Customer::first();
        if (!$user || !$customer) return;

        $conv = Conversation::firstOrCreate([
            'type' => 'direct',
            'privacy' => 'private',
            'created_by_type' => User::class,
            'created_by_id' => $user->id,
        ]);

        ConversationParticipant::firstOrCreate([
            'conversation_id' => $conv->id,
            'participant_type' => User::class,
            'participant_id' => $user->id,
        ], ['role' => 'owner','can_post' => true,'can_edit' => true,'can_delete' => true,'joined_at' => now()]);
        ConversationParticipant::firstOrCreate([
            'conversation_id' => $conv->id,
            'participant_type' => Customer::class,
            'participant_id' => $customer->id,
        ], ['role' => 'member','can_post' => true,'can_edit' => true,'can_delete' => false,'joined_at' => now()]);

        if ($conv->messages()->count() === 0) {
            Message::create([
                'conversation_id' => $conv->id,
                'sender_type' => User::class,
                'sender_id' => $user->id,
                'body' => 'Welcome to support chat! How can we help?',
            ]);
        }
    }
}
