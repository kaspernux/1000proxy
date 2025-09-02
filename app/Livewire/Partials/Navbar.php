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
    public $tick = 0; // dummy reactive counter to force rerender when needed
    // Accept terms flag present on some pages; declared here to avoid Livewire
    // "Public property not found" errors when other components update it.
    public $terms_accepted = false;

    protected $listeners = [
    'update-cart-count' => 'updateCartCount',
    'cart-updated' => 'refreshCartCount',
    'cartUpdated' => 'refreshCartCount',
    'refresh-cart-count' => 'refreshCartCount',
    'wallet-updated' => 'refreshWallet'
    ];

    public function mount()
    {
        $this->total_count = count(CartManagement::getCartItemsFromCookie());
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
        // Only update if count has changed
        $new_count = count(CartManagement::getCartItemsFromCookie());
        if ($new_count !== $this->total_count) {
            $this->total_count = $new_count;
            $this->dispatch('cart-count-updated', count: $this->total_count);
        }
    }

    public function render()
    {
        // Do not refresh cart count on every render to avoid unnecessary calls
        return view('livewire.partials.navbar');
    }

    public function refreshWallet(): void
    {
        // Bump a reactive counter to trigger a re-render so wallet balance reflects DB changes
        $this->tick++;
    }
}