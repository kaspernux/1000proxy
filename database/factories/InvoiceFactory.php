<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Order;
use App\Models\Customer;
use App\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        return [
            'iid' => strtoupper($this->faker->bothify('INV########')),
            'wallet_transaction_id' => null,
            'customer_id' => Customer::factory(),
            'payment_method_id' => PaymentMethod::factory(),
            'payment_id' => strtoupper($this->faker->bothify('PAY########')),
            'payment_status' => $this->faker->randomElement(['pending', 'paid', 'failed']),
            'pay_address' => $this->faker->bothify('ADDR################'),
            'price_amount' => $this->faker->randomFloat(2, 10, 500),
            'price_currency' => 'USD',
            'pay_amount' => null,
            'pay_currency' => null,
            'order_id' => Order::factory(),
            'order_description' => $this->faker->sentence(),
            'ipn_callback_url' => $this->faker->url(),
            'invoice_url' => $this->faker->url(),
            'success_url' => $this->faker->url(),
            'cancel_url' => $this->faker->url(),
            'partially_paid_url' => $this->faker->url(),
            'purchase_id' => $this->faker->uuid(),
            'amount_received' => 0,
            'payin_extra_id' => null,
            'smart_contract' => null,
            'network' => null,
            'network_precision' => null,
            'time_limit' => null,
            'expiration_estimate_date' => null,
            'is_fixed_rate' => false,
            'is_fee_paid_by_user' => true,
            'valid_until' => null,
            'type' => null,
            'redirect_url' => null,
        ];
    }
}
