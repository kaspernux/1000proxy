<?php

namespace Tests\Feature\Filament;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrdersCsvExportActionTest extends TestCase
{
    use RefreshDatabase;

    private function staff(string $role): User
    {
        return User::factory()->create(['role' => $role, 'email_verified_at' => now(), 'is_active' => true]);
    }

    #[Test]
    public function admin_can_trigger_bulk_export_csv()
    {
        $admin = $this->staff('admin');
        $this->actingAs($admin);

        // Seed a few orders
        Order::factory()->count(5)->create();

        // Reach the Orders index to ensure policies allow access
        $this->get('/admin/proxy-shop/orders')->assertOk();

        // Use the resource bulk export to stream a CSV
        $component = Livewire::test(\App\Filament\Clusters\ProxyShop\Resources\OrderResource\Pages\ListOrders::class);
        $component->assertOk();

        // Select all orders and trigger the bulk export
        $ids = Order::pluck('id')->toArray();
        $component->callTableBulkAction('export_csv', $ids)
            ->assertOk();
    }

    #[Test]
    public function support_manager_cannot_see_header_export_action()
    {
        $support = $this->staff('support_manager');
        $this->actingAs($support);

        // Support manager can access Orders list, but export is gated by policy
        $this->get('/admin/proxy-shop/orders')->assertOk();

        // Sanity check: policy denies export
        $this->assertFalse($support->can('export', \App\Models\Order::class));
    }
}
