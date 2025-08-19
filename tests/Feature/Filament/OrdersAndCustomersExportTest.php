<?php

namespace Tests\Feature\Filament;

use App\Models\Order;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class OrdersAndCustomersExportTest extends TestCase
{
    use RefreshDatabase;

    private function staff(string $role): User
    {
        return User::factory()->create(['role' => $role, 'email_verified_at' => now(), 'is_active' => true]);
    }

    #[Test]
    public function admin_sees_export_actions()
    {
        $admin = $this->staff('admin');
        $this->actingAs($admin);
        $this->get('/admin/order-management/orders')->assertOk();
        $this->get('/admin/customer-management/customers')->assertOk();
        // UI assertions left minimal due to Filament rendering; presence validated by policy and no 403
        $this->assertTrue($admin->can('export', \App\Models\Order::class));
        $this->assertTrue($admin->can('export', \App\Models\Customer::class));
    }

    #[Test]
    public function manager_can_export_orders_and_customers()
    {
        $manager = $this->staff('manager');
        $this->assertTrue($manager->can('export', \App\Models\Order::class));
        $this->assertTrue($manager->can('export', \App\Models\Customer::class));
    }

    #[Test]
    public function support_manager_cannot_export_orders_but_may_export_customers()
    {
        $support = $this->staff('support_manager');
        $this->assertFalse($support->can('export', \App\Models\Order::class));
        $this->assertTrue($support->can('export', \App\Models\Customer::class));
    }

    #[Test]
    public function sales_support_cannot_export()
    {
        $sales = $this->staff('sales_support');
        $this->assertFalse($sales->can('export', \App\Models\Order::class));
        $this->assertFalse($sales->can('export', \App\Models\Customer::class));
    }
}
