<?php

namespace App\Events;

use App\Models\Server;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ServerStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Server $server, public array $metrics = []) {}

    public function broadcastOn(): array
    {
        return [new Channel('servers')];
    }

    public function broadcastAs(): string
    {
        return 'server.status';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->server->id,
            'status' => $this->server->status,
            'metrics' => $this->metrics,
        ];
    }
}
