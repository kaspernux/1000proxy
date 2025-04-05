<?php

namespace App\Livewire;

use App\Models\Order;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('My Orders - 1000 PROXIES')]

class MyOrdersPage extends Component
{
    use WithPagination;

    public function render()
    {
        $my_orders = Order::where('customer_id', auth()->id())->latest()->paginate(12);
        return view('livewire.my-orders-page', [
            'orders' => $my_orders,
        ]);
    }
}