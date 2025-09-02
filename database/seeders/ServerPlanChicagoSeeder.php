<?php

namespace Database\Seeders;

use App\Models\Server;
use App\Models\ServerInbound;
use App\Models\ServerPlan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ServerPlanChicagoSeeder extends Seeder
{
    public function run(): void
    {
        // Target the Chicago X-UI server only
        $server = Server::where('host', 'chicago.1000proxy.me')
            ->where('panel_port', 1111)
            ->first();

        if (! $server) {
            $this->command?->warn('Chicago server not found. Run LiveXuiServerSeeder or ensure the server record exists.');
            return;
        }

        // Allow optional overrides via environment variables
        $brandId = env('CHICAGO_BRAND_ID') ?: $server->server_brand_id;
        $categoryId = env('CHICAGO_CATEGORY_ID') ?: $server->server_category_id;

        if (! $brandId || ! $categoryId) {
            $this->command?->warn('Chicago server missing brand/category association. Provide CHICAGO_BRAND_ID and CHICAGO_CATEGORY_ID if needed.');
        }

        $inbounds = ServerInbound::where('server_id', $server->id)->get();
        $inboundByProtocol = $inbounds->keyBy(fn ($i) => $i->protocol ?? '');

        // Reuse same plan definitions as Amsterdam but region/cc set to US/Chicago
        $plans = [
            ['name' => 'DC Single VLESS (US)',      'protocol' => 'vless',       'price' => 0.59,  'original_price' => 0.89, 'billing' => 'monthly', 'days' => 30, 'volume' => 25,   'bandwidth_mbps' => 30,   'type' => 'multiple', 'data_limit_gb' => 25,  'cc' => 1],
            ['name' => 'DC Basic VLESS (US)',      'protocol' => 'vless',       'price' => 0.99,  'original_price' => 1.40, 'billing' => 'monthly', 'days' => 30, 'volume' => 50,   'bandwidth_mbps' => 50,   'type' => 'multiple', 'data_limit_gb' => 50,  'cc' => 2],
            ['name' => 'DC Standard VLESS (US)',   'protocol' => 'vless',       'price' => 1.49,  'original_price' => 1.99, 'billing' => 'monthly', 'days' => 30, 'volume' => 100,  'bandwidth_mbps' => 100,  'type' => 'multiple', 'data_limit_gb' => 100, 'cc' => 3],
            ['name' => 'DC Premium VLESS (US)',    'protocol' => 'vless',       'price' => 2.49,  'original_price' => 3.20, 'billing' => 'monthly', 'days' => 30, 'volume' => 300,  'bandwidth_mbps' => 300,  'type' => 'multiple', 'data_limit_gb' => 300, 'cc' => 5],
            ['name' => 'DC Ultra VLESS (US)',      'protocol' => 'vless',       'price' => 3.49,  'original_price' => 4.20, 'billing' => 'monthly', 'days' => 30, 'volume' => 500,  'bandwidth_mbps' => 500,  'type' => 'multiple', 'data_limit_gb' => 500, 'cc' => 7],

            ['name' => 'DC Single VMess (US)',      'protocol' => 'vmess',       'price' => 0.65,  'original_price' => 0.95, 'billing' => 'monthly', 'days' => 30, 'volume' => 30,   'bandwidth_mbps' => 50,   'type' => 'multiple', 'data_limit_gb' => 30,  'cc' => 1],
            ['name' => 'DC Basic VMess (US)',      'protocol' => 'vmess',       'price' => 1.09,  'original_price' => 1.47, 'billing' => 'monthly', 'days' => 30, 'volume' => 60,   'bandwidth_mbps' => 100,  'type' => 'multiple', 'data_limit_gb' => 60,  'cc' => 2],
            ['name' => 'DC Premium VMess (US)',    'protocol' => 'vmess',       'price' => 1.89,  'original_price' => 2.40, 'billing' => 'monthly', 'days' => 30, 'volume' => 200,  'bandwidth_mbps' => 300,  'type' => 'multiple', 'data_limit_gb' => 200, 'cc' => 4],

            ['name' => 'Dedicated Trojan 1G (US)', 'protocol' => 'trojan',      'price' => 9.99,  'original_price' => 12.99,'billing' => 'monthly', 'days' => 30, 'volume' => 0,    'bandwidth_mbps' => 1000, 'type' => 'single',   'unlimited_traffic' => true, 'cc' => 1],
            ['name' => 'Dedicated VLESS 1G (US)',  'protocol' => 'vless',       'price' => 8.49,  'original_price' => 10.99,'billing' => 'monthly', 'days' => 30, 'volume' => 0,    'bandwidth_mbps' => 1000, 'type' => 'single',   'unlimited_traffic' => true, 'cc' => 1],
            ['name' => 'Dedicated VMess 1G (US)',  'protocol' => 'vmess',       'price' => 8.99,  'original_price' => 11.49,'billing' => 'monthly', 'days' => 30, 'volume' => 0,    'bandwidth_mbps' => 1000, 'type' => 'single',   'unlimited_traffic' => true, 'cc' => 1],

            ['name' => 'Budget Shadowsocks (US)',  'protocol' => 'shadowsocks', 'price' => 0.67,  'original_price' => 0.99, 'billing' => 'monthly', 'days' => 30, 'volume' => 40,   'bandwidth_mbps' => 80,   'type' => 'multiple', 'data_limit_gb' => 40,  'cc' => 2],
            ['name' => 'SOCKS DC Shared (US)',     'protocol' => 'socks',       'price' => 0.80,  'original_price' => 1.10, 'billing' => 'monthly', 'days' => 30, 'volume' => 100,  'bandwidth_mbps' => 100,  'type' => 'multiple', 'data_limit_gb' => 100, 'cc' => 3],
        ];

        foreach ($plans as $plan) {
            $slug = Str::slug($plan['name'] . '-' . $server->id);
            // Normalize protocol to allowed enum values to avoid SQL enum truncation errors
            $allowed = ['vless', 'vmess', 'trojan', 'shadowsocks', 'mixed'];
            $protocol = strtolower($plan['protocol'] ?? 'mixed');
            if (! in_array($protocol, $allowed, true)) {
                // Map unknown/legacy protocols (e.g. 'socks') to 'mixed'
                $protocol = 'mixed';
            }

            $preferredInboundId = optional($inboundByProtocol->get($protocol))->id
                ?? optional($inboundByProtocol->get('vless'))->id
                ?? null;

            $payload = [
                'name' => $plan['name'],
                'server_brand_id' => $brandId,
                'server_category_id' => $categoryId,
                'country_code' => 'US',
                'region' => 'Chicago',
                'protocol' => $protocol,
                'bandwidth_mbps' => $plan['bandwidth_mbps'],
                'supports_ipv6' => true,
                'popularity_score' => 0,
                'server_status' => 'online',
                'product_image' => 'server-plans/default-plan.png',
                'description' => 'Live X-UI server plan aligned to market pricing and performance.',
                'capacity' => 10000,
                'price' => $plan['price'],
                'type' => $plan['type'],
                'days' => $plan['days'],
                'duration_days' => $plan['days'],
                'volume' => $plan['volume'],
                'is_active' => true,
                'is_featured' => false,
                'is_popular' => false,
                'in_stock' => true,
                'on_sale' => true,
                'preferred_inbound_id' => $preferredInboundId,
                'original_price' => $plan['original_price'] ?? null,
                'billing_cycle' => $plan['billing'] ?? 'monthly',
                'unlimited_traffic' => $plan['unlimited_traffic'] ?? false,
                'data_limit_gb' => $plan['data_limit_gb'] ?? ($plan['volume'] ?? null),
                'concurrent_connections' => $plan['cc'] ?? 2,
                'supported_protocols' => [$protocol],
                'auto_provision' => true,
            ];

            ServerPlan::updateOrCreate(
                [
                    'server_id' => $server->id,
                    'slug' => $slug,
                ],
                $payload
            );
        }

        $this->command?->info('Chicago server plans seeded successfully!');
    }
}
