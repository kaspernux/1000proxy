<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;
use App\Models\ServerBrand;
use App\Models\ServerCategory;
use App\Models\ServerPlan;
use App\Models\Server;
use App\Models\Order;
use App\Models\Customer;
use App\Services\XUIService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Jantinnerezo\LivewireAlert\LivewireAlert;

#[Title('Home - Premium VPN & Proxy Solutions | 1000 PROXIES')]

class HomePage extends Component
{
    use LivewireAlert;

    public $searchTerm = '';
    public $selectedCategory = '';
    public $selectedBrand = '';
    public bool $showStats = true;
    public bool $showFeaturedPlans = true;

    // Loading states
    public $is_loading = false;
    public $is_searching = false;

    // Data properties
    public $brands;
    public $categories;
    public $featuredPlans;
    public $platformStats;

    protected $listeners = [
        'cartUpdated' => '$refresh',
        'userRegistered' => 'handleUserRegistered'
    ];

    protected function rules()
    {
        return [
            'searchTerm' => 'nullable|string|max:255',
            'selectedCategory' => 'nullable|string|max:255',
            'selectedBrand' => 'nullable|string|max:255',
        ];
    }

    public function mount()
    {
        // Initialize component state
        $this->showStats = true;
        $this->showFeaturedPlans = true;

        // Load data
        $this->brands = $this->loadBrands();
        $this->categories = $this->loadCategories();
        $this->featuredPlans = $this->loadFeaturedPlans();
        $this->platformStats = $this->loadPlatformStats();
    }

    public function loadBrands()
    {
        return Cache::remember('homepage.brands', 3600, function() {
            return ServerBrand::where('is_active', 1)
                ->withCount(['plans' => function($query) {
                    $query->where('is_active', true);
                }])
                ->having('plans_count', '>', 0)
                ->orderBy('name')
                ->get();
        });
    }

    public function loadCategories()
    {
        return Cache::remember('homepage.categories', 3600, function() {
            return ServerCategory::where('is_active', true)
                ->withCount(['plans' => function($query) {
                    $query->where('is_active', true);
                }])
                ->having('plans_count', '>', 0)
                ->orderBy('name')
                ->get();
        });
    }

    public function loadFeaturedPlans()
    {
        return Cache::remember('homepage.featured_plans', 1800, function() {
            return ServerPlan::where('is_active', true)
                ->where('is_featured', true)
                ->with(['brand', 'category', 'server'])
                ->orderBy('price')
                ->limit(6)
                ->get();
        });
    }

    public function loadPlatformStats()
    {
        return Cache::remember('homepage.platform_stats', 3600, function() {
            return [
                'total_users' => Customer::count(),
                'total_orders' => Order::whereIn('order_status', ['completed', 'processing'])->count(),
                'active_servers' => ServerPlan::where('is_active', true)->count(),
                'countries_count' => ServerPlan::where('server_plans.is_active', true)
                    ->join('servers', 'server_plans.server_id', '=', 'servers.id')
                    ->where('servers.is_active', true)
                    ->distinct('servers.country')
                    ->count('servers.country'),
                'avg_rating' => 4.8, // This could be calculated from reviews
                'total_reviews' => 12000, // This could be from a reviews table
            ];
        });
    }

    public function updatedSearchTerm()
    {
        $this->dispatch('searchUpdated', $this->searchTerm);
    }

    public function updatedSelectedCategory()
    {
        // Trigger re-computation of filtered servers
        $this->render();
    }

    public function updatedSelectedBrand()
    {
        // Trigger re-computation of filtered servers
        $this->render();
    }

    #[Computed]
    public function filteredServers()
    {
        $query = Server::where('is_active', true)
            ->where('status', 'up')
            ->with(['brand', 'category', 'plans' => function($q) {
                $q->where('is_active', true)->orderBy('price');
            }])
            ->whereHas('plans', function($q) {
                $q->where('is_active', true);
            });

        // Apply search term filter
        if (!empty($this->searchTerm)) {
            $searchTerm = $this->searchTerm;
            $query->where(function($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                  ->orWhere('country', 'like', "%{$searchTerm}%")
                  ->orWhereHas('brand', function($brandQuery) use ($searchTerm) {
                      $brandQuery->where('name', 'like', "%{$searchTerm}%");
                  })
                  ->orWhereHas('category', function($categoryQuery) use ($searchTerm) {
                      $categoryQuery->where('name', 'like', "%{$searchTerm}%");
                  });
            });
        }

        // Apply category filter
        if (!empty($this->selectedCategory)) {
            $query->where('server_category_id', $this->selectedCategory);
        }

        // Apply brand filter
        if (!empty($this->selectedBrand)) {
            $query->where('server_brand_id', $this->selectedBrand);
        }

        return $query->orderBy('name')->get();
    }

