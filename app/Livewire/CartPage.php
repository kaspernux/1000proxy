<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\OrderItem;
use App\Models\ServerPlan;
use App\Models\ServerClient;
use Livewire\WithPagination;
use App\Models\ServerInbound;
use App\Models\ServerCategory;
use Livewire\Attributes\Title;
use App\Helpers\CartManagement;
use App\Livewire\Partials\Navbar;
use Jantinnerezo\LivewireAlert\LivewireAlert;


#[Title('Cart - 1000 PROXIES')]

class CartPage extends Component
{
    use LivewireAlert;

    public $order_items = [];
    public $grand_amount;

    public function mount(){
        $this->order_items = CartManagement::getCartItemsFromCookie();
        $this->grand_amount = CartManagement::calculateGrandTotal($this->order_items);

    }

    public function removeItem($server_plan_id){
        $this->order_items = CartManagement::removeCartItem($server_plan_id);
        $this->grand_amount = CartManagement::calculateGrandTotal($this->order_items);
        $this->dispatch('update-cart-count', total_count: count($this->order_items))->to(Navbar::class);
        $this->alert('success', 'Product deleted successfully!', [
            'position' => 'bottom-end',
            'timer' => '2000',
            'toast' => true,
            'timerProgressBar' => true,
        ]);
    }

    public function increaseQty($server_plan_id){
        $this->order_items = CartManagement::incrementQuantityToCartItem($server_plan_id);
        $this->grand_amount = CartManagement::calculateGrandTotal($this->order_items);
    }

    public function decreaseQty($server_plan_id){
        $this->order_items = CartManagement::decrementQuantityToCartItem($server_plan_id);
        $this->grand_amount = CartManagement::calculateGrandTotal($this->order_items);
    }

    public function render()
    {
        return view('livewire.cart-page');
    }
}