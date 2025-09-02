<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Models\{Server, ServerPlan, Order, OrderItem, Customer};
use App\Services\{XUIService, ClientProvisioningService};

class ProvisionAllServerPlans extends Command
{
    protected $signature = 'orders:provision-all-plans
        {--server-id= : Target server ID}
        {--host= : Target server host}
        {--port= : Panel port}
        {--email=demo@1000proxy.io : Customer email to use}
        {--amount=1000 : Wallet balance to ensure before ordering}
        {--insecure : Disable TLS verification}
        {--json : Output JSON summary}
    ';

    protected $description = 'Fund wallet and place real wallet-paid orders for ALL plans on a server, then provision via X-UI.';

    public function handle(): int
    {
        $serverId = $this->option('server-id');
        $host = $this->option('host');
        $port = $this->option('port');
        $email = (string)$this->option('email');
        $ensureAmount = (float)$this->option('amount');
        $insecure = (bool)$this->option('insecure');
        $asJson = (bool)$this->option('json');

        // Resolve server
        $server = null;
        if ($serverId) {
            $server = Server::find($serverId);
        } elseif ($host) {
            $q = Server::where('host', $host);
            if ($port) { $q->where('panel_port', (int)$port); }
            $server = $q->first();
        }
        if (!$server) {
            $this->error('Server not found. Provide --server-id or --host [--port].');
            return 1;
        }

        // Login to XUI
        if ($insecure) { app()->instance('xui.insecure', true); $this->warn('Insecure TLS mode ON'); }
        $xui = new XUIService($server);
        if (!$xui->testConnection()) {
            $this->error('Login to X-UI failed. Aborting.');
            return 1;
        }
        $synced = $xui->syncAllInbounds();
        $this->line("Synced {$synced} inbounds");

        // Locate or create customer and ensure wallet balance
        $customer = Customer::firstWhere('email', $email);
        if (!$customer) {
            $this->warn("Customer {$email} not found. Creating...");
            $customer = Customer::create([
                'name' => 'Provision Bot',
                'email' => $email,
                'password' => bcrypt(str()->random(16)),
                'email_verified_at' => now(),
                'is_active' => true,
            ]);
        }
        $wallet = $customer->getWallet();
        $current = (float)$wallet->balance;
        if ($current < $ensureAmount) {
            $delta = $ensureAmount - $current;
            $customer->addToWallet($delta, 'E2E Provision Test Funding');
            $wallet->refresh();
        }
        $this->info("Wallet ready: $" . number_format((float)$wallet->balance, 2));

        // Fetch all plans for server
        $plans = ServerPlan::where('server_id', $server->id)->where('is_active', true)->get();
        if ($plans->isEmpty()) {
            $this->warn('No plans found for this server. Seed first.');
            return 1;
        }
        $this->line("Plans to order: {$plans->count()}");

        $provisioner = app()->makeWith(ClientProvisioningService::class, ['xuiService' => new XUIService($server)]);
        $summary = [];
        $failures = 0; $success = 0;

        foreach ($plans as $plan) {
            $cost = (float) ($plan->getTotalPrice());
            // Ensure balance per plan
            if ($customer->getWallet()->balance < $cost) {
                $customer->addToWallet($cost * 2, 'Auto top-up for plan purchase');
            }

            $order = $this->createPaidOrder($customer, $plan);
            if (!$order) {
                $failures++;
                $summary[] = [
                    'plan' => $plan->name,
                    'status' => 'order_failed',
                ];
                continue;
            }

            try {
                $result = $provisioner->provisionOrder($order->fresh('items.serverPlan'));
                $success++;
                $summary[] = [
                    'plan' => $plan->name,
                    'order_id' => $order->id,
                    'status' => 'provisioned',
                    'items' => array_values($result),
                ];
            } catch (\Throwable $e) {
                Log::error('Provision error', ['plan_id' => $plan->id, 'error' => $e->getMessage()]);
                $failures++;
                $summary[] = [
                    'plan' => $plan->name,
                    'order_id' => $order->id,
                    'status' => 'provision_error',
                    'error' => $e->getMessage(),
                ];
            }
        }

        if ($asJson) {
            $this->line(json_encode(['success' => $success, 'failures' => $failures, 'summary' => $summary], JSON_PRETTY_PRINT));
        } else {
            $this->info("Completed. Success: {$success}, Failures: {$failures}");
        }

        return $failures > 0 ? 2 : 0;
    }

    protected function createPaidOrder(Customer $customer, ServerPlan $plan): ?Order
    {
        $cost = (float)$plan->getTotalPrice();
        if ($customer->getWallet()->balance < $cost) {
            $this->error("Insufficient wallet for plan {$plan->name} (need {$cost})");
            return null;
        }
        $customer->payFromWallet($cost, 'Provision All Plans Purchase');
        $order = Order::create([
            'customer_id' => $customer->id,
            'grand_amount' => $cost,
            'currency' => 'USD',
            'payment_status' => 'paid',
            'order_status' => 'new',
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'server_plan_id' => $plan->id,
            'server_id' => $plan->server_id,
            'quantity' => 1,
            'total_amount' => $cost,
            'unit_amount' => $plan->price,
        ]);
        return $order->fresh('items.serverPlan');
    }
}
