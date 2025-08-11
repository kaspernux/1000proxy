<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Order;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Log;
use App\Livewire\Traits\LivewireAlertV4;
use Illuminate\Validation\ValidationException;

class MyOrderDetailPage extends Component
{
    use AuthorizesRequests, LivewireAlertV4;

    public Order $order;
    public bool $isLoading = false;

    protected function rules()
    {
        return [
            'order.id' => 'required|integer|exists:orders,id',
        ];
    }

    public function mount($orderId)
    {
        try {
            $this->order = Order::with(['items.serverPlan', 'invoices', 'payment'])
                ->where('id', $orderId)
                ->where('customer_id', Auth::guard('customer')->id())
                ->firstOrFail();

            // Security logging for order access
            Log::info('Order detail accessed', [
                'order_id' => $this->order->id,
                'customer_id' => Auth::guard('customer')->id(),
                'ip' => request()->ip(),
            ]);

        } catch (\Exception $e) {
            Log::warning('Unauthorized order access attempt', [
                'order_id' => $orderId,
                'customer_id' => Auth::guard('customer')->id(),
                'ip' => request()->ip(),
                'error' => $e->getMessage()
            ]);
            
            abort(404, 'Order not found or access denied.');
        }
    }

    public function cancelOrder()
    {
        try {
            $this->isLoading = true;

            // Rate limiting for order cancellations
            $key = 'cancel_order.' . Auth::guard('customer')->id();
            if (RateLimiter::tooManyAttempts($key, 5)) {
                $seconds = RateLimiter::availableAt($key) - time();
                throw new \Exception("Too many cancellation attempts. Please try again in {$seconds} seconds.");
            }

            $this->authorize('update', $this->order);

            if ($this->order->status !== 'pending') {
                $this->alert('error', 'Cannot cancel this order. Only pending orders can be cancelled.', [
                    'position' => 'center',
                    'timer' => 4000,
                    'toast' => false,
                ]);
                return;
            }

            RateLimiter::hit($key, 300); // 5-minute window

            $this->order->update(['status' => 'cancelled']);

            // Security logging
            Log::info('Order cancelled by customer', [
                'order_id' => $this->order->id,
                'customer_id' => Auth::guard('customer')->id(),
                'ip' => request()->ip(),
                'original_status' => 'pending'
            ]);

            $this->alert('success', 'Order cancelled successfully!', [
                'position' => 'center',
                'timer' => 3000,
                'toast' => false,
            ]);

            // Redirect after brief delay
            $this->dispatch('redirect-after-delay', ['url' => route('my-orders'), 'delay' => 2000]);

        } catch (\Exception $e) {
            Log::error('Order cancellation error', [
                'order_id' => $this->order->id,
                'customer_id' => Auth::guard('customer')->id(),
                'error' => $e->getMessage(),
                'ip' => request()->ip()
            ]);
            
            $this->alert('error', 'Failed to cancel order. Please try again.', [
                'position' => 'center',
                'timer' => 4000,
                'toast' => false,
            ]);
        } finally {
            $this->isLoading = false;
        }
    }

    public function render()
    {
        return view('livewire.my-order-detail-page')
            ->layout('layouts.app', ['title' => 'Order #' . $this->order->id]);
    }
}