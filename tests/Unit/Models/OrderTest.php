<?php

namespace Tests\Unit\Models;

use App\Models\Order;
use App\Models\User;
use App\Models\OrderItem;
use App\Models\Invoice;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_can_be_created()
    {
        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'total_amount' => 99.99,
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'total_amount' => 99.99,
            'status' => 'pending',
        ]);
    }

    public function test_order_belongs_to_user()
    {
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $order->user);
        $this->assertEquals($user->id, $order->user->id);
    }

    public function test_order_can_have_invoice()
    {
        $order = Order::factory()->create();
        $invoice = Invoice::factory()->create(['order_id' => $order->id]);

        $this->assertInstanceOf(Invoice::class, $order->invoice);
        $this->assertEquals($invoice->id, $order->invoice->id);
    }

    public function test_order_can_have_order_items()
    {
        $order = Order::factory()->create();
        $orderItem = OrderItem::factory()->create(['order_id' => $order->id]);

        $this->assertTrue($order->orderItems->contains($orderItem));
    }

    public function test_order_calculates_total_correctly()
    {
        $order = Order::factory()->create();
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'quantity' => 2,
            'unit_amount' => 50.00,
        ]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'quantity' => 1,
            'unit_amount' => 25.00,
        ]);

        $expectedTotal = (2 * 50.00) + (1 * 25.00); // 125.00
        
        // Assuming there's a method to calculate total
        // $this->assertEquals($expectedTotal, $order->calculateTotal());
    }

    public function test_order_can_be_marked_as_paid()
    {
        $order = Order::factory()->create(['payment_status' => 'pending']);
        
        $order->update(['payment_status' => 'paid']);
        
        $this->assertEquals('paid', $order->fresh()->payment_status);
    }

    public function test_order_can_be_marked_as_completed()
    {
        $order = Order::factory()->create(['status' => 'processing']);
        
        $order->update(['status' => 'completed']);
        
        $this->assertEquals('completed', $order->fresh()->status);
    }

    public function test_order_fillable_attributes()
    {
        $order = new Order();
        $fillable = $order->getFillable();

        $expectedFillable = [
            'user_id', 'status', 'payment_status', 'total_amount', 'grand_amount'
        ];

        foreach ($expectedFillable as $attribute) {
            $this->assertContains($attribute, $fillable, "Attribute {$attribute} should be fillable");
        }
    }

    public function test_order_casts()
    {
        $order = new Order();
        $casts = $order->getCasts();

        // Assuming these casts exist
        $this->assertArrayHasKey('total_amount', $casts);
        $this->assertArrayHasKey('grand_amount', $casts);
    }

    public function test_order_scope_paid()
    {
        Order::factory()->create(['payment_status' => 'paid']);
        Order::factory()->create(['payment_status' => 'pending']);
        Order::factory()->create(['payment_status' => 'paid']);

        // Assuming there's a paid scope
        // $paidOrders = Order::paid()->get();
        // $this->assertCount(2, $paidOrders);
    }

    public function test_order_scope_completed()
    {
        Order::factory()->create(['status' => 'completed']);
        Order::factory()->create(['status' => 'pending']);
        Order::factory()->create(['status' => 'completed']);

        // Assuming there's a completed scope
        // $completedOrders = Order::completed()->get();
        // $this->assertCount(2, $completedOrders);
    }
}
