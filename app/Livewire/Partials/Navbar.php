<?php

namespace App\Livewire\Partials;

use App\Models\User;
use Livewire\Component;
use App\Models\Customer;
use App\Helpers\CartManagement;
use Livewire\Attributes\On;

class Navbar extends Component
{
    public $total_count = 0;

    protected $listeners = [
        'update-cart-count' => 'updateCartCount',
        'cart-updated' => 'refreshCartCount',
        'cartUpdated' => 'refreshCartCount'
    ];

    public function mount()
    {
        $this->refreshCartCount();
    }
    
    #[On('update-cart-count')]
    public function updateCartCount($total_count = null)
    {
        if ($total_count !== null) {
            $this->total_count = $total_count;
        } else {
            $this->refreshCartCount();
        }
        
        // Dispatch browser event to update any other cart displays
        $this->dispatch('cart-count-updated', count: $this->total_count);
    }

    public function refreshCartCount()
    {
        $this->total_count = count(CartManagement::getCartItemsFromCookie());
    }

    public function render()
    {
        $this->refreshCartCount();
        return view('livewire.partials.navbar');
    }
}