<?php

namespace Database\Seeders;

use App\Models\Server;
use App\Models\ServerInbound;
use App\Models\ServerPlan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ServerPlanSeeder extends Seeder
{
	public function run(): void
	{
		// Target the live Amsterdam X-UI server only
		$server = Server::where('host', 'amsterdam.1000proxy.me')
			->where('panel_port', 1111)
			->first();

		if (! $server) {
			$this->command?->warn('Live server not found. Run LiveXuiServerSeeder first.');
			return;
		}

		$brandId = $server->server_brand_id;
		$categoryId = $server->server_category_id;

		if (! $brandId || ! $categoryId) {
			$this->command?->warn('Live server missing brand/category association. Ensure LiveXuiServerSeeder set these.');
		}

		// Map inbounds by protocol for preferred_inbound_id assignment
		$inbounds = ServerInbound::where('server_id', $server->id)->get();
		$inboundByProtocol = $inbounds->keyBy(fn ($i) => $i->protocol ?? '');

		$plans = [
			// Shared (multiple) VLESS/VMess SOCKS/HTTP over WS+TLS — Datacenter Proxies
			['name' => 'DC Single VLESS (NL)',      'protocol' => 'vless',       'price' => 0.59,  'original_price' => 0.89, 'billing' => 'monthly', 'days' => 30, 'volume' => 25,   'bandwidth_mbps' => 30,   'type' => 'multiple', 'data_limit_gb' => 25,  'cc' => 1],
			['name' => 'DC Basic VLESS (NL)',      'protocol' => 'vless',       'price' => 0.99,  'original_price' => 1.40, 'billing' => 'monthly', 'days' => 30, 'volume' => 50,   'bandwidth_mbps' => 50,   'type' => 'multiple', 'data_limit_gb' => 50,  'cc' => 2],
			['name' => 'DC Standard VLESS (NL)',   'protocol' => 'vless',       'price' => 1.49,  'original_price' => 1.99, 'billing' => 'monthly', 'days' => 30, 'volume' => 100,  'bandwidth_mbps' => 100,  'type' => 'multiple', 'data_limit_gb' => 100, 'cc' => 3],
			['name' => 'DC Premium VLESS (NL)',    'protocol' => 'vless',       'price' => 2.49,  'original_price' => 3.20, 'billing' => 'monthly', 'days' => 30, 'volume' => 300,  'bandwidth_mbps' => 300,  'type' => 'multiple', 'data_limit_gb' => 300, 'cc' => 5],
			['name' => 'DC Ultra VLESS (NL)',      'protocol' => 'vless',       'price' => 3.49,  'original_price' => 4.20, 'billing' => 'monthly', 'days' => 30, 'volume' => 500,  'bandwidth_mbps' => 500,  'type' => 'multiple', 'data_limit_gb' => 500, 'cc' => 7],

			['name' => 'DC Single VMess (NL)',      'protocol' => 'vmess',       'price' => 0.65,  'original_price' => 0.95, 'billing' => 'monthly', 'days' => 30, 'volume' => 30,   'bandwidth_mbps' => 50,   'type' => 'multiple', 'data_limit_gb' => 30,  'cc' => 1],
			['name' => 'DC Basic VMess (NL)',      'protocol' => 'vmess',       'price' => 1.09,  'original_price' => 1.47, 'billing' => 'monthly', 'days' => 30, 'volume' => 60,   'bandwidth_mbps' => 100,  'type' => 'multiple', 'data_limit_gb' => 60,  'cc' => 2],
			['name' => 'DC Premium VMess (NL)',    'protocol' => 'vmess',       'price' => 1.89,  'original_price' => 2.40, 'billing' => 'monthly', 'days' => 30, 'volume' => 200,  'bandwidth_mbps' => 300,  'type' => 'multiple', 'data_limit_gb' => 200, 'cc' => 4],

			// Dedicated single-user: Trojan / VLESS with higher bandwidth and unlimited flag
			['name' => 'Dedicated Trojan 1G (NL)', 'protocol' => 'trojan',      'price' => 9.99,  'original_price' => 12.99,'billing' => 'monthly', 'days' => 30, 'volume' => 0,    'bandwidth_mbps' => 1000, 'type' => 'single',   'unlimited_traffic' => true, 'cc' => 1],
			['name' => 'Dedicated VLESS 1G (NL)',  'protocol' => 'vless',       'price' => 8.49,  'original_price' => 10.99,'billing' => 'monthly', 'days' => 30, 'volume' => 0,    'bandwidth_mbps' => 1000, 'type' => 'single',   'unlimited_traffic' => true, 'cc' => 1],
			['name' => 'Dedicated VMess 1G (NL)',  'protocol' => 'vmess',       'price' => 8.99,  'original_price' => 11.49,'billing' => 'monthly', 'days' => 30, 'volume' => 0,    'bandwidth_mbps' => 1000, 'type' => 'single',   'unlimited_traffic' => true, 'cc' => 1],

			// Budget and scraping-friendly Shadowsocks / SOCKS
			['name' => 'Budget Shadowsocks (NL)',  'protocol' => 'shadowsocks', 'price' => 0.67,  'original_price' => 0.99, 'billing' => 'monthly', 'days' => 30, 'volume' => 40,   'bandwidth_mbps' => 80,   'type' => 'multiple', 'data_limit_gb' => 40,  'cc' => 2],
			['name' => 'SOCKS DC Shared (NL)',     'protocol' => 'socks',       'price' => 0.80,  'original_price' => 1.10, 'billing' => 'monthly', 'days' => 30, 'volume' => 100,  'bandwidth_mbps' => 100,  'type' => 'multiple', 'data_limit_gb' => 100, 'cc' => 3],
		];

		foreach ($plans as $plan) {
			$slug = Str::slug($plan['name'] . '-' . $server->id);
			$preferredInboundId = optional($inboundByProtocol->get($plan['protocol']))->id
				?? optional($inboundByProtocol->get('vless'))->id
				?? null;

			$payload = [
				'name' => $plan['name'],
				'server_brand_id' => $brandId,
				'server_category_id' => $categoryId,
				'country_code' => 'NL',
				'region' => 'Amsterdam',
				'protocol' => $plan['protocol'],
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
				// Rich attributes
				'original_price' => $plan['original_price'] ?? null,
				'billing_cycle' => $plan['billing'] ?? 'monthly',
				'unlimited_traffic' => $plan['unlimited_traffic'] ?? false,
				'data_limit_gb' => $plan['data_limit_gb'] ?? ($plan['volume'] ?? null),
				'concurrent_connections' => $plan['cc'] ?? 2,
				'supported_protocols' => [$plan['protocol']],
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

		$this->command?->info('Live server plans seeded successfully!');
	}
}

