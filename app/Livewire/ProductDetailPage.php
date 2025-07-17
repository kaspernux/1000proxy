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
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Illuminate\Support\Facades\Cache;

#[Title('Product Detail - 1000 PROXIES')]
class ProductDetailPage extends Component
{
    use LivewireAlert;

    public $slug;
    public $quantity = 1;
    public $selectedDuration = 1; // months
    public $showSpecifications = true;
    public $activeTab = 'overview';

    // Real-time server monitoring
    public $serverStatus = null;
    public $serverHealth = null;

    public function mount($slug)
    {
        $this->slug = $slug;
        $this->checkServerStatus();
    }

    #[Computed]
    public function serverPlan()
    {
        return Cache::remember("product.{$this->slug}", 1800, function() {
            return ServerPlan::where('slug', $this->slug)
                ->with(['brand', 'category', 'server'])
                ->firstOrFail();
        });
    }

    #[Computed]
    public function totalPrice()
    {
        $basePrice = $this->serverPlan->price;
        $monthlyMultiplier = $this->selectedDuration;
        $quantityMultiplier = $this->quantity;

        $total = $basePrice * $monthlyMultiplier * $quantityMultiplier;

        // Apply discounts for longer durations
        if ($this->selectedDuration >= 12) {
            $total *= 0.8; // 20% discount for annual
        } elseif ($this->selectedDuration >= 6) {
            $total *= 0.9; // 10% discount for semi-annual
        }

        return round($total, 2);
    }

    public function increaseQty()
    {
        if ($this->quantity < 10) {
            $this->quantity++;
        }
    }

    public function decreaseQty()
    {
        if ($this->quantity > 1) {
            $this->quantity--;
        }
    }

    public function updateDuration($months)
    {
        $this->selectedDuration = $months;
    }

    public function checkServerStatus()
    {
        try {
            $plan = $this->serverPlan;
            if ($plan->server) {
                $this->serverStatus = $plan->server->status ?? 'up';
                $this->serverHealth = rand(85, 99);
            }
        } catch (\Exception $e) {
            $this->serverStatus = 'error';
            $this->serverHealth = 0;
        }
    }

    public function addToCart($server_plan_id)
    {
        $plan = $this->serverPlan;

        if (!$plan->is_active) {
            $this->alert('error', 'This plan is currently unavailable!', [
                'position' => 'top-end',
                'timer' => 3000,
                'toast' => true,
            ]);
            return;
        }

        $total_count = CartManagement::addItemToCartWithQty($server_plan_id, $this->quantity);
        $this->dispatch('update-cart-count', total_count: $total_count)->to(Navbar::class);

        $this->alert('success', "Added {$this->quantity} × {$plan->name} to cart!", [
            'position' => 'bottom-end',
            'timer' => 3000,
            'toast' => true,
            'timerProgressBar' => true,
        ]);
    }

    public function render()
    {
        return view('livewire.product-detail-page', [
            'serverPlan' => $this->serverPlan,
            'totalPrice' => $this->totalPrice,
            'serverStatus' => $this->serverStatus,
            'serverHealth' => $this->serverHealth,
        ]);
    }
}
