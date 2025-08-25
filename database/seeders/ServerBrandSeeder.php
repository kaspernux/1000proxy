<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ServerBrandSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $brands = [
            [
                'name' => 'ProxyTitan',
                'slug' => 'proxy-titan',
                'image' => 'server_brands/proxy-titan.png',
                'desc' => 'ProxyTitan is a leading server brand renowned for its powerful and secure proxy servers, designed to handle high traffic with ease. Whether for personal use or enterprise solutions, ProxyTitan provides reliable and scalable proxy services tailored to meet your needs.',
                'is_active' => true,
            ],
            [
                'name' => 'ShieldProxy',
                'slug' => 'shield-proxy',
                'image' => 'server_brands/shield-proxy.png',
                'desc' => 'ShieldProxy specializes in robust and secure proxy servers that ensure your online privacy and anonymity. With advanced encryption and security features, ShieldProxy is the go-to choice for users seeking to protect their digital footprint while enjoying seamless internet access.',
                'is_active' => true,
            ],
            [
                'name' => 'StealthNet',
                'slug' => 'stealth-net',
                'image' => 'server_brands/stealth-net.png',
                'desc' => 'StealthNet offers high-performance proxy servers that are perfect for stealth browsing and data scraping. Known for their speed and reliability, StealthNet servers provide an excellent solution for businesses and individuals requiring efficient and anonymous internet connectivity.',
                'is_active' => true,
            ],
            [
                'name' => 'GuardianProxy',
                'slug' => 'guardian-proxy',
                'image' => 'server_brands/guardian-proxy.png',
                'desc' => 'GuardianProxy is dedicated to providing top-tier proxy services with a focus on security and performance. Their servers are optimized for a variety of applications, including gaming, streaming, and secure browsing, ensuring a smooth and protected online experience for all users.',
                'is_active' => true,
            ],
            [
                'name' => 'FroxyEdge',
                'slug' => 'froxy-edge',
                'image' => 'server_brands/froxy-edge.png',
                'desc' => 'FroxyEdge focuses on premium rotating and residential pools with >99% uptime and low-latency exit nodes ideal for e‑commerce and SERP workloads.',
                'is_active' => true,
            ],
            [
                'name' => 'LunaNet',
                'slug' => 'luna-net',
                'image' => 'server_brands/luna-net.png',
                'desc' => 'LunaNet delivers secure, compliance-ready ISP and static residential IPs with flexible billing and encryption-first architecture.',
                'is_active' => true,
            ],
            [
                'name' => 'NexusDC',
                'slug' => 'nexus-dc',
                'image' => 'server_brands/nexus-dc.png',
                'desc' => 'NexusDC provides high‑performance datacenter networks, balanced for scraping bursts and stable production traffic at scale.',
                'is_active' => true,
            ],
            [
                'name' => 'WhiteProxy Labs',
                'slug' => 'whiteproxy-labs',
                'image' => 'server_brands/whiteproxy-labs.png',
                'desc' => 'WhiteProxy Labs ships curated IPv4/IPv6, mobile, and ISP proxies with developer-friendly tooling and fair pricing.',
                'is_active' => true,
            ],
        ];

        // Use updateOrCreate to avoid duplicate entry errors
        foreach ($brands as $brand) {
            \App\Models\ServerBrand::updateOrCreate(
                ['slug' => $brand['slug']], // Check by slug
                $brand // Update with all data
            );
        }
    }
}
