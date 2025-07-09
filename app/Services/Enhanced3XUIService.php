<?php

namespace App\Services;

use App\Models\Server;
use App\Models\ServerInbound;
use App\Models\ServerClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Exception;

/**
 * Enhanced 3X-UI API Service
 * Provides complete wrapper for all 3X-UI API endpoints based on official Postman collection
 */
class Enhanced3XUIService
{
    private Server $server;
    private int $timeout;
    private int $retryCount;

    public function __construct(Server $server)
    {
        $this->server = $server;
        $this->timeout = $server->getApiTimeout();
        $this->retryCount = $server->getApiRetryCount();
    }

    /**
     * Authenticate with 3X-UI panel and store session
     */
    public function login(): bool
    {
        try {
            if ($this->server->isLoginLocked()) {
                Log::warning("Login locked for server {$this->server->name} due to too many failed attempts");
                return false;
            }

            $response = Http::timeout($this->timeout)
                ->asForm()
                ->post($this->server->getApiEndpoint('login'), [
                    'username' => $this->server->username,
                    'password' => $this->server->password,
                ]);

            if ($response->successful()) {
                $data = $response->json();

                if ($data['success'] ?? false) {
                    // Extract session cookie from response
                    $sessionCookie = $this->extractSessionCookie($response);

                    if ($sessionCookie) {
                        $this->server->updateSession($sessionCookie, 60); // 1 hour session
                        Log::info("Successfully logged in to 3X-UI server: {$this->server->name}");
                        return true;
                    }
                }

                Log::error("Login failed for server {$this->server->name}: " . ($data['msg'] ?? 'Unknown error'));
            } else {
                Log::error("Login HTTP error for server {$this->server->name}: " . $response->status());
            }
        } catch (Exception $e) {
            Log::error("Login exception for server {$this->server->name}: " . $e->getMessage());
        }

        $this->server->incrementLoginAttempts();
        return false;
    }

    /**
     * Ensure we have a valid session, login if needed
     */
    private function ensureValidSession(): bool
    {
        if (!$this->server->hasValidSession()) {
            return $this->login();
        }
        return true;
    }

    /**
     * Make authenticated API request with retry logic
     */
    private function makeAuthenticatedRequest(string $method, string $endpoint, array $data = []): array
    {
        $attempt = 0;
        $lastException = null;

        while ($attempt < $this->retryCount) {
            try {
                if (!$this->ensureValidSession()) {
                    throw new Exception("Failed to authenticate with 3X-UI server");
                }

                $request = Http::timeout($this->timeout)
                    ->withHeaders($this->server->getSessionHeader());

                if ($method === 'POST') {
                    $response = $request->asJson()->post($this->server->getApiEndpoint($endpoint), $data);
                } else {
                    $response = $request->get($this->server->getApiEndpoint($endpoint));
                }

                if (!$response->successful()) {
                    throw new Exception("API request failed with status: " . $response->status());
                }

                $result = $response->json();

                if (!($result['success'] ?? false)) {
                    throw new Exception("API request failed: " . ($result['msg'] ?? 'Unknown error'));
                }

                return $result;
            } catch (Exception $e) {
                $lastException = $e;
                $attempt++;

                if ($attempt < $this->retryCount) {
                    Log::warning("API request failed, retrying ({$attempt}/{$this->retryCount}): " . $e->getMessage());
                    sleep(1); // Wait before retry
                }
            }
        }

        throw $lastException ?? new Exception("API request failed after {$this->retryCount} attempts");
    }

    /**
     * Extract session cookie from response
     */
    private function extractSessionCookie($response): ?string
    {
        $cookies = $response->headers()['Set-Cookie'] ?? $response->headers()['set-cookie'] ?? [];

        foreach ($cookies as $cookie) {
            if (is_string($cookie) && strpos($cookie, 'session=') !== false) {
                $parts = explode(';', $cookie);
                $sessionPart = trim($parts[0]);
                if (strpos($sessionPart, 'session=') === 0) {
                    return substr($sessionPart, 8); // Remove "session=" prefix
                }
            }
        }

        return null;
    }

