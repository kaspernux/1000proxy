<?php

namespace App\Livewire\Partials;

use App\Models\User;
use Livewire\Component;
use App\Models\Customer;
use App\Helpers\CartManagement;

class Navbar extends Component
{
    public $total_count = 0;

    protected $listeners = ['update-cart-count' => 'updateCartCount'];

    public function mount()
    {
        $this->total_count = count(CartManagement::getCartItemsFromCookie());
    }
    #[On('updateCartCount')]
    public function updateCartCount($total_count)
    {
        $this->total_count = $total_count;
    }

    public function render()
    {
        return view('livewire.partials.navbar');
    }
}