<?php

namespace App\Events;

use App\Models\User;
use App\Models\Customer;
use App\Models\WalletTransaction;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WalletBalanceChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Customer $user;
    public WalletTransaction $transaction;
    public float $newBalance;

    /**
     * Create a new event instance.
     */
    public function __construct(Customer $user, WalletTransaction $transaction, float $newBalance)
    {
        $this->user = $user;
        $this->transaction = $transaction;
        $this->newBalance = $newBalance;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('customer.' . $this->user->id),
            new PrivateChannel('wallet.' . $this->user->id),
        ];
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'customer_id' => $this->user->id,
            'transaction_id' => $this->transaction->id,
            'transaction_type' => $this->transaction->type,
            'transaction_amount' => $this->transaction->amount,
            'new_balance' => $this->newBalance,
            'description' => $this->transaction->description,
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'wallet.balance.changed';
    }
}
