<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use App\Services\XUIService;
use Tests\Fakes\FakeXUIService;
use App\Models\Server;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Bind fake XUI service for all tests to avoid real HTTP.
        $this->app->bind(XUIService::class, function ($app, $params) {
            $server = $params['server'] ?? Server::factory()->create();
            return new FakeXUIService($server);
        });
    }
}
