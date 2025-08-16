<?php

namespace Tests\Fakes;

use App\Services\XUIService;
use App\Models\Server;
use Illuminate\Support\Str;

class FakeXUIService extends XUIService
{
    public array $createdInbounds = [];
    public array $addedClients = [];

    public function __construct(Server $server)
    {
        // Directly assign without network logic
        $this->server = $server;
        $this->timeout = 1;
        $this->retryCount = 1;
    }

    public function createInbound(array $inboundData): array
    {
        $id = count($this->createdInbounds) + 1000; // fake remote id
        $record = array_merge($inboundData, ['id' => $id]);
        $this->createdInbounds[] = $record;
        return $record;
    }

    public function addClient(int $inboundId, string $clientSettings): array
    {
        $this->addedClients[] = [
            'inbound_id' => $inboundId,
            'settings' => $clientSettings,
        ];
        return [
            'success' => true,
            'obj' => [
                'id' => $inboundId,
                'settings' => $clientSettings,
            ],
        ];
    }

    public function generateUID(): string
    {
        return (string) Str::uuid();
    }
}
