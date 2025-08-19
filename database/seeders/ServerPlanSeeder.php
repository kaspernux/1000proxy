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
			['name' => 'Basic - Amsterdam (VLESS)',    'protocol' => 'vless',       'price' => 6.99,  'days' => 30, 'volume' => 100,  'bandwidth_mbps' => 100,  'type' => 'multiple'],
			['name' => 'Standard - Amsterdam (VLESS)', 'protocol' => 'vless',       'price' => 9.99,  'days' => 30, 'volume' => 200,  'bandwidth_mbps' => 200,  'type' => 'multiple'],
			['name' => 'Premium - Amsterdam (VLESS)',  'protocol' => 'vless',       'price' => 12.99, 'days' => 30, 'volume' => 300,  'bandwidth_mbps' => 300,  'type' => 'multiple'],
			['name' => 'Pro - Amsterdam (VLESS)',      'protocol' => 'vless',       'price' => 19.99, 'days' => 30, 'volume' => 500,  'bandwidth_mbps' => 500,  'type' => 'multiple'],
			['name' => 'Ultra - Amsterdam (VLESS)',    'protocol' => 'vless',       'price' => 24.99, 'days' => 30, 'volume' => 800,  'bandwidth_mbps' => 1000, 'type' => 'multiple'],
			['name' => 'Basic - Amsterdam (VMess)',    'protocol' => 'vmess',       'price' => 7.99,  'days' => 30, 'volume' => 120,  'bandwidth_mbps' => 150,  'type' => 'multiple'],
			['name' => 'Brand Edition - Amsterdam (VMess)', 'protocol' => 'vmess',  'price' => 14.99, 'days' => 30, 'volume' => 350,  'bandwidth_mbps' => 350,  'type' => 'branded'],
			['name' => 'Dedicated - Amsterdam (Trojan)',   'protocol' => 'trojan',  'price' => 29.99, 'days' => 30, 'volume' => 1000, 'bandwidth_mbps' => 1000, 'type' => 'single'],
			['name' => 'Budget - Amsterdam (Shadowsocks)', 'protocol' => 'shadowsocks','price' => 4.99, 'days' => 30, 'volume' => 80, 'bandwidth_mbps' => 80, 'type' => 'multiple'],
		];

		foreach ($plans as $plan) {
			$slug = Str::slug($plan['name'] . '-' . $server->id);
			$preferredInboundId = optional($inboundByProtocol->get($plan['protocol']))->id
				?? optional($inboundByProtocol->get('vless'))->id
				?? null;

			ServerPlan::updateOrCreate(
				[
					'server_id' => $server->id,
					'slug' => $slug,
				],
				[
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
					'name' => $plan['name'],
					'product_image' => 'server-plans/default-plan.png',
					'description' => 'Live server plan for Amsterdam region',
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
				]
			);
		}

		$this->command?->info('Live server plans seeded successfully!');
	}
}

