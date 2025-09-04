<?php

namespace Tests\Unit\Provisioning;

use Tests\TestCase;
use Illuminate\Support\Facades\Http;
use App\Services\ClientProvisioningService;
use App\Services\XUIService;
use App\Models\ServerInbound;
use App\Models\ServerPlan;
use App\Models\Server;

class ClientProvisioningServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Http::fake([
            '*/panel/api/inbounds/addClient' => Http::response([
                'success' => true,
                'msg' => 'Client added successfully',
                'obj' => [
                    'id' => 'uuid-test',
                    'settings' => json_encode(['clients' => [['id' => 'uuid-test','email' => 'test']]]),
                ],
            ], 200),
            // Generic fallback for other panel endpoints
            '*/panel/api/*' => Http::response(['success' => true], 200),
        ]);
    }

    public function test_create_remote_client_for_vless()
    {
        Http::fake([
            '*/panel/api/inbounds/addClient' => Http::response(['success' => true, 'msg' => 'ok', 'obj' => ['id' => 'uuid-1']], 200),
        ]);

        $xui = XUIService::makeLegacy();
        $service = new ClientProvisioningService($xui);

        $inbound = new ServerInbound();
        $inbound->id = 1;
        $inbound->remote_id = 1;
        $inbound->protocol = 'vless';
        $inbound->port = 12345;
        $inbound->settings = [];
        $inbound->server = new class {
            public $sni = 'test.sni';
            public $panel_url = 'https://127.0.0.1';
            public $host = '127.0.0.1';
            public function getPanelHost() { return '127.0.0.1'; }
            public function getSubscriptionPort() { return 80; }
        };

        $payload = [
            'id' => 'uuid-1',
            'email' => 'user-vless',
            'limit_ip' => 0,
            'totalGB' => 0,
            'expiry_time' => now()->addDays(30)->timestamp * 1000,
            'subId' => 'sub-1',
        ];

    $ref = new \ReflectionClass($service);
    $method = $ref->getMethod('createRemoteClient');
    $method->setAccessible(true);
    $result = $method->invokeArgs($service, [$inbound, $payload]);

    $this->assertTrue(is_array($result));
    $this->assertTrue(!empty($result['id']) || (!empty($result['success']) && $result['success'] === true));
    }

    public function test_create_remote_client_for_vmess_and_wireguard_and_http()
    {
        Http::fake([
            '*/panel/api/inbounds/addClient' => Http::response(['success' => true, 'msg' => 'ok', 'obj' => ['id' => 'uuid-2']], 200),
        ]);

    $xui = XUIService::makeLegacy();
    $service = new ClientProvisioningService($xui);

    // vmess
    $inboundV = new ServerInbound();
    $inboundV->id = 2; $inboundV->remote_id = 2; $inboundV->protocol = 'vmess'; $inboundV->port = 23456; $inboundV->settings = ['alterId' => 0];
    $inboundV->server = new class { public $sni = 'vmess.sni'; public $panel_url = 'https://127.0.0.1'; public $host = '127.0.0.1'; public function getPanelHost(){return '127.0.0.1';} public function getSubscriptionPort(){return 80;}};
    $vmess = ['id' => 'vmess-uuid', 'email' => 'user-vmess', 'limit_ip' => 0, 'totalGB' => 0, 'expiry_time' => now()->addDays(30)->timestamp * 1000, 'subId' => 'sub-2'];
    $method = (new \ReflectionClass($service))->getMethod('createRemoteClient');
    $method->setAccessible(true);
    $resVmess = $method->invokeArgs($service, [$inboundV, $vmess]);
    $this->assertTrue(is_array($resVmess));
    $this->assertTrue(!empty($resVmess['id']) || (!empty($resVmess['success']) && $resVmess['success'] === true));

    // wireguard
    $inboundW = new ServerInbound();
    $inboundW->id = 3; $inboundW->remote_id = 3; $inboundW->protocol = 'wireguard'; $inboundW->port = 34567; $inboundW->settings = [];
    $inboundW->server = new class { public $sni = 'wg.sni'; public $panel_url = 'https://127.0.0.1'; public $host = '127.0.0.1'; public function getPanelHost(){return '127.0.0.1';} public function getSubscriptionPort(){return 80;}};
    $wg = ['id' => 'wg-uuid', 'email' => 'user-wg', 'publicKey' => 'pk', 'presharedKey' => 'psk', 'limit_ip' => 0, 'totalGB' => 0, 'expiry_time' => now()->addDays(30)->timestamp * 1000, 'subId' => 'sub-3'];
    $resWg = $method->invokeArgs($service, [$inboundW, $wg]);
    $this->assertTrue(is_array($resWg));
    $this->assertTrue(!empty($resWg['id']) || (!empty($resWg['success']) && $resWg['success'] === true));

    // http
    $inboundH = new ServerInbound();
    $inboundH->id = 4; $inboundH->remote_id = 4; $inboundH->protocol = 'http'; $inboundH->port = 45678; $inboundH->settings = [];
    $inboundH->server = new class { public $sni = 'http.sni'; public $panel_url = 'https://127.0.0.1'; public $host = '127.0.0.1'; public function getPanelHost(){return '127.0.0.1';} public function getSubscriptionPort(){return 80;}};
    $http = ['id' => 'http-1', 'email' => 'user-http', 'username' => 'u', 'password' => 'p', 'limit_ip' => 0, 'totalGB' => 0, 'expiry_time' => now()->addDays(30)->timestamp * 1000, 'subId' => 'sub-4'];
    $resHttp = $method->invokeArgs($service, [$inboundH, $http]);
    $this->assertTrue(is_array($resHttp));
    $this->assertTrue(!empty($resHttp['id']) || (!empty($resHttp['success']) && $resHttp['success'] === true));
    }
}
