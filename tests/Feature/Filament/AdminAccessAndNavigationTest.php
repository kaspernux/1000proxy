<?php

namespace Tests\Feature\Filament;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class AdminAccessAndNavigationTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role): User
    {
        return User::factory()->create(['role' => $role, 'email_verified_at' => now(), 'is_active' => true]);
    }

    #[Test]
    public function roles_can_access_expected_sections()
    {
        $admin = $this->makeUser('admin');
        $manager = $this->makeUser('manager');
        $support = $this->makeUser('support_manager');
        $sales = $this->makeUser('sales_support');

        // Admin can see BI and Activity Logs
        $this->actingAs($admin)->get('/admin')->assertOk();
        $this->actingAs($admin)->get('/admin/business-intelligence')->assertOk();
        $this->actingAs($admin)->get('/admin/activity-logs')->assertOk();

        // Manager cannot access Activity Logs (admin only), but can access customers/orders/servers
        $this->actingAs($manager)->get('/admin/activity-logs')->assertForbidden();
        $this->actingAs($manager)->get('/admin/customer-management/customers')->assertOk();
        $this->actingAs($manager)->get('/admin/order-management/orders')->assertOk();
        $this->actingAs($manager)->get('/admin/server-management/servers')->assertOk();

        // Support manager: read-only sections visible
        $this->actingAs($support)->get('/admin/customer-management/customers')->assertOk();
        $this->actingAs($support)->get('/admin/server-management/servers')->assertOk();
        $this->actingAs($support)->get('/admin/order-management/orders')->assertOk();

        // Sales support: can view customers and orders but not servers
        $this->actingAs($sales)->get('/admin/customer-management/customers')->assertOk();
        $this->actingAs($sales)->get('/admin/order-management/orders')->assertOk();
        $this->actingAs($sales)->get('/admin/server-management/servers')->assertForbidden();
    }
}
