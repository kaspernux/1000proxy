<?php

namespace Tests\Feature\Integration;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use App\Models\Server;
use App\Services\XUIService;

class TgBotBackupSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_backup_endpoint_smoke()
    {
        $server = Server::factory()->create([
            'host' => 'tg.example.com',
            'is_active' => true,
        ]);

        Http::fake([
            '*/login' => Http::response(['success' => true, 'obj' => 'sess'], 200),
            '*/panel/api/inbounds/createbackup' => Http::response(['success' => true, 'msg' => 'ok'], 200),
        ]);

        $svc = new XUIService($server);
        $this->assertTrue($svc->createBackup());
    }
}
