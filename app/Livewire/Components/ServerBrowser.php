<?php

namespace App\Livewire\Components;

use Livewire\Component;
use App\Models\Server;
use App\Models\ServerPlan;
use App\Models\ServerCategory;
use App\Models\ServerBrand;
use Livewire\WithPagination;
use Livewire\Attributes\On;
use App\Livewire\Traits\LivewireAlertV4;

class ServerBrowser extends Component
{
    use WithPagination;
    use LivewireAlertV4;

    // Real-time filtering properties
    /*
     * Public mutable filter + UI state properties.
     * Removed #[Reactive] attributes because we need to mutate these directly in tests
     * and component actions; Livewire reactive props are read-only mirrors.
     */
    public $searchTerm = '';
    public $selectedCountry = '';
    public $selectedCategory = '';
    public $selectedBrand = '';
    public $selectedProtocol = '';
    public $priceRange = [0, 1000];
    public $speedRange = [0, 1000];
    public $sortBy = 'location_first';
    public $viewMode = 'grid'; // grid, list, compact
    public $itemsPerPage = 12;

    // Real-time status tracking
    public $loading = false;
    public $serverHealth = [];
    public $lastUpdate = null;

    protected $listeners = [
        'refreshServers' => 'refreshServerData',
        'updateFilters' => 'applyFilters',
        'checkServerHealth' => 'checkAllServerHealth'
    ];

    public function mount()
    {
        $this->lastUpdate = now();
        $this->checkAllServerHealth();
    }

    public function render()
    {
        $this->loading = true;

        $query = ServerPlan::with(['server', 'category', 'brand'])
            ->where('server_plans.is_active', true);

        // Apply search filter
        if ($this->searchTerm) {
            $term = '%' . $this->searchTerm . '%';
            $query->where(function($q) use ($term) {
                $q->where('server_plans.name', 'like', $term)
                  ->orWhere('server_plans.description', 'like', $term)
                  ->orWhereHas('server', function($serverQuery) use ($term) {
                      $serverQuery->where('servers.name', 'like', $term)
                                   ->orWhere('servers.country', 'like', $term);
                  });
            });
        }

        // Apply country filter
        if ($this->selectedCountry) {
            $query->whereHas('server', function($q) {
                $q->where('country', $this->selectedCountry);
            });
        }

        // Apply category filter
        if ($this->selectedCategory) {
            $query->whereHas('category', function($q) {
                $q->where('slug', $this->selectedCategory);
            });
        }

        // Apply brand filter
        if ($this->selectedBrand) {
            $query->whereHas('brand', function($q) {
                $q->where('slug', $this->selectedBrand);
            });
        }

        // Apply protocol filter
        if ($this->selectedProtocol) {
            $query->where('protocol', $this->selectedProtocol);
        }

        // Apply price range filter
    $query->whereBetween('server_plans.price', $this->priceRange);

    // Apply speed range filter using bandwidth_mbps column (previously referenced non-existent max_speed)
    $query->whereBetween('server_plans.bandwidth_mbps', $this->speedRange);

        // Apply sorting
        switch ($this->sortBy) {
            case 'location_first':
            // Join servers for ordering by country but ensure we don't shadow plan columns (e.g. name)
            // Selecting server_plans.* prevents the servers.name column from overwriting server_plans.name
            $query->join('servers', 'server_plans.server_id', '=', 'servers.id')
                ->select('server_plans.*')
                ->where('servers.is_active', true)
                ->orderBy('servers.country')
                ->orderBy('server_plans.price');
                break;
            case 'price_low':
            $query->orderBy('server_plans.price', 'asc');
                break;
            case 'price_high':
            $query->orderBy('server_plans.price', 'desc');
                break;
            case 'speed_high':
            $query->orderBy('server_plans.bandwidth_mbps', 'desc');
                break;
            case 'popularity':
            $query->orderBy('server_plans.total_orders', 'desc');
                break;
            case 'newest':
            $query->orderBy('server_plans.created_at', 'desc');
                break;
            default:
            $query->orderBy('server_plans.is_featured', 'desc')
                ->orderBy('server_plans.price', 'asc');
        }

        $serverPlans = $query->paginate($this->itemsPerPage);

        $this->loading = false;

        // Get unique countries from servers
        $countries = Server::select('country', 'flag')
            ->distinct()
            ->whereNotNull('country')
            ->orderBy('country')
            ->get()
            ->map(function($server) {
                return [
                    'code' => $server->country,
                    'name' => $server->country,
                    'flag' => $server->flag
                ];
            });

        return view('livewire.components.server-browser', [
            'serverPlans' => $serverPlans,
            'countries' => $countries,
            'categories' => ServerCategory::all(),
            'brands' => ServerBrand::all(),
            'protocols' => ['VLESS', 'VMESS', 'TROJAN', 'SHADOWSOCKS', 'HYSTERIA2'],
            'serverHealth' => $this->serverHealth
        ]);
    }

