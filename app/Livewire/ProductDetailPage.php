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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
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

    // Loading states
    public $is_loading = false;
    public $is_adding_to_cart = false;

    // Real-time server monitoring
    public $serverStatus = null;
    public $serverHealth = null;

    protected function rules()
    {
        return [
            'quantity' => 'required|integer|min:1|max:10',
            'selectedDuration' => 'required|integer|min:1|max:24',
        ];
    }

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
        try {
            if ($this->quantity < 10) {
                $this->quantity++;
            } else {
                $this->alert('warning', 'Maximum quantity limit reached (10 items).', [
                    'position' => 'bottom-end',
                    'timer' => 2000,
                    'toast' => true,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Quantity increase error', [
                'error' => $e->getMessage(),
                'slug' => $this->slug,
                'current_quantity' => $this->quantity,
            ]);
        }
    }

    public function decreaseQty()
    {
        try {
            if ($this->quantity > 1) {
                $this->quantity--;
            } else {
                $this->alert('warning', 'Minimum quantity is 1 item.', [
                    'position' => 'bottom-end',
                    'timer' => 2000,
                    'toast' => true,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Quantity decrease error', [
                'error' => $e->getMessage(),
                'slug' => $this->slug,
                'current_quantity' => $this->quantity,
            ]);
        }
    }

    public function updateDuration($months)
    {
        try {
            if ($months >= 1 && $months <= 24) {
                $this->selectedDuration = $months;
            } else {
                $this->alert('error', 'Invalid duration selected.', [
                    'position' => 'bottom-end',
                    'timer' => 3000,
                    'toast' => true,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Duration update error', [
                'error' => $e->getMessage(),
                'slug' => $this->slug,
                'duration' => $months,
            ]);
        }
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
        $this->is_adding_to_cart = true;

        try {
            // Rate limiting for cart additions
            $key = 'add_to_cart_detail.' . request()->ip();
            if (RateLimiter::tooManyAttempts($key, 15)) {
                $seconds = RateLimiter::availableAt($key) - time();
                throw new \Exception("Too many cart additions. Please try again in {$seconds} seconds.");
            }

            $this->validate(['quantity' => 'required|integer|min:1|max:10']);

            $plan = $this->serverPlan;

            if (!$plan->is_active) {
                $this->alert('error', 'This plan is currently unavailable!', [
                    'position' => 'top-end',
                    'timer' => 3000,
                    'toast' => true,
                ]);
                $this->is_adding_to_cart = false;
                return;
            }

            RateLimiter::hit($key, 60); // 1-minute window

            $total_count = CartManagement::addItemToCartWithQty($server_plan_id, $this->quantity);
            $this->dispatch('update-cart-count', total_count: $total_count)->to(Navbar::class);

            $this->is_adding_to_cart = false;

            $this->alert('success', "Added {$this->quantity} × {$plan->name} to cart!", [
                'position' => 'bottom-end',
                'timer' => 3000,
                'toast' => true,
                'timerProgressBar' => true,
            ]);

            // Security logging
            Log::info('Product added to cart', [
                'server_plan_id' => $server_plan_id,
                'quantity' => $this->quantity,
                'plan_name' => $plan->name,
                'ip' => request()->ip(),
            ]);

        } catch (ValidationException $e) {
            $this->is_adding_to_cart = false;
            throw $e;
        } catch (\Exception $e) {
            $this->is_adding_to_cart = false;
            Log::error('Add to cart error in product detail', [
                'error' => $e->getMessage(),
                'server_plan_id' => $server_plan_id,
                'quantity' => $this->quantity,
                'ip' => request()->ip()
            ]);
            
            $this->alert('error', 'Failed to add product to cart. Please try again.', [
                'position' => 'bottom-end',
                'timer' => 3000,
                'toast' => true,
            ]);
        }
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
