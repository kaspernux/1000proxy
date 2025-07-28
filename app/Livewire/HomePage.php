<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Title;
use Livewire\Attributes\Computed;
use App\Models\ServerBrand;
use App\Models\ServerCategory;
use App\Models\ServerPlan;
use App\Models\Order;
use App\Models\User;
use App\Services\XUIService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

#[Title('Home - Premium VPN & Proxy Solutions | 1000 PROXIES')]

class HomePage extends Component
{
    public $searchTerm = '';
    public $selectedCategory = '';
    public $selectedBrand = '';
    public bool $showStats = true;
    public bool $showFeaturedPlans = true;

    // Data properties
    public $brands;
    public $categories;
    public $featuredPlans;
    public $platformStats;

    protected $listeners = [
        'cartUpdated' => '$refresh',
        'userRegistered' => 'handleUserRegistered'
    ];

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
                'total_users' => User::where('role', 'customer')->count(),
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
        $this->redirectToProducts();
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
        $plan = ServerPlan::findOrFail($planId);

        if (!$plan->is_active) {
            $this->dispatch('toast', [
                'type' => 'error',
                'message' => 'This plan is currently unavailable.'
            ]);
            return;
        }

        // Add to cart logic here
        session()->push('cart.items', [
            'plan_id' => $planId,
            'quantity' => 1,
            'added_at' => now(),
        ]);

        $this->dispatch('cartUpdated');
        $this->dispatch('toast', [
            'type' => 'success',
            'message' => 'Plan added to cart successfully!'
        ]);
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
        ]);
    }
}
