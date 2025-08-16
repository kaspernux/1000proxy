<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Order;
use App\Models\Customer;
use Illuminate\Support\Str;

class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        $customerFactory = Customer::factory();
        return [
            'customer_id' => $customerFactory,
            // Backward-compatibility: allow legacy tests to pass user_id; we'll mirror it to customer_id via model accessor later
            'user_id' => null,
            'order_number' => strtoupper(Str::random(10)),
            'grand_amount' => $this->faker->randomFloat(2, 10, 500),
            'currency' => 'USD',
            'payment_method' => null,
            'payment_status' => 'paid',
            'order_status' => 'processing',
            'status' => 'processing',
            'subtotal' => $this->faker->randomFloat(2, 5, 400),
            'tax_amount' => 0,
            'shipping_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => $this->faker->randomFloat(2, 5, 400),
            'billing_first_name' => $this->faker->firstName(),
            'billing_last_name' => $this->faker->lastName(),
            'billing_email' => $this->faker->safeEmail(),
            'billing_phone' => $this->faker->phoneNumber(),
            'billing_company' => $this->faker->company(),
            'billing_address' => $this->faker->streetAddress(),
            'billing_city' => $this->faker->city(),
            'billing_state' => $this->faker->state(),
            'billing_postal_code' => $this->faker->postcode(),
            'billing_country' => $this->faker->countryCode(),
            'coupon_code' => null,
            'payment_transaction_id' => null,
            'payment_invoice_url' => null,
            'payment_details' => [],
            'notes' => null,
        ];
    }
}