    // === INBOUND MANAGEMENT METHODS ===

    /**
     * Get all inbounds with client statistics
     */
    public function listInbounds(): array
    {
        $result = $this->makeAuthenticatedRequest('GET', 'panel/api/inbounds/list');
        return $result['obj'] ?? [];
    }

    /**
     * Get specific inbound by ID
     */
    public function getInbound(int $inboundId): array
    {
        $result = $this->makeAuthenticatedRequest('GET', "panel/api/inbounds/get/{$inboundId}");
        return $result['obj'] ?? [];
    }

    /**
     * Create new inbound
     */
    public function createInbound(array $inboundData): array
    {
        $result = $this->makeAuthenticatedRequest('POST', 'panel/api/inbounds/add', $inboundData);
        return $result['obj'] ?? [];
    }

    /**
     * Update existing inbound
     */
    public function updateInbound(int $inboundId, array $inboundData): array
    {
        $result = $this->makeAuthenticatedRequest('POST', "panel/api/inbounds/update/{$inboundId}", $inboundData);
        return $result['obj'] ?? [];
    }

    /**
     * Delete inbound
     */
    public function deleteInbound(int $inboundId): bool
    {
        try {
            $result = $this->makeAuthenticatedRequest('POST', "panel/api/inbounds/del/{$inboundId}");
            return $result['success'] ?? false;
        } catch (Exception $e) {
            Log::error("Failed to delete inbound {$inboundId}: " . $e->getMessage());
            return false;
        }
    }

    // === CLIENT MANAGEMENT METHODS ===

