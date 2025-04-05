<?php

namespace App\Livewire;

use App\Models\Server;
use Livewire\Component;
use App\Models\ServerPlan;
use App\Models\ServerClient;
use Livewire\WithPagination;
use App\Models\ServerInbound;
use App\Models\ServerCategory;
use App\Helpers\CartManagement;
use App\Livewire\Partials\Navbar;
use Jantinnerezo\LivewireAlert\LivewireAlert;

#[Title('Product Detail - 1000 PROXIES')]

class ProductDetailPage extends Component
{
    use LivewireAlert;

    public $slug;
    public $quantity = 1;

    public function mount($slug){
        $this->slug = $slug;
    }

    public function increaseQty(){
        $this->quantity++;
    }

    public function decreaseQty(){
        if($this->quantity > 1){
            $this->quantity--;
        }
    }

    // Add product to cart method

    public function addToCart($server_plan_id)
    {
        $total_count = CartManagement::addItemToCartWithQty($server_plan_id, $this->quantity);
        $this->dispatch('update-cart-count', total_count: $total_count)->to(Navbar::class);

        $this->alert('success', 'Product added successfully!', [
            'position' => 'bottom-end',
            'timer' => '2000',
            'toast' => true,
            'timerProgressBar' => true,
        ]);
    }

    public function render()
    {
        return view('livewire.product-detail-page', [
            'serverPlan' => ServerPlan::where('slug', $this->slug)->firstOrFail(),
        ]);


   }
}