    public function selectCategory($categoryId)
    {
        $this->selectedCategory = $categoryId;
        $this->redirectToProducts();
    }

    public function selectBrand($brandId)
    {
        $this->selectedBrand = $brandId;
        $this->redirectToProducts();
    }

    public function searchPlans()
    {
        $this->is_searching = true;

        try {
            // Rate limiting for searches
            $key = 'search_plans.' . request()->ip();
            if (RateLimiter::tooManyAttempts($key, 10)) {
                $seconds = RateLimiter::availableAt($key) - time();
                throw new \Exception("Too many search attempts. Please try again in {$seconds} seconds.");
            }

            $this->validate(['searchTerm' => 'nullable|string|max:255']);

            RateLimiter::hit($key, 60); // 1-minute window

            $this->redirectToProducts();
            $this->is_searching = false;

        } catch (ValidationException $e) {
            $this->is_searching = false;
            throw $e;
        } catch (\Exception $e) {
            $this->is_searching = false;
            Log::error('Search plans error', [
                'error' => $e->getMessage(),
                'search_term' => $this->searchTerm,
                'ip' => request()->ip()
            ]);
            
            $this->alert('error', 'Search failed. Please try again.', [
                'position' => 'bottom-end',
                'timer' => 3000,
                'toast' => true,
            ]);
        }
    }

    private function redirectToProducts()
    {
        $params = array_filter([
            'search' => $this->searchTerm,
            'category' => $this->selectedCategory,
            'brand' => $this->selectedBrand,
        ]);

        $queryString = http_build_query($params);
        $url = '/products' . ($queryString ? '?' . $queryString : '');

        return redirect($url);
    }

    public function addToCart($planId)
    {
        try {
            // Rate limiting for cart additions
            $key = 'add_to_cart_home.' . request()->ip();
            if (RateLimiter::tooManyAttempts($key, 15)) {
                $seconds = RateLimiter::availableAt($key) - time();
                throw new \Exception("Too many cart additions. Please try again in {$seconds} seconds.");
            }

            $plan = ServerPlan::findOrFail($planId);

            if (!$plan->is_active) {
                $this->alert('error', 'This plan is currently unavailable.', [
                    'position' => 'bottom-end',
                    'timer' => 3000,
                    'toast' => true,
                ]);
                return;
            }

            RateLimiter::hit($key, 60); // 1-minute window

            // Add to cart using CartManagement helper for consistency
            $total_count = \App\Helpers\CartManagement::addItemToCart($planId);

            $this->dispatch('update-cart-count', total_count: $total_count)->to(\App\Livewire\Partials\Navbar::class);
            $this->alert('success', 'Plan added to cart successfully!', [
                'position' => 'bottom-end',
                'timer' => 3000,
                'toast' => true,
            ]);

            // Security logging
            Log::info('Plan added to cart from homepage', [
                'plan_id' => $planId,
                'plan_name' => $plan->name,
                'ip' => request()->ip(),
            ]);

        } catch (\Exception $e) {
            Log::error('Add to cart error from homepage', [
                'error' => $e->getMessage(),
                'plan_id' => $planId,
                'ip' => request()->ip()
            ]);
            
            $this->alert('error', 'Failed to add plan to cart. Please try again.', [
                'position' => 'bottom-end',
                'timer' => 3000,
                'toast' => true,
            ]);
        }
    }

    public function handleUserRegistered($userId)
    {
        // Clear cache to refresh stats
        Cache::forget('homepage.platform_stats');
        $this->showStats = true;
    }

    public function toggleStats()
    {
        $this->showStats = !$this->showStats;
    }

    public function toggleFeaturedPlans()
    {
        $this->showFeaturedPlans = !$this->showFeaturedPlans;
    }

    public function render()
    {
        return view('livewire.home-page', [
            'brands' => $this->brands,
            'categories' => $this->categories,
            'featuredPlans' => $this->featuredPlans,
            'stats' => $this->platformStats,
            'filteredServers' => $this->filteredServers,
        ]);
    }
}
