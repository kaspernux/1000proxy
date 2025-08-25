<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\{Customer, ServerPlan, Order, OrderItem};

class CreateProgrammaticOrder extends Command
{
    protected $signature = 'orders:create-programmatic
        {--customer= : Customer ID}
        {--email= : Customer email to find}
        {--plan= : ServerPlan ID}
        {--slug= : ServerPlan slug to find}
        {--quantity=1 : Quantity}
        {--paid : Mark order as paid}
        {--json : Output JSON summary}';

    protected $description = 'Create an order programmatically (with invoice) for a given customer and plan.';

    public function handle(): int
    {
        $json = (bool) $this->option('json');
        try {
            // Resolve customer
            $customer = null;
            if ($id = $this->option('customer')) {
                $customer = Customer::find($id);
            } elseif ($email = $this->option('email')) {
                $customer = Customer::where('email', $email)->first();
            } else {
                $customer = Customer::latest('id')->first();
            }
            if (!$customer) { $this->error('Customer not found'); return 1; }

            // Resolve plan
            $plan = null;
            if ($pid = $this->option('plan')) {
                $plan = ServerPlan::find($pid);
            } elseif ($slug = $this->option('slug')) {
                $plan = ServerPlan::where('slug', $slug)->first();
            } else {
                $plan = ServerPlan::where('is_active', true)->orderByDesc('id')->first();
            }
            if (!$plan) { $this->error('ServerPlan not found'); return 1; }

            $qty = max(1, (int) $this->option('quantity'));
            $unit = (float) ($plan->price ?? 0);
            $total = $unit * $qty;
            $paid = (bool) $this->option('paid');

            $order = null; $invoice = null; $item = null;
            DB::transaction(function () use ($customer, $plan, $qty, $unit, $total, $paid, &$order, &$invoice, &$item) {
                $order = Order::create([
                    'customer_id' => $customer->id,
                    'grand_amount' => $total,
                    'total_amount' => $total,
                    'currency' => 'USD',
                    'payment_status' => $paid ? 'paid' : 'pending',
                    'order_status' => $paid ? 'processing' : 'new',
                ]);
                $item = OrderItem::create([
                    'order_id' => $order->id,
                    'server_plan_id' => $plan->id,
                    'quantity' => $qty,
                    'unit_amount' => $unit,
                    'total_amount' => $total,
                ]);
                // Ensure invoice exists; reflect paid flag
                $invoice = $order->ensureInvoice([
                    'payment_status' => $paid ? 'paid' : 'pending',
                ]);
            });

            $data = [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'customer_id' => $order->customer_id,
                'payment_status' => $order->payment_status,
                'order_status' => $order->order_status,
                'total' => (string) $order->total_amount,
                'invoice_id' => $invoice?->id,
                'plan' => [
                    'id' => $plan->id,
                    'slug' => $plan->slug,
                    'name' => $plan->name,
                ],
                'item_id' => $item?->id,
            ];
            if ($json) { $this->line(json_encode($data, JSON_PRETTY_PRINT)); }
            else {
                $this->info("Order #{$data['order_id']} created for customer {$data['customer_id']}, invoice #" . ($data['invoice_id'] ?? 'null'));
            }
            return 0;
        } catch (\Throwable $e) {
            if ($json) { $this->line(json_encode(['error' => $e->getMessage()], JSON_PRETTY_PRINT)); }
            else { $this->error($e->getMessage()); }
            return 1;
        }
    }
}
