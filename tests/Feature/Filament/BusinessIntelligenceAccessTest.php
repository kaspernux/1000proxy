<?php

namespace Tests\Feature\Filament;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class BusinessIntelligenceAccessTest extends TestCase
{
    use RefreshDatabase;

    private function make(string $role): User
    {
        return User::factory()->create(['role' => $role, 'email_verified_at' => now(), 'is_active' => true]);
    }

    #[Test]
    public function admin_can_access_bi_all_pages()
    {
        $u = $this->make('admin');
        $this->actingAs($u)->get('/admin/business-intelligence')->assertOk();
        $this->actingAs($u)->get('/admin/business-intelligence/revenue')->assertOk();
        $this->actingAs($u)->get('/admin/business-intelligence/users')->assertOk();
        $this->actingAs($u)->get('/admin/business-intelligence/servers')->assertOk();
        $this->actingAs($u)->get('/admin/business-intelligence/insights')->assertOk();
    }

    #[Test]
    public function manager_support_can_access_bi_when_enabled()
    {
        $manager = $this->make('manager');
        $support = $this->make('support_manager');

        // Index
        $this->actingAs($manager)->get('/admin/business-intelligence')->assertOk();
        $this->actingAs($support)->get('/admin/business-intelligence')->assertOk();
        // Subpages
        foreach (['/revenue','/users','/servers','/insights'] as $sub) {
            $this->actingAs($manager)->get('/admin/business-intelligence' . $sub)->assertOk();
            $this->actingAs($support)->get('/admin/business-intelligence' . $sub)->assertOk();
        }
    }

    #[Test]
    public function sales_support_cannot_access_bi()
    {
        $u = $this->make('sales_support');
        $this->actingAs($u)->get('/admin/business-intelligence')->assertForbidden();
        foreach (['/revenue','/users','/servers','/insights'] as $sub) {
            $this->actingAs($u)->get('/admin/business-intelligence' . $sub)->assertForbidden();
        }
    }

    #[Test]
    public function analyst_can_access_bi()
    {
        $u = $this->make('analyst');
        $this->actingAs($u)->get('/admin/business-intelligence')->assertOk();
    }
}
