<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\OrderItem;
use App\Models\Order;
use App\Models\ServerPlan;

class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'server_plan_id' => ServerPlan::factory(),
            'quantity' => 1,
            'unit_amount' => $this->faker->randomFloat(2, 5, 100),
            'total_amount' => function (array $attrs) {
                return $attrs['unit_amount'] * ($attrs['quantity'] ?? 1);
            },
            'agent_bought' => false,
            'expires_at' => now()->addDays(30),
        ];
    }
}
