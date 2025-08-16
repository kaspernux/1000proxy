<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use App\Services\XUIService;
use Tests\Fakes\FakeXUIService;
use App\Models\Server;

abstract class TestCase extends BaseTestCase
{
    // Define default headers in setUp using withHeaders() to avoid property signature mismatches

    protected function setUp(): void
    {
        parent::setUp();
        // Force non-JSON Accept to exercise web redirect + session errors
        $this->withHeaders([
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
        ]);
        // Bind fake XUI service for all tests to avoid real HTTP.
        $this->app->bind(XUIService::class, function ($app, $params) {
            $server = $params['server'] ?? Server::factory()->create();
            return new FakeXUIService($server);
        });
    }
}
