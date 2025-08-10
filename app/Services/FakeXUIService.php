<?php

namespace App\Services;

use App\Models\Server;
use Illuminate\Support\Str;

/**
 * Lightweight fake of XUIService used in the test environment.
 * Provides just enough behaviour for provisioning tests to pass
 * without performing any HTTP calls.
 */
class FakeXUIService
{
    private Server $server;

    /** @var array<int,array> */
    private static array $inbounds = [];

    /** @var array<int,array<int,string>> Maps inbound ID => client UUIDs */
    private static array $inboundClients = [];

    public function __construct(Server $server)
    {
        $this->server = $server;
    }

    public function generateUID(): string
    {
        return Str::uuid()->toString();
    }

    /**
     * Simulate adding a client to an inbound. Always succeeds.
     */
    public function addClient(int $inboundId, string $clientSettings): bool
    {
        // Decode provided settings to extract client id/subId for simple bookkeeping (optional)
        $decoded = json_decode($clientSettings, true);
        $clients = $decoded['clients'] ?? [];
        foreach ($clients as $c) {
            $id = $c['id'] ?? Str::uuid()->toString();
            self::$inboundClients[$inboundId][] = $id;
        }
        return true;
    }

    /**
     * Simulate remote inbound creation; returns structure similar to real API response.
     */
    public function createInbound(array $inboundData): array
    {
        $id = random_int(1000, 999999);
        $record = array_merge([
            'id' => $id,
            'up' => 0,
            'down' => 0,
            'total' => 0,
            'enable' => $inboundData['enable'] ?? true,
            'expiry_time' => $inboundData['expiry_time'] ?? 0,
            'settings' => $inboundData['settings'] ?? json_encode(['clients' => []]),
            'streamSettings' => $inboundData['streamSettings'] ?? json_encode([]),
            'sniffing' => $inboundData['sniffing'] ?? json_encode([]),
            'allocate' => $inboundData['allocate'] ?? json_encode([]),
        ], $inboundData);

        self::$inbounds[$id] = $record;
        self::$inboundClients[$id] = [];

        return $record;
    }
}
