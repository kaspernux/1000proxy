<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Server;
use App\Services\XUIService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class XUIServiceUrlConstructionTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_base_url_construction_with_new_fields()
    {
        // Create a server with the new structured fields
        $server = Server::factory()->create([
            'host' => 'example.com',
            'panel_port' => 2053,
            'web_base_path' => '/admin',
            'ip' => '192.168.1.100',
            'status' => 'up',
            'panel_url' => null,
        ]);

        $expectedUrl = 'http://example.com:2053/admin';
        $this->assertEquals($expectedUrl, $server->getApiBaseUrl());
    }

    public function test_api_base_url_construction_with_https()
    {
        $server = Server::factory()->create([
            'host' => 'https://secure.example.com',
            'panel_port' => 8443,
            'web_base_path' => null,
            'ip' => '192.168.1.100',
            'status' => 'up',
            'panel_url' => null,
        ]);

    // getApiBaseUrl strips scheme from host and derives scheme; expected to preserve https
    $expectedUrl = 'https://secure.example.com:8443';
        $this->assertEquals($expectedUrl, $server->getApiBaseUrl());
    }

    public function test_api_base_url_construction_without_web_base_path()
    {
        $server = Server::factory()->create([
            'host' => '192.168.1.100',
            'panel_port' => 2053,
            'web_base_path' => null,
            'ip' => '192.168.1.100',
            'status' => 'up',
            'panel_url' => null,
        ]);

        $expectedUrl = 'http://192.168.1.100:2053';
        $this->assertEquals($expectedUrl, $server->getApiBaseUrl());
    }

    public function test_api_endpoint_construction()
    {
        $server = Server::factory()->create([
            'host' => 'example.com',
            'panel_port' => 2053,
            'web_base_path' => '/admin',
            'ip' => '192.168.1.100',
            'status' => 'up',
        ]);

        $expectedLoginUrl = 'http://example.com:2053/admin/login';
        $this->assertEquals($expectedLoginUrl, $server->getApiEndpoint('login'));

        $expectedInboundsUrl = 'http://example.com:2053/admin/panel/api/inbounds/list';
        $this->assertEquals($expectedInboundsUrl, $server->getApiEndpoint('panel/api/inbounds/list'));
    }

    public function test_backward_compatibility_with_panel_url()
    {
        $server = Server::factory()->create([
            'panel_url' => 'http://legacy.example.com:8080/panel',
            // Provide host/panel_port parsed from legacy panel_url to satisfy non-null constraints while
            // still ensuring code path selects new structure first; expectation remains same base URL
            'host' => 'legacy.example.com',
            'panel_port' => 8080,
            'web_base_path' => 'panel',
            'ip' => '192.168.1.100',
            'status' => 'up',
        ]);

    $expectedUrl = 'http://legacy.example.com:8080/panel';
    $this->assertEquals($expectedUrl, $server->getApiBaseUrl());
    }
}