    /**
     * Add client to inbound
     */
    public function addClient(int $inboundId, string $clientSettings): bool
    {
        try {
            $result = $this->makeAuthenticatedRequest('POST', 'panel/api/inbounds/addClient', [
                'id' => $inboundId,
                'settings' => $clientSettings,
            ]);
            return $result['success'] ?? false;
        } catch (Exception $e) {
            Log::error("Failed to add client to inbound {$inboundId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update client by UUID
     */
    public function updateClient(string $clientUuid, int $inboundId, string $clientSettings): bool
    {
        try {
            $result = $this->makeAuthenticatedRequest('POST', "panel/api/inbounds/updateClient/{$clientUuid}", [
                'id' => $inboundId,
                'settings' => $clientSettings,
            ]);
            return $result['success'] ?? false;
        } catch (Exception $e) {
            Log::error("Failed to update client {$clientUuid}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete client from inbound
     */
    public function deleteClient(int $inboundId, string $clientUuid): bool
    {
        try {
            $result = $this->makeAuthenticatedRequest('POST', "panel/api/inbounds/{$inboundId}/delClient/{$clientUuid}");
            return $result['success'] ?? false;
        } catch (Exception $e) {
            Log::error("Failed to delete client {$clientUuid} from inbound {$inboundId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get client by email
     */
    public function getClientByEmail(string $email): ?array
    {
        try {
            $result = $this->makeAuthenticatedRequest('GET', "panel/api/inbounds/getClientTraffics/{$email}");
            return $result['obj'] ?? null;
        } catch (Exception $e) {
            Log::error("Failed to get client by email {$email}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get client by UUID
     */
    public function getClientByUuid(string $uuid): ?array
    {
        try {
            $result = $this->makeAuthenticatedRequest('GET', "panel/api/inbounds/getClientTrafficsById/{$uuid}");
            return $result['obj'] ?? null;
        } catch (Exception $e) {
            Log::error("Failed to get client by UUID {$uuid}: " . $e->getMessage());
            return null;
        }
    }

    // === CLIENT IP MANAGEMENT ===

    /**
     * Get client IP addresses
     */
    public function getClientIps(string $email): array
    {
        try {
            $result = $this->makeAuthenticatedRequest('POST', "panel/api/inbounds/clientIps/{$email}");
            $ips = $result['obj'] ?? [];
            return is_array($ips) ? $ips : [];
        } catch (Exception $e) {
            Log::error("Failed to get client IPs for {$email}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Clear client IP addresses
     */
    public function clearClientIps(string $email): bool
    {
        try {
            $result = $this->makeAuthenticatedRequest('POST', "panel/api/inbounds/clearClientIps/{$email}");
            return $result['success'] ?? false;
        } catch (Exception $e) {
            Log::error("Failed to clear client IPs for {$email}: " . $e->getMessage());
            return false;
        }
    }

    // === TRAFFIC MANAGEMENT ===

    /**
     * Reset client traffic
     */
    public function resetClientTraffic(int $inboundId, string $email): bool
    {
        try {
            $result = $this->makeAuthenticatedRequest('POST', "panel/api/inbounds/{$inboundId}/resetClientTraffic/{$email}");
            return $result['success'] ?? false;
        } catch (Exception $e) {
            Log::error("Failed to reset client traffic for {$email}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Reset all client traffic in an inbound
     */
    public function resetAllClientTraffics(int $inboundId): bool
    {
        try {
            $result = $this->makeAuthenticatedRequest('POST', "panel/api/inbounds/resetAllClientTraffics/{$inboundId}");
            return $result['success'] ?? false;
        } catch (Exception $e) {
            Log::error("Failed to reset all client traffic for inbound {$inboundId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Reset all traffic statistics
     */
    public function resetAllTraffics(): bool
    {
        try {
            $result = $this->makeAuthenticatedRequest('POST', 'panel/api/inbounds/resetAllTraffics');
            return $result['success'] ?? false;
        } catch (Exception $e) {
            Log::error("Failed to reset all traffic: " . $e->getMessage());
            return false;
        }
    }

    // === MONITORING & UTILITIES ===

    /**
     * Get online clients
     */
    public function getOnlineClients(): array
    {
        try {
            $result = $this->makeAuthenticatedRequest('POST', 'panel/api/inbounds/onlines');
            return $result['obj'] ?? [];
        } catch (Exception $e) {
            Log::error("Failed to get online clients: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Delete depleted clients from inbound
     */
    public function deleteDepletedClients(int $inboundId): bool
    {
        try {
            $result = $this->makeAuthenticatedRequest('POST', "panel/api/inbounds/delDepletedClients/{$inboundId}");
            return $result['success'] ?? false;
        } catch (Exception $e) {
            Log::error("Failed to delete depleted clients from inbound {$inboundId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Create backup (sends to Telegram if configured)
     */
    public function createBackup(): bool
    {
        try {
            $result = $this->makeAuthenticatedRequest('GET', 'panel/api/inbounds/createbackup');
            return $result['success'] ?? false;
        } catch (Exception $e) {
            Log::error("Failed to create backup: " . $e->getMessage());
            return false;
        }
    }

    // === SYNCHRONIZATION METHODS ===

    /**
     * Sync all inbounds from 3X-UI to local database
     */
    public function syncAllInbounds(): int
    {
        try {
            $inbounds = $this->listInbounds();
            $syncedCount = 0;

            foreach ($inbounds as $inboundData) {
                $this->syncInbound($inboundData);
                $syncedCount++;
            }

            // Update server global statistics
            $this->server->update([
                'total_inbounds' => count($inbounds),
                'active_inbounds' => count(array_filter($inbounds, fn($i) => $i['enable'] ?? false)),
                'last_global_sync_at' => now(),
            ]);

            Log::info("Synced {$syncedCount} inbounds for server {$this->server->name}");
            return $syncedCount;
        } catch (Exception $e) {
            Log::error("Failed to sync all inbounds: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Sync specific inbound from 3X-UI to local database
     */
    public function syncInbound(array $inboundData): ?ServerInbound
    {
        try {
            // Find existing inbound by remote_id or create new one
            $inbound = $this->server->inbounds()->where('remote_id', $inboundData['id'])->first();

            if (!$inbound) {
                $inbound = new ServerInbound([
                    'server_id' => $this->server->id,
                    'protocol' => $inboundData['protocol'] ?? 'vless',
                    'port' => $inboundData['port'] ?? 0,
                ]);
            }

            // Update inbound with 3X-UI data
            $inbound->updateFromXuiApiData($inboundData);
            $inbound->save();

            Log::info("Synced inbound {$inbound->id} (remote: {$inboundData['id']}) from 3X-UI");
            return $inbound;
        } catch (Exception $e) {
            Log::error("Failed to sync inbound: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Sync all clients from 3X-UI to local database
     */
    public function syncAllClients(): int
    {
        try {
            $inbounds = $this->listInbounds();
            $syncedCount = 0;

            foreach ($inbounds as $inboundData) {
                if (isset($inboundData['clientStats']) && is_array($inboundData['clientStats'])) {
                    foreach ($inboundData['clientStats'] as $clientStats) {
                        $this->syncClient($clientStats);
                        $syncedCount++;
                    }
                }
            }

            Log::info("Synced {$syncedCount} clients for server {$this->server->name}");
            return $syncedCount;
        } catch (Exception $e) {
            Log::error("Failed to sync all clients: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Sync specific client from 3X-UI to local database
     */
    public function syncClient(array $clientStats): ?ServerClient
    {
        try {
            // Find existing client by email or remote_client_id
            $client = ServerClient::where('email', $clientStats['email'])->first();

            if (!$client) {
                $client = ServerClient::where('remote_client_id', $clientStats['id'])->first();
            }

            if ($client) {
                $client->updateFromXuiApiClientStats($clientStats);
                $client->save();
                Log::info("Synced client {$client->id} ({$clientStats['email']}) from 3X-UI");
            } else {
                Log::warning("Could not find local client for 3X-UI email: {$clientStats['email']}");
            }

            return $client;
        } catch (Exception $e) {
            Log::error("Failed to sync client: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Update online status for all clients
     */
    public function updateOnlineStatus(): int
    {
        try {
            $onlineEmails = $this->getOnlineClients();
            $updatedCount = 0;

            // Mark all clients as offline first
            ServerClient::where('is_online', true)->update(['is_online' => false]);

            // Mark online clients
            foreach ($onlineEmails as $email) {
                $client = ServerClient::where('email', $email)->first();
                if ($client) {
                    $client->update(['is_online' => true, 'last_online_check_at' => now()]);
                    $updatedCount++;
                }
            }

            // Update server total online clients
            $this->server->update([
                'total_online_clients' => count($onlineEmails),
            ]);

            Log::info("Updated online status for {$updatedCount} clients");
            return $updatedCount;
        } catch (Exception $e) {
            Log::error("Failed to update online status: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Perform full synchronization with 3X-UI
     */
    public function fullSync(): array
    {
        try {
            $results = [
                'inbounds_synced' => $this->syncAllInbounds(),
                'clients_synced' => $this->syncAllClients(),
                'online_status_updated' => $this->updateOnlineStatus(),
                'timestamp' => now()->toISOString(),
            ];

            Log::info("Full sync completed for server {$this->server->name}", $results);
            return $results;
        } catch (Exception $e) {
            Log::error("Full sync failed for server {$this->server->name}: " . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    // === HELPER METHODS ===

    /**
     * Test connection to 3X-UI server
     */
    public function testConnection(): bool
    {
        try {
            return $this->login();
        } catch (Exception $e) {
            Log::error("Connection test failed for server {$this->server->name}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get server health status
     */
    public function getHealthStatus(): array
    {
        $status = [
            'server_accessible' => false,
            'session_valid' => false,
            'api_responsive' => false,
            'last_check' => now()->toISOString(),
        ];

        try {
            // Test if we can login
            $status['server_accessible'] = $this->login();

            if ($status['server_accessible']) {
                $status['session_valid'] = $this->server->hasValidSession();

                // Test API responsiveness with a simple call
                $this->listInbounds();
                $status['api_responsive'] = true;
            }
        } catch (Exception $e) {
            Log::error("Health check failed for server {$this->server->name}: " . $e->getMessage());
        }

        return $status;
    }
}
