<?php

namespace Tests\Unit\Services;

use App\Services\XUIService;
use Tests\TestCase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;

class XUIServiceTest extends TestCase
{
    protected XUIService $xuiService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->xuiService = XUIService::makeLegacy();
    }

    public function test_can_authenticate_with_xui_panel()
    {
        Http::fake([
            '*/login' => Http::response([
                'success' => true,
                'msg' => 'Login successful',
                'obj' => 'session_cookie_here'
            ], 200)
        ]);

        $result = $this->xuiService->authenticate('admin', 'password', 'https://test-panel.com');

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('session', $result);
    }

    public function test_authentication_fails_with_invalid_credentials()
    {
        Http::fake([
            '*/login' => Http::response([
                'success' => false,
                'msg' => 'Invalid credentials'
            ], 401)
        ]);

        $result = $this->xuiService->authenticate('admin', 'wrong_password', 'https://test-panel.com');

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    public function test_can_create_client()
    {
        Http::fake([
            '*/panel/api/inbounds/addClient' => Http::response([
                'success' => true,
                'msg' => 'Client added successfully',
                'obj' => [
                    'id' => 'uuid-123',
                    'settings' => '{"clients":[{"id":"uuid-123","email":"test@example.com"}]}'
                ]
            ], 200)
        ]);

        $clientData = [
            'id' => 1,
            'email' => 'test@example.com',
            'uuid' => 'uuid-123',
            'enable' => true,
            'limit_ip' => 1,
            'totalGB' => 1073741824, // 1GB
            'expiry_time' => now()->addDays(30)->timestamp * 1000,
        ];

        $result = $this->xuiService->createClient($clientData, 'session_cookie');

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('client', $result);
    }

    public function test_can_generate_subscription_link()
    {
        $serverConfig = [
            'host' => 'test.example.com',
            'port' => 443,
            'protocol' => 'vless',
            'uuid' => 'uuid-123',
            'path' => '/path',
            'security' => 'tls',
        ];

        $link = $this->xuiService->generateSubscriptionLink($serverConfig);

        $this->assertStringStartsWith('vless://', $link);
        $this->assertStringContainsString('uuid-123', $link);
        $this->assertStringContainsString('test.example.com', $link);
    }

    public function test_can_generate_qr_code()
    {
        $subscriptionLink = 'vless://uuid-123@test.example.com:443?path=/path&security=tls';

        $qrCode = $this->xuiService->generateQRCode($subscriptionLink);

        $this->assertStringStartsWith('data:image/png;base64,', $qrCode);
    }

    public function test_can_get_client_statistics()
    {
        Http::fake([
            '*/panel/api/inbounds/getClientTraffics/*' => Http::response([
                'success' => true,
                'obj' => [
                    'up' => 1048576,   // 1MB
                    'down' => 2097152, // 2MB
                    'total' => 3145728 // 3MB
                ]
            ], 200)
        ]);

        $stats = $this->xuiService->getClientStats('uuid-123', 'session_cookie');

        $this->assertTrue($stats['success']);
        $this->assertArrayHasKey('up', $stats['data']);
        $this->assertArrayHasKey('down', $stats['data']);
        $this->assertArrayHasKey('total', $stats['data']);
    }

    public function test_can_delete_client()
    {
        Http::fake([
            '*/panel/api/inbounds/1/delClient/uuid-123' => Http::response([
                'success' => true,
                'msg' => 'Client deleted successfully'
            ], 200)
        ]);

        $deleted = $this->xuiService->deleteClient(1, 'uuid-123');

        $this->assertTrue($deleted);
    }

    public function test_can_update_client()
    {
        Http::fake([
            '*/panel/api/inbounds/updateClient/uuid-123' => Http::response([
                'success' => true,
                'msg' => 'Client updated successfully'
            ], 200)
        ]);

        $settingsJson = json_encode([
            'clients' => [[
                'id' => 'uuid-123',
                'email' => 'updated@example.com',
                'enable' => true,
            ]]
        ]);

        $updated = $this->xuiService->updateClient('uuid-123', 1, $settingsJson);

        $this->assertTrue($updated);
    }

    public function test_handles_network_errors_gracefully()
    {
        Http::fake(function () {
            throw new \Exception('Network timeout');
        });

        $result = $this->xuiService->authenticate('admin', 'password', 'https://test-panel.com');

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('Network timeout', $result['error']);
    }

    public function test_validates_server_configuration()
    {
        $invalidConfig = [
            'host' => '', // Empty host
            'port' => 'invalid_port',
            'protocol' => 'unknown_protocol',
        ];

        $result = $this->xuiService->validateServerConfig($invalidConfig);

        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('errors', $result);
    }

    public function test_can_batch_create_clients()
    {
        Http::fake([
            '*/panel/api/inbounds/addClient' => Http::response([
                'success' => true,
                'msg' => 'Client added successfully'
            ], 200)
        ]);

        $clientsData = [
            ['email' => 'user1@example.com', 'uuid' => 'uuid-1'],
            ['email' => 'user2@example.com', 'uuid' => 'uuid-2'],
            ['email' => 'user3@example.com', 'uuid' => 'uuid-3'],
        ];

        $results = $this->xuiService->batchCreateClients($clientsData, 'session_cookie');

        $this->assertCount(3, $results);
        foreach ($results as $result) {
            $this->assertTrue($result['success']);
        }
    }
}
