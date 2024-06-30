<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Customer;
use App\Models\PaymentMethod;
use App\Helpers\CartManagement;
use Illuminate\Support\Facades\Auth;
use Jantinnerezo\LivewireAlert\LivewireAlert;

#[Title('Checkout - 1000 PROXIES')]
class CheckoutPage extends Component
{
    use LivewireAlert;

    public $name;
    public $email;
    public $telegram_id;
    public $phone;
    public $payment_method;
    public $customer;
    public $paymentMethods;

    public function mount()
    {
        // Assuming the authenticated user is the customer
        $this->customer = Auth::guard('customer')->user();

        // Check if customer is not null and then assign properties
        if ($this->customer) {
            $this->name = $this->customer->name;
            $this->email = $this->customer->email;
            $this->telegram_id = $this->customer->telegram_id;
            $this->phone = $this->customer->phone;
        }

        // Fetch the available payment methods
        $this->paymentMethods = PaymentMethod::all();
    }

    public function updatedName($value)
    {
        if ($this->customer) {
            $this->customer->update(['name' => $value]);
        }
    }

    public function updatedPhone($value)
    {
        if ($this->customer) {
            $this->customer->update(['phone' => $value]);
        }
    }

    public function updatedTelegramId($value)
    {
        if ($this->customer) {
            $this->customer->update(['telegram_id' => $value]);
        }
    }

    public function updatedEmail()
    {
        // Set a flash message indicating email update is not allowed
        $this->alert('error', 'Updating email is not allowed now!', [
            'position' => 'bottom-end',
            'timer' => '2000',
            'toast' => true,
            'timerProgressBar' => true,
        ]);
        // Reset the email property to the original value
        $this->email = $this->customer->email;
    }

    public function placeOrder(){
        $this->validate([
            'name' => 'required',
            'email' => 'required',
            'phone' => 'nullable',
            'telegram_id' => 'nullable',
            'payment_method' => 'required', // Add your specific validation rules for payment method selection
        ]);
    }

    public function render()
    {
        $order_items = CartManagement::getCartItemsFromCookie();
        $grand_amount = CartManagement::calculateGrandTotal($order_items);

        return view('livewire.checkout-page', [
            'order_items' => $order_items,
            'grand_amount' => $grand_amount,
            'customer' => $this->customer,
            'paymentMethods' => $this->paymentMethods,
            'staticPaymentMethods' => ['stripe' => 'Stripe'],
        ]);
    }
}
