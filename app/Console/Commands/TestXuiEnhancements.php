<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Server;
use App\Models\ServerPlan;
use App\Models\ServerInbound;
use App\Models\ServerClient;
use App\Services\ClientProvisioningService;
use App\Services\ClientLifecycleService;

class TestXuiEnhancements extends Command
{
    protected $signature = 'xui:test-enhancements {--operation=all}';
    protected $description = 'Test the enhanced XUI models and services';

    public function handle()
    {
        $operation = $this->option('operation');

        $this->info("🚀 Testing XUI Enhancements - Operation: {$operation}");

        switch ($operation) {
            case 'models':
                $this->testModels();
                break;
            case 'capacity':
                $this->testCapacity();
                break;
            case 'lifecycle':
                $this->testLifecycle();
                break;
            case 'provisioning':
                $this->testProvisioning();
                break;
            case 'all':
            default:
                $this->testModels();
                $this->testCapacity();
                $this->testLifecycle();
                break;
        }

        $this->info("✅ XUI Enhancements testing completed!");
    }

    protected function testModels()
    {
        $this->info("\n📊 Testing Enhanced Models...");

        // Test Server model enhancements
        $servers = Server::with(['inbounds', 'plans'])->get();
        foreach ($servers as $server) {
            $this->line("Server: {$server->name}");
            $this->line("  - Health Status: " . ($server->health_status ?? 'unknown'));
            $this->line("  - Can Provision: " . ($server->canProvision() ? 'Yes' : 'No'));
            $this->line("  - Total Capacity: " . ($server->getTotalAvailableCapacity() ?? 'Unlimited'));
            $this->line("  - Default Inbound: " . ($server->getDefaultInbound()?->port ?? 'None'));
        }

        // Test ServerPlan model enhancements
        $plans = ServerPlan::with(['server', 'preferredInbound'])->get();
        foreach ($plans as $plan) {
            $this->line("Plan: {$plan->name}");
            $this->line("  - Available: " . ($plan->isAvailable() ? 'Yes' : 'No'));
            $this->line("  - Capacity: {$plan->current_clients}/{$plan->max_clients}");
            $this->line("  - Best Inbound: " . ($plan->getBestInbound()?->port ?? 'None'));
        }

        // Test ServerInbound model enhancements
        $inbounds = ServerInbound::with('server')->get();
        foreach ($inbounds as $inbound) {
            $this->line("Inbound: Port {$inbound->port}");
            $this->line("  - Can Provision: " . ($inbound->canProvision() ? 'Yes' : 'No'));
            $this->line("  - Utilization: " . round($inbound->getCapacityUtilization(), 2) . '%');
            $this->line("  - Available: " . ($inbound->getAvailableCapacity() ?? 'Unlimited'));
        }
    }

    protected function testCapacity()
    {
        $this->info("\n📈 Testing Capacity Management...");

        $plans = ServerPlan::where('is_active', true)->get();
        foreach ($plans as $plan) {
            $this->line("Testing capacity for plan: {$plan->name}");

            for ($quantity = 1; $quantity <= 5; $quantity++) {
                $hasCapacity = $plan->hasCapacity($quantity);
                $this->line("  - Quantity {$quantity}: " . ($hasCapacity ? 'Available' : 'Not Available'));
            }

            // Test best inbound selection
            $bestInbound = $plan->getBestInbound();
            if ($bestInbound) {
                $this->line("  - Best Inbound: Port {$bestInbound->port} (Utilization: " . round($bestInbound->getCapacityUtilization(), 2) . '%)');
            } else {
                $this->line("  - Best Inbound: None available");
            }
        }
    }

    protected function testLifecycle()
    {
        $this->info("\n🔄 Testing Client Lifecycle...");

        $lifecycleService = app(ClientLifecycleService::class);

        // Get statistics
        $stats = $lifecycleService->getClientStatistics();
        $this->line("Client Statistics:");
        foreach ($stats as $key => $value) {
            $this->line("  - " . ucwords(str_replace('_', ' ', $key)) . ": {$value}");
        }

        // Test client operations
        $clients = ServerClient::with(['plan', 'customer', 'order'])->limit(3)->get();
        foreach ($clients as $client) {
            $this->line("\nClient: {$client->email}");
            $this->line("  - Status: {$client->status}");
            $this->line("  - Expired: " . ($client->isExpired() ? 'Yes' : 'No'));
            $this->line("  - Near Expiration: " . ($client->isNearExpiration() ? 'Yes' : 'No'));
            $this->line("  - Traffic Exceeded: " . ($client->isTrafficLimitExceeded() ? 'Yes' : 'No'));

            if ($client->traffic_limit_mb) {
                $this->line("  - Traffic Usage: {$client->traffic_used_mb}MB / {$client->traffic_limit_mb}MB ({$client->traffic_percentage_used}%)");
            }
        }
    }

    protected function testProvisioning()
    {
        $this->info("\n🛠️ Testing Provisioning Service...");

        $provisioningService = app(ClientProvisioningService::class);

        // Test pre-provision checks
        $plans = ServerPlan::where('is_active', true)->limit(3)->get();
        foreach ($plans as $plan) {
            $this->line("Testing provisioning for plan: {$plan->name}");

            // Test capacity checks
            for ($quantity = 1; $quantity <= 3; $quantity++) {
                $canProvision = $plan->hasCapacity($quantity) &&
                               $plan->server->canProvision($quantity) &&
                               $plan->isAvailable();

                $this->line("  - Quantity {$quantity}: " . ($canProvision ? 'Can Provision' : 'Cannot Provision'));
            }

            // Test inbound selection
            $bestInbound = $plan->getBestInbound();
            if ($bestInbound) {
                $this->line("  - Selected Inbound: Port {$bestInbound->port} (Status: {$bestInbound->status})");
            } else {
                $this->line("  - Selected Inbound: None available");
            }
        }

        $this->info("\n⚠️ Note: Actual provisioning test requires valid XUI credentials and should be run carefully in development environment.");
    }
}
