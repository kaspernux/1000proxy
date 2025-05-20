<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use App\Jobs\ProcessXuiOrder;

#[Title('Success - 1000 PROXIES')]
class SuccessPage extends Component
{
    #[Url]
    public $session_id;

    #[Url]
    public $payment_id;

    public function render()
    {
        $latest_order = Order::with(['invoice', 'paymentMethod', 'customer'])->where('customer_id', auth()->user()->id)->latest()->first();

        if (!$latest_order || !$latest_order->paymentMethod) {
            return redirect()->route('cancel');
        }

        $slug = $latest_order->paymentMethod->slug;

        if ($slug === 'stripe' && $this->session_id) {
            Stripe::setApiKey(env('STRIPE_SECRET'));
            $session_info = Session::retrieve($this->session_id);

            if ($session_info->payment_status !== 'paid') {
                $latest_order->update(['payment_status' => 'failed']);
                return redirect()->route('cancel');
            }

            $latest_order->update(['payment_status' => 'paid']);
        }

        elseif ($slug === 'nowpayments') {
            $response = Http::get(route('payment.status', ['orderId' => $latest_order->id]));

            if ($response->successful()) {
                $payment_status = $response->json('payment_status');

                if ($payment_status === 'finished') {
                    $latest_order->update(['payment_status' => 'paid']);
                } else {
                    $latest_order->update(['payment_status' => 'pending']);
                    return redirect()->route('my.orders');
                }
            } else {
                $latest_order->update(['payment_status' => 'failed']);
                return redirect()->route('cancel');
            }
        }

        elseif ($slug === 'wallet') {
            // ✅ No need to deduct again
            if ($latest_order->payment_status !== 'paid') {
                $latest_order->update(['payment_status' => 'paid']);
            }
        }

        // Process the XUI Order if not already processed
        if ($latest_order->payment_status === 'paid' && !$latest_order->is_completed) {
            ProcessXuiOrder::dispatchWithDependencies($latest_order);
        }

        return view('livewire.success-page', [
            'order' => $latest_order,
        ]);
    }
}
