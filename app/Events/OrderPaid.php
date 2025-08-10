<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderPaid implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Order $order) {}

    public function broadcastOn(): array
    {
        return [new Channel('orders')];
    }

    public function broadcastAs(): string
    {
        return 'order.paid';
    }

    public function broadcastWith(): array
    {
        // Guard against partially missing attributes if model was just updated
        $order = $this->order;
        return [
            'id' => $order->id,
            // Use null coalesce to avoid accidental attribute access errors
            'amount' => $order->amount ?? $order->grand_amount ?? null,
            'currency' => $order->currency ?? null,
            'payment_status' => $order->payment_status ?? null,
            'created_at' => optional($order->created_at)->toIso8601String(),
        ];
    }
}
