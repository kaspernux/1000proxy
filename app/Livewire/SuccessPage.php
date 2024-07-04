<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Url;
use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Stripe\Stripe;
use Stripe\Checkout\Session;

#[Title('Success - 1000 PROXIES')]
class SuccessPage extends Component
{
    #[Url]
    public $session_id;

    #[Url]
    public $payment_id;

    public function render()
    {
        $latest_order = Order::with('invoice')->where('customer_id', auth()->user()->id)->latest()->first();

        if ($this->session_id) {
            Stripe::setApiKey(env('STRIPE_SECRET'));
            $session_info = Session::retrieve($this->session_id);

            if ($session_info->payment_status != 'paid') {
                $latest_order->payment_status = 'failed';
                $latest_order->save();
                return redirect()->route('cancel');
            } else if ($session_info->payment_status == 'paid') {
                $latest_order->payment_status = 'paid';
                $latest_order->save();
            }
        } elseif ($this->payment_id) {
            // Call the controller method to get payment status
            $response = Http::get(route('payment.status', ['orderId' => $latest_order->id]));

            if ($response->successful()) {
                $payment_status = $response->json('payment_status');

                if ($payment_status == 'finished') {
                    $latest_order->payment_status = 'paid';
                    $latest_order->save();
                } else {
                    $latest_order->payment_status = 'failed';
                    $latest_order->save();
                    return redirect()->route('cancel');
                }
            } else {
                $latest_order->payment_status = 'failed';
                $latest_order->save();
                return redirect()->route('cancel');
            }
        }

        return view('livewire.success-page', [
            'order' => $latest_order,
        ]);
    }
}