<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Customer;
use App\Models\ServerClient;
use App\Models\Order;
use App\Services\CacheService;

class MonitorDebug extends Command
{
    protected $signature = 'monitor:debug {--find=} {--id=}';
    protected $description = 'Debug monitoring data sources for a customer (clients, orders, items)';

    public function handle(): int
    {
        $id = $this->option('id');
        $find = $this->option('find');

        $customers = collect();
        if ($id) {
            $c = Customer::find($id);
            if ($c) { $customers = collect([$c]); }
        } elseif ($find) {
            $customers = Customer::query()
                ->where('email', 'like', "%{$find}%")
                ->orWhere('name', 'like', "%{$find}%")
                ->orderByDesc('id')
                ->limit(5)
                ->get();
        } else {
            $this->error('Provide --id or --find');
            return self::INVALID;
        }

        if ($customers->isEmpty()) {
            $this->warn('No customers matched.');
            return self::SUCCESS;
        }

        $cache = new CacheService();
        foreach ($customers as $customer) {
            $this->line("Customer #{$customer->id} | {$customer->name} | {$customer->email}");
            $clients = $cache->getUserServerClients((int) $customer->id);
            $this->line('  Clients: ' . $clients->count());
            $this->line('   - with server: ' . $clients->filter(fn($c)=>!is_null($c->server_id))->count());
            $this->line('   - with inbound: ' . $clients->filter(fn($c)=>!is_null($c->server_inbound_id))->count());
            $this->line('   - statuses: ' . json_encode($clients->groupBy('status')->map->count()));

            $orders = Order::with('items.serverPlan.server')->where('customer_id', $customer->id)->get();
            $itemCount = $orders->sum(fn($o)=>$o->items->count());
            $this->line('  Orders: ' . $orders->count() . ' | Items: ' . $itemCount);
            foreach ($orders as $o) {
                $this->line("   Order #{$o->id} items: " . $o->items->pluck('serverPlan.name')->join(', '));
            }
        }

        return self::SUCCESS;
    }
}
