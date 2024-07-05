<?php

namespace App\Livewire;

use App\Models\Order;
use App\Models\Invoice;
use Livewire\Component;
use App\Models\OrderItem;
use App\Models\ServerPlan;


#[Title('Order Detail - 1000 PROXIES')]

class MyOrderDetailPage extends Component
{
    public $order_id;

    public function mount($order_id){
        $this->order_id = $order_id;

    }
    public function render()
    {
        $order_items = OrderItem::with('serverPlan')->where('order_id', $this->order_id)->get();
        $invoice = Invoice::where('order_id', $this->order_id)->first();
        $order = Order::where('id', $this->order_id)->first();
        return view('livewire.my-order-detail-page', [
            'order_items' => $order_items,
            'invoice' => $invoice,
            'order' => $order,
        ]);
    }
}