<?php

namespace App\Console\Commands;

use App\Models\Server;
use App\Services\XUIService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestXUIService extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:xui {server_id?} {--all} {--detailed}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test 3X-UI API connectivity and functionality';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Starting 3X-UI API Integration Test');
        $this->newLine();

        if ($this->option('all')) {
            return $this->testAllServers();
        }

        $serverId = $this->argument('server_id') ?? $this->askForServer();

        if (!$serverId) {
            $this->error('No server selected. Aborting test.');
            return 1;
        }

        return $this->testServer($serverId);
    }

    /**
     * Ask user to select a server for testing
     */
    private function askForServer(): ?int
    {
        $servers = Server::select('id', 'name', 'host', 'port')->take(10)->get();

        if ($servers->count() === 0) {
            $this->error('No servers found in database. Please run seeder first.');
            return null;
        }

        $this->info('Available servers:');
        foreach ($servers as $server) {
            $this->line("  {$server->id}: {$server->name} ({$server->host}:{$server->port})");
        }

        $serverId = $this->ask('Enter server ID to test');

        if (!is_numeric($serverId) || !$servers->where('id', $serverId)->first()) {
            $this->error('Invalid server ID');
            return null;
        }

        return (int) $serverId;
    }

    /**
     * Test all available servers
     */
    private function testAllServers(): int
    {
        $servers = Server::take(5)->get(); // Test first 5 servers only
        $results = [];

        foreach ($servers as $server) {
            $this->info("Testing server: {$server->name}");
            $result = $this->performServerTest($server);
            $results[] = $result;
            $this->newLine();
        }

        $this->displaySummary($results);
        return 0;
    }

    /**
     * Test a specific server
     */
    private function testServer(int $serverId): int
    {
        $server = Server::find($serverId);

        if (!$server) {
            $this->error("Server with ID {$serverId} not found");
            return 1;
        }

        $this->info("Testing server: {$server->name}");
        $result = $this->performServerTest($server);

        $this->displayTestResult($result);
        return $result['success'] ? 0 : 1;
    }

    /**
     * Perform comprehensive server testing
     */
    private function performServerTest(Server $server): array
    {
        $result = [
            'server' => $server,
            'tests' => [],
            'success' => false,
            'total_tests' => 0,
            'passed_tests' => 0
        ];

        try {
            $xuiService = new XUIService($server);

            // Test 1: Authentication
            $result['tests']['authentication'] = $this->testAuthentication($xuiService);
            $result['total_tests']++;
            if ($result['tests']['authentication']['success']) {
                $result['passed_tests']++;
            }

            // Test 2: List Inbounds (if authentication successful)
            if ($result['tests']['authentication']['success']) {
                $result['tests']['list_inbounds'] = $this->testListInbounds($xuiService);
                $result['total_tests']++;
                if ($result['tests']['list_inbounds']['success']) {
                    $result['passed_tests']++;
                }

                // Test 3: Get Online Clients
                $result['tests']['online_clients'] = $this->testOnlineClients($xuiService);
                $result['total_tests']++;
                if ($result['tests']['online_clients']['success']) {
                    $result['passed_tests']++;
                }
            }

            $result['success'] = $result['passed_tests'] > 0;

        } catch (\Exception $e) {
            $result['error'] = $e->getMessage();
            if ($this->option('detailed')) {
                $this->error("Exception: " . $e->getMessage());
            }
        }

        return $result;
    }

    /**
     * Test authentication with 3X-UI panel
     */
    private function testAuthentication(XUIService $xuiService): array
    {
        $this->line('  🔐 Testing authentication...');

        try {
            $success = $xuiService->login();

            if ($success) {
                $this->line('    ✅ Authentication successful');
                return ['success' => true, 'message' => 'Login successful'];
            } else {
                $this->line('    ❌ Authentication failed');
                return ['success' => false, 'message' => 'Login failed'];
            }
        } catch (\Exception $e) {
            $this->line('    ❌ Authentication error: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Test listing inbounds
     */
    private function testListInbounds(XUIService $xuiService): array
    {
        $this->line('  📋 Testing inbound listing...');

        try {
            $inbounds = $xuiService->listInbounds();

            if (isset($inbounds['success']) && $inbounds['success']) {
                $count = count($inbounds['obj'] ?? []);
                $this->line("    ✅ Found {$count} inbounds");
                return ['success' => true, 'message' => "Found {$count} inbounds", 'count' => $count];
            } else {
                $message = $inbounds['msg'] ?? 'Failed to list inbounds';
                $this->line("    ❌ {$message}");
                return ['success' => false, 'message' => $message];
            }
        } catch (\Exception $e) {
            $this->line('    ❌ Inbound listing error: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Test getting online clients
     */
    private function testOnlineClients(XUIService $xuiService): array
    {
        $this->line('  👥 Testing online clients...');

        try {
            $clients = $xuiService->getOnlineClients();

            if (isset($clients['success']) && $clients['success']) {
                $count = count($clients['obj'] ?? []);
                $this->line("    ✅ Found {$count} online clients");
                return ['success' => true, 'message' => "Found {$count} online clients", 'count' => $count];
            } else {
                $message = $clients['msg'] ?? 'Failed to get online clients';
                $this->line("    ❌ {$message}");
                return ['success' => false, 'message' => $message];
            }
        } catch (\Exception $e) {
            $this->line('    ❌ Online clients error: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Display test result for single server
     */
    private function displayTestResult(array $result): void
    {
        $this->newLine();
        $this->info("📊 Test Results for: {$result['server']->name}");
        $this->line("Host: {$result['server']->host}:{$result['server']->port}");
        $this->line("Tests passed: {$result['passed_tests']}/{$result['total_tests']}");

        if ($result['success']) {
            $this->info('✅ Overall Status: PASS');
        } else {
            $this->error('❌ Overall Status: FAIL');
        }

        if (isset($result['error'])) {
            $this->error("Error: {$result['error']}");
        }
    }

    /**
     * Display summary for multiple servers
     */
    private function displaySummary(array $results): void
    {
        $this->newLine();
        $this->info('📊 Test Summary');
        $this->line(str_repeat('=', 50));

        $totalServers = count($results);
        $successfulServers = 0;

        foreach ($results as $result) {
            $status = $result['success'] ? '✅' : '❌';
            $this->line("{$status} {$result['server']->name} ({$result['passed_tests']}/{$result['total_tests']})");

            if ($result['success']) {
                $successfulServers++;
            }
        }

        $this->newLine();
        $this->info("Servers tested: {$totalServers}");
        $this->info("Successful: {$successfulServers}");
        $this->info("Failed: " . ($totalServers - $successfulServers));

        if ($successfulServers === $totalServers) {
            $this->info('🎉 All servers passed testing!');
        } elseif ($successfulServers > 0) {
            $this->warn('⚠️  Some servers failed testing');
        } else {
            $this->error('💥 All servers failed testing');
        }
    }
}
