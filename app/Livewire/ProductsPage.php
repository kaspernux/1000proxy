<?php

namespace App\Livewire;

use App\Models\Server;
use Livewire\Component;
use App\Models\ServerPlan;
use App\Models\ServerBrand;
use Livewire\WithPagination;
use App\Models\ServerCategory;
use App\Helpers\CartManagement;
use App\Livewire\Partials\Navbar;
use Livewire\Attributes\Url;
use Livewire\Attributes\Title;
use Jantinnerezo\LivewireAlert\LivewireAlert;

#[Title('Products Page - 1000 PROXIES')]
class ProductsPage extends Component
{
    use WithPagination;
    use LivewireAlert;

    // Advanced filtering system with location-first sorting
    #[Url]
    public $selected_categories = [];

    #[Url]
    public $selected_brands = [];

    #[Url]
    public $selected_countries = [];

    #[Url]
    public $selected_protocols = [];

    #[Url]
    public $featured = [];

    #[Url]
    public $on_sale = [];

    #[Url]
    public $ip_version = ''; // ipv4, ipv6, both

    #[Url]
    public $server_status = 'online'; // online, offline, all

    public $price_min = 0;
    public $price_max = 1000;
    public $bandwidth_min = 0;
    public $bandwidth_max = 1000;
    public $sortOrder = 'location_first';

    protected $queryString = [
        'selected_categories' => ['except' => []],
        'selected_brands' => ['except' => []],
        'selected_countries' => ['except' => []],
        'selected_protocols' => ['except' => []],
        'featured' => ['except' => []],
        'on_sale' => ['except' => []],
        'ip_version' => ['except' => ''],
        'server_status' => ['except' => 'online'],
        'price_min' => ['except' => 0],
        'price_max' => ['except' => 1000],
        'bandwidth_min' => ['except' => 0],
        'bandwidth_max' => ['except' => 1000],
        'sortOrder' => ['except' => 'location_first'],
    ];

    // Add product to cart method
    public function addToCart($server_plan_id)
    {
        $total_count = CartManagement::addItemToCart($server_plan_id);
        $this->dispatch('update-cart-count', total_count: $total_count)->to(Navbar::class);

        $this->alert('success', 'Product added successfully!', [
            'position' => 'bottom-end',
            'timer' => '2000',
            'toast' => true,
            'timerProgressBar' => true,
        ]);
    }

    // Apply filters method
    public function applyFilters()
    {
        // Trigger re-render with updated filters
        $this->render();
    }

    // Reset all filters to default values
    public function resetFilters()
    {
        $this->selected_categories = [];
        $this->selected_brands = [];
        $this->selected_countries = [];
        $this->selected_protocols = [];
        $this->featured = [];
        $this->on_sale = [];
        $this->ip_version = '';
        $this->server_status = 'online';
        $this->price_min = 0;
        $this->price_max = 1000;
        $this->bandwidth_min = 0;
        $this->bandwidth_max = 1000;
        $this->sortOrder = 'location_first';

        $this->alert('success', 'Filters reset successfully!', [
            'position' => 'bottom-end',
            'timer' => '2000',
            'toast' => true,
            'timerProgressBar' => true,
        ]);
    }

    public function render()
    {
        $serverQuery = ServerPlan::query()->where('server_plans.is_active', 1);

        // Location-first filtering
        if (!empty($this->selected_countries)) {
            $serverQuery->whereHas('server', function ($query) {
                $query->whereIn('country', $this->selected_countries);
            });
        }

        // Category filtering (Gaming, Streaming, General)
        if (!empty($this->selected_categories)) {
            $serverQuery->whereIn('server_plans.server_category_id', $this->selected_categories);
        }

        // Brand filtering (different X-UI server instances)
        if (!empty($this->selected_brands)) {
            $serverQuery->whereHas('server', function ($query) {
                $query->whereIn('servers.server_brand_id', $this->selected_brands);
            });
        }

        // Protocol filtering (VLESS, VMESS, TROJAN, SHADOWSOCKS)
        if (!empty($this->selected_protocols)) {
            $serverQuery->where(function ($query) {
                foreach ($this->selected_protocols as $protocol) {
                    $query->orWhereJsonContains('protocols', $protocol);
                }
            });
        }

        // Price range filtering
        if ($this->price_min > 0) {
            $serverQuery->where('price', '>=', $this->price_min);
        }
        if ($this->price_max < 1000) {
            $serverQuery->where('price', '<=', $this->price_max);
        }

        // Bandwidth filtering
        if ($this->bandwidth_min > 0) {
            $serverQuery->where('bandwidth_limit', '>=', $this->bandwidth_min);
        }
        if ($this->bandwidth_max < 1000) {
            $serverQuery->where('bandwidth_limit', '<=', $this->bandwidth_max);
        }

        // IP version filtering
        if ($this->ip_version === 'ipv4') {
            $serverQuery->where('ipv4_support', true);
        } elseif ($this->ip_version === 'ipv6') {
            $serverQuery->where('ipv6_support', true);
        } elseif ($this->ip_version === 'both') {
            $serverQuery->where('ipv4_support', true)->where('ipv6_support', true);
        }

        // Server status filtering
        if ($this->server_status === 'online') {
            $serverQuery->whereHas('server', function ($query) {
                $query->where('status', 'up');
            });
        } elseif ($this->server_status === 'offline') {
            $serverQuery->whereHas('server', function ($query) {
                $query->where('status', 'down');
            });
        }

        // Featured and sale filtering
        if ($this->featured) {
            $serverQuery->where('is_featured', 1);
        }

        if ($this->on_sale) {
            $serverQuery->where('on_sale', 1);
        }

        // Advanced sorting with location-first priority
        if ($this->sortOrder === 'location_first') {
            $serverQuery->join('servers', 'server_plans.server_id', '=', 'servers.id')
                        ->where('servers.is_active', true)
                        ->orderBy('servers.country', 'asc')
                        ->orderBy('server_plans.created_at', 'desc')
                        ->select('server_plans.*');
        } elseif ($this->sortOrder === 'price_low') {
            $serverQuery->orderBy('price', 'asc');
        } elseif ($this->sortOrder === 'price_high') {
            $serverQuery->orderBy('price', 'desc');
        } elseif ($this->sortOrder === 'speed') {
            $serverQuery->orderBy('bandwidth_limit', 'desc');
        } elseif ($this->sortOrder === 'popularity') {
            $serverQuery->orderBy('popularity_score', 'desc');
        } else {
            $serverQuery->orderBy('created_at', 'desc');
        }

        // Get unique countries for location filter
        $countries = Server::where('status', 'up')
                          ->distinct()
                          ->pluck('country')
                          ->filter()
                          ->sort()
                          ->values();

        // Get available protocols
        $protocols = ['VLESS', 'VMESS', 'TROJAN', 'SHADOWSOCKS'];

        return view('livewire.products-page', [
            'serverPlans' => $serverQuery->paginate(9),
            'brands'      => ServerBrand::where('is_active', 1)->get(['id', 'name', 'slug']),
            'categories'  => ServerCategory::where('is_active', 1)->get(['id', 'name', 'slug', 'image']),
            'countries'   => $countries,
            'protocols'   => $protocols,
            'servers'     => Server::where('status', 'up')->get(['id', 'country']),
        ]);
    }
}