    public function applyFilters($filters = [])
    {
        foreach ($filters as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }

        $this->resetPage();
        $this->refreshServerData();
    }

    public function clearFilters()
    {
        $this->searchTerm = '';
        $this->selectedCountry = '';
        $this->selectedCategory = '';
        $this->selectedBrand = '';
        $this->selectedProtocol = '';
        $this->priceRange = [0, 1000];
        $this->speedRange = [0, 1000];
        $this->sortBy = 'location_first';

        $this->resetPage();
        $this->refreshServerData();

        $this->alert('success', 'Filters cleared successfully!', [
            'position' => 'top-end',
            'timer' => 2000,
            'toast' => true,
        ]);
    }

    public function changeViewMode($mode)
    {
        $this->viewMode = $mode;
        $this->dispatch('viewModeChanged', mode: $mode);
    }

    public function changeItemsPerPage($count)
    {
        $this->itemsPerPage = $count;
        $this->resetPage();
    }

    #[On('refreshServers')]
    public function refreshServerData()
    {
        $this->lastUpdate = now();
        $this->checkAllServerHealth();
        $this->dispatch('serversRefreshed');
    }

    public function checkAllServerHealth()
    {
        // Get all active servers and check their health
        $servers = Server::where('is_active', true)->get();

        foreach ($servers as $server) {
            $this->serverHealth[$server->id] = $this->checkServerHealth($server);
        }
    }

    private function checkServerHealth($server)
    {
        // Basic health check - in production this would ping the actual server
        $health = [
            'status' => 'online',
            'response_time' => rand(10, 200), // ms
            'load' => rand(1, 100), // percentage
            'last_check' => now(),
            'uptime' => '99.9%'
        ];

        // Simulate some servers being offline occasionally
        if (rand(1, 20) === 1) {
            $health['status'] = 'offline';
            $health['response_time'] = null;
        }

        return $health;
    }

    public function getServerHealthStatus($serverId)
    {
        return $this->serverHealth[$serverId] ?? [
            'status' => 'unknown',
            'response_time' => null,
            'load' => 0,
            'last_check' => null,
            'uptime' => 'N/A'
        ];
    }

    public function toggleServerFavorite($serverPlanId)
    {
        // This would typically save to user favorites
        $this->dispatch('serverFavoriteToggled', serverPlanId: $serverPlanId);

        $this->alert('success', 'Favorite updated!', [
            'position' => 'top-end',
            'timer' => 1500,
            'toast' => true,
        ]);
    }

    public function quickAddToCart($serverPlanId)
    {
        $this->dispatch('addToCart', serverPlanId: $serverPlanId);

        $this->alert('success', 'Added to cart!', [
            'position' => 'bottom-end',
            'timer' => 2000,
            'toast' => true,
            'timerProgressBar' => true,
        ]);
    }

    // Real-time updates via polling
    public function pollForUpdates()
    {
        if ($this->lastUpdate && $this->lastUpdate->diffInMinutes(now()) >= 5) {
            $this->refreshServerData();
        }
    }
}
