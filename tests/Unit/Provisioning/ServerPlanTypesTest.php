<?php

namespace Tests\Unit\Provisioning;

use Tests\TestCase;
use App\Models\ServerPlan;

class ServerPlanTypesTest extends TestCase
{
    public function test_protocols_and_types_contains_expected_values()
    {
        $protocols = ServerPlan::protocols();
        $types = ServerPlan::types();

        $this->assertIsArray($protocols);
        $this->assertArrayHasKey('vless', $protocols);
        $this->assertArrayHasKey('wireguard', $protocols);

        $this->assertIsArray($types);
        $this->assertArrayHasKey('single', $types);
        $this->assertArrayHasKey('dedicated', $types);
        $this->assertArrayHasKey('branded', $types);
    }
}
