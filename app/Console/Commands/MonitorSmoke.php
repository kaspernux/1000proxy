<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use App\Models\Customer;
use App\Models\ServerClient;
use App\Filament\Customer\Pages\ProxyStatusMonitoring;

class MonitorSmoke extends Command
{
    protected $signature = 'monitor:smoke {customerId?} {--limit=5} {--email=} {--name=} {--find=}';
    protected $description = 'Simulate Proxy Status Monitoring page and print a brief summary for a customer';

    public function handle(): int
    {
        $customerId = $this->argument('customerId');

        // Resolve by email/name if provided
        if (!$customerId) {
            if ($email = $this->option('email')) {
                $customerId = Customer::where('email', $email)->value('id');
            }
        }
        if (!$customerId) {
            if ($name = $this->option('name')) {
                $customerId = Customer::where('name', $name)->value('id');
            }
        }
        if (!$customerId) {
            if ($q = $this->option('find')) {
                $customerId = Customer::where('email', 'like', "%{$q}%")
                    ->orWhere('name', 'like', "%{$q}%")
                    ->orderByDesc('id')
                    ->value('id');
            }
        }

        // Fallback: from active server clients by customer_id, else via orders
        if (!$customerId) {
            $customerId = ServerClient::where('status', 'active')
                ->whereNotNull('customer_id')
                ->orderByDesc('updated_at')
                ->value('customer_id');
        }
        if (!$customerId) {
            $customerId = \App\Models\Order::whereNotNull('customer_id')
                ->orderByDesc('id')
                ->value('customer_id');
        }

        if (!$customerId) {
            $this->warn('No customer with active proxies found.');
            return self::SUCCESS;
        }

        $customer = Customer::find($customerId);
        if (!$customer) {
            $this->error('Customer not found: ' . $customerId);
            return self::FAILURE;
        }

    Auth::shouldUse('customer');
    Auth::guard('customer')->setUser($customer);

    $page = app(ProxyStatusMonitoring::class);
    $page->mount();
    // Force refresh to bypass any stale cache
    $page->refreshSilently();

    $overall = $page->overallMetrics;
    $this->info('Overall: ' . json_encode($overall));

    $statuses = array_slice($page->proxyStatuses, 0, (int) $this->option('limit'));
        foreach ($statuses as $s) {
            $this->line(sprintf(
                '- %s | %s | %s | %d ms | %.1f%% | %s',
                $s['name'] ?? 'n/a',
                $s['status'] ?? 'n/a',
                $s['location'] ?? 'n/a',
                (int) ($s['latency'] ?? 0),
                (float) ($s['uptime'] ?? 0),
                $s['data_usage']['total'] ?? '0 B'
            ));
        }

        $this->info('History points: ' . count($page->performanceHistory));
        return self::SUCCESS;
    }
}
