<?php

namespace App\Events;

use App\Models\ServerClient;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ClientStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public ServerClient $client;
    public bool $oldStatus;
    public bool $newStatus;

    /**
     * Create a new event instance.
     */
    public function __construct(ServerClient $client, bool $oldStatus, bool $newStatus)
    {
        $this->client = $client;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->client->user_id),
            new PrivateChannel('client.' . $this->client->id),
        ];
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'client_id' => $this->client->id,
            'old_status' => $this->oldStatus,
            'new_status' => $this->newStatus,
            'client' => $this->client->toArray(),
            'server_name' => $this->client->server->name,
            'timestamp' => now()->toISOString(),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'client.status.changed';
    }
}
