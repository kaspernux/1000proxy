<?php

namespace App\Filament\Customer\Pages;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Grid;
use Filament\Pages\Page;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use App\Models\Server;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use BackedEnum;
use App\Helpers\CartManagement;

class ServerBrowsing extends Page implements HasForms
{
    use InteractsWithForms;
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-server';
    protected static ?string $navigationLabel = 'Browse Servers';
    protected string $view = 'filament.customer.pages.server-browsing';
    protected static ?int $navigationSort = 5;

    public $filters = [
        'country' => null,
        'type' => null,
        'status' => null,
        'price_min' => null,
        'price_max' => null,
        'search' => null,
        'sort' => 'price_asc',
        'favorites_only' => false,
        'plan_days' => null,
    ];

    public $showFilters = false;
    public $showAdvancedFilters = false;
    public $perPage = 12;
    public $servers = [];
    public $countries = [];
    public $topCountries = [];
    public $page = 1;
    public $hasMore = true;
    public bool $compactView = false;
    public $selectedPlans = [];
    // Stats for header cards
    public int $statTotalServers = 0;
    public int $statActiveNow = 0;
    public int $statCountries = 0;
    public float $statStartingPrice = 0.0;

    // Keep filters in the URL so links are shareable/bookmarkable
    protected $queryString = [
        'filters.country' => ['except' => null],
        'filters.type' => ['except' => null],
        'filters.status' => ['except' => null],
        'filters.price_min' => ['except' => null],
        'filters.price_max' => ['except' => null],
        'filters.search' => ['except' => null],
        'filters.sort' => ['except' => 'price_asc'],
        'filters.favorites_only' => ['except' => false],
        'filters.plan_days' => ['except' => null],
        'compactView' => ['except' => false],
        'page' => ['except' => 1],
    ];

    public function mount()
    {
        $this->loadCountries();
    $this->loadTopCountries();
        $this->loadServers();
        $this->computeStats();
        // If initial load yields no results, reset filters to defaults and show all available servers with active plans
        if (empty($this->servers)) {
            $this->resetFilters();
        }
    }

    /**
     * Compute global stats shown in header cards using the same availability rules as listings.
     */
    protected function computeStats(): void
    {
        // Use the same filters as the listing to avoid mismatches in counts
        $q = $this->buildFilteredServerQuery();

        $this->statTotalServers = (clone $q)->count();
        $this->statActiveNow    = (clone $q)->whereIn('servers.status', ['active', 'up'])->count();
        $this->statCountries    = (clone $q)->distinct('servers.country')->count('servers.country');

        // Compute min price among active plans for the filtered servers
        $serverIds = (clone $q)->pluck('servers.id');
        if ($serverIds->isEmpty()) {
            $this->statStartingPrice = 0.0;
        } else {
            $this->statStartingPrice = (float) \App\Models\ServerPlan::query()
                ->where('server_plans.is_active', true)
                ->whereIn('server_id', $serverIds)
                ->min('price') ?? 0.0;
        }
    }

    /**
     * Build the base server query with the same availability and filters as the listing, but without sorting/pagination.
     */
    protected function buildFilteredServerQuery(): Builder
    {
        $query = Server::query()
            ->withAvg('serverRatings', 'rating')
            ->where(function($q) {
                $q->where('servers.status','active')
                  ->orWhere('servers.status','up')
                  ->orWhere('servers.is_active', true)
                  ->orWhereNull('servers.status');
            })
            ->whereHas('plans', function($q) {
                $q->where('is_active', true);
            });

        // Apply filters (mirror of loadServers, excluding sorting)
        if ($this->filters['country']) {
            $query->where('servers.country', $this->filters['country']);
        }
        if ($this->filters['type']) {
            $query->where('servers.type', $this->filters['type']);
        }
        if ($this->filters['status']) {
            $query->where('servers.status', $this->filters['status']);
        }
        if ($this->filters['price_min']) {
            $query->whereHas('plans', function($q) {
                $q->where('price', '>=', $this->filters['price_min'])
                  ->where('is_active', true);
            });
        }
        if ($this->filters['price_max']) {
            $query->whereHas('plans', function($q) {
                $q->where('price', '<=', $this->filters['price_max'])
                  ->where('is_active', true);
            });
        }
        if ($this->filters['plan_days']) {
            $days = (int) $this->filters['plan_days'];
            $query->whereHas('plans', function ($q) use ($days) {
                $q->where('days', $days)->where('is_active', true);
            });
        }
        if ($this->filters['search']) {
            $query->where(function (Builder $q) {
                $q->where('servers.name', 'like', '%' . $this->filters['search'] . '%')
                  ->orWhere('servers.description', 'like', '%' . $this->filters['search'] . '%')
                  ->orWhere('servers.country', 'like', '%' . $this->filters['search'] . '%');
            });
        }
        if ($this->filters['favorites_only']) {
            $customerId = Auth::guard('customer')->id();
            $favorites = (array) (session()->get("favorites.customer_{$customerId}") ?? []);
            if (!empty($favorites)) {
                $query->whereIn('servers.id', $favorites);
            } else {
                $query->whereRaw('1=0');
            }
        }

        return $query;
    }

    public function form($form)
    {
        // Bind form state directly to the existing $filters array
        return $form
            ->schema($this->getFormSchema())
            ->statePath('filters');
    }

    public function getFormSchema(): array
    {
        return [
            Grid::make(2)
                ->schema([
                    TextInput::make('search')
                        ->label('Search Servers')
                        ->placeholder('Search by name, country, or description...')
                        ->suffixIcon('heroicon-m-magnifying-glass')
                        ->suffixIconColor('primary')
                        ->live(debounce: 500)
                        ->columnSpanFull(),

                    Select::make('country')
                        ->label('Country / Region')
                        ->placeholder('All Countries')
                        ->options($this->countries)
                        ->searchable()
                        ->suffixIcon('heroicon-m-globe-alt')
                        ->live(),

                    Select::make('type')
                        ->label('Server Type')
                        ->placeholder('All Types')
                        ->options([
                            'dedicated' => 'Dedicated Proxy',
                            'shared' => 'Shared Proxy',
                            'rotating' => 'Rotating Proxy',
                            'static' => 'Static Proxy',
                        ])
                        ->suffixIcon('heroicon-m-server')
                        ->live(),

                    TextInput::make('price_min')
                        ->label('Min Price ($)')
                        ->numeric()
                        ->placeholder('0')
                        ->prefix('$')
                        ->live(debounce: 500),

                    TextInput::make('price_max')
                        ->label('Max Price ($)')
                        ->numeric()
                        ->placeholder('1000')
                        ->prefix('$')
                        ->live(debounce: 500),

                    Select::make('sort')
                        ->label('Sort By')
                        ->options([
                            'price_asc' => '💰 Price: Low to High',
                            'price_desc' => '💎 Price: High to Low',
                            'name_asc' => '🔤 Name: A to Z',
                            'name_desc' => '🔡 Name: Z to A',
                            'country_asc' => '🌍 Country: A to Z',
                            'rating_desc' => '⭐ Rating: High to Low',
                        ])
                        ->default('price_asc')
                        ->suffixIcon('heroicon-m-bars-arrow-down')
                        ->live()
                        ->columnSpanFull(),
                ]),
        ];
    }

    public function loadCountries()
    {
        $this->countries = Server::query()
            ->where(function($q){
                                $q->where('servers.status','active')
                                    ->orWhere('servers.status','up')
                                    ->orWhere('servers.is_active', true)
                                    ->orWhereNull('servers.status');
            })
                        ->whereNotNull('servers.country')
                        ->distinct()
                        ->pluck('country', 'country')
            ->toArray();
    }

    public function loadTopCountries(): void
    {
        $this->topCountries = Server::query()
                        ->select('country', \DB::raw('COUNT(*) as total'))
            ->where(function($q){
                                $q->where('servers.status','active')
                                    ->orWhere('servers.status','up')
                                    ->orWhere('servers.is_active', true)
                                    ->orWhereNull('servers.status');
            })
                        ->whereNotNull('servers.country')
                        ->groupBy('country')
            ->orderByDesc('total')
            ->limit(6)
            ->pluck('country')
            ->toArray();
    }

    public function loadServers($append = false)
    {
                $query = Server::query()
                        ->with(['reviews', 'plans' => function($q) {
                $q->where('is_active', true)->orderBy('price', 'asc');
            }])
            ->withAvg('serverRatings', 'rating')
                        // Consider servers usable if status is 'active' or 'up' or explicitly is_active=true (legacy),
                        // and always require at least one active plan via whereHas below.
                        ->where(function($q) {
                                $q->where('servers.status', 'active')
                                    ->orWhere('servers.status', 'up')
                                    ->orWhere('servers.is_active', true)
                                    ->orWhereNull('servers.status');
                        })
            ->whereHas('plans', function($q) {
                $q->where('is_active', true);
            });

        // Apply filters
        if ($this->filters['country']) {
            $query->where('servers.country', $this->filters['country']);
        }

        if ($this->filters['type']) {
            $query->where('servers.type', $this->filters['type']);
        }

        if ($this->filters['status']) {
            $query->where('servers.status', $this->filters['status']);
        }

        if ($this->filters['price_min']) {
            $query->whereHas('plans', function($q) {
                $q->where('price', '>=', $this->filters['price_min'])
                  ->where('is_active', true);
            });
        }

        if ($this->filters['price_max']) {
            $query->whereHas('plans', function($q) {
                $q->where('price', '<=', $this->filters['price_max'])
                  ->where('is_active', true);
            });
        }

        if ($this->filters['plan_days']) {
            $days = (int) $this->filters['plan_days'];
            $query->whereHas('plans', function ($q) use ($days) {
                $q->where('days', $days)->where('is_active', true);
            });
        }

                if ($this->filters['search']) {
                        $query->where(function (Builder $q) {
                                $q->where('servers.name', 'like', '%' . $this->filters['search'] . '%')
                                    ->orWhere('servers.description', 'like', '%' . $this->filters['search'] . '%')
                                    ->orWhere('servers.country', 'like', '%' . $this->filters['search'] . '%');
            });
        }

        if ($this->filters['favorites_only']) {
            $customerId = Auth::guard('customer')->id();
            $favorites = (array) (session()->get("favorites.customer_{$customerId}") ?? []);
            if (!empty($favorites)) {
                $query->whereIn('servers.id', $favorites);
            } else {
                // No favorites, return empty result
                $query->whereRaw('1=0');
            }
        }

        // Apply sorting
        switch ($this->filters['sort']) {
            case 'price_asc':
                $query->leftJoin('server_plans', function($join) {
                    $join->on('servers.id', '=', 'server_plans.server_id')
                         ->where('server_plans.is_active', true);
                })
                ->select('servers.*')
                ->orderBy('server_plans.price', 'asc');
                break;
            case 'price_desc':
                $query->leftJoin('server_plans', function($join) {
                    $join->on('servers.id', '=', 'server_plans.server_id')
                         ->where('server_plans.is_active', true);
                })
                ->select('servers.*')
                ->orderBy('server_plans.price', 'desc');
                break;
            case 'name_asc':
                $query->orderBy('servers.name', 'asc');
                break;
            case 'name_desc':
                $query->orderBy('servers.name', 'desc');
                break;
            case 'country_asc':
                $query->orderBy('servers.country', 'asc');
                break;
            case 'rating_desc':
                $query->orderBy('server_ratings_avg_rating', 'desc');
                break;
        }

        $servers = $query->paginate($this->perPage, ['*'], 'page', $this->page);

        if ($append) {
            $this->servers = array_merge($this->servers, $servers->items());
        } else {
            $this->servers = $servers->items();
        }

        $this->hasMore = $servers->hasMorePages();

        // Initialize default selected plan for each loaded server (cheapest active)
        foreach ($servers->items() as $srv) {
            $plan = $srv->plans->first();
            if ($plan && empty($this->selectedPlans[$srv->id])) {
                $this->selectedPlans[$srv->id] = $plan->id;
            }
        }
    }

    public function updatedFilters()
    {
        $this->page = 1;
        $this->loadServers();
    $this->computeStats();
    }

    public function loadMore()
    {
        if ($this->hasMore) {
            $this->page++;
            $this->loadServers(true);
        }
    }

    public function toggleFilters()
    {
        $this->showFilters = !$this->showFilters;
    }

    public function toggleAdvancedFilters()
    {
        $this->showAdvancedFilters = !$this->showAdvancedFilters;
    }

    public function toggleCompactView(): void
    {
        $this->compactView = ! $this->compactView;
    }

    /**
     * Clear a single filter key to null and refresh results.
     */
    public function clearFilter(string $key): void
    {
        if (array_key_exists($key, $this->filters)) {
            $this->filters[$key] = null;
            $this->updatedFilters();
        }
    }

    public function resetFilters()
    {
        $this->filters = [
            'country' => null,
            'type' => null,
            'status' => null,
            'price_min' => null,
            'price_max' => null,
            'search' => null,
            'sort' => 'price_asc',
            'favorites_only' => false,
            'plan_days' => null,
        ];
        $this->page = 1;
    $this->loadServers();
    $this->computeStats();
    }

    public function toggleFavorite($serverId)
    {
    $customer = Auth::guard('customer')->user();
    $server = Server::find($serverId);

        if (!$server) {
            return;
        }

        $key = "favorites.customer_{$customer->id}";
        $favorites = (array) (session()->get($key) ?? []);
        if (in_array($serverId, $favorites, true)) {
            $favorites = array_values(array_filter($favorites, fn ($id) => (int) $id !== (int) $serverId));
            session()->put($key, $favorites);
            Notification::make()
                ->title('Removed from favorites')
                ->body("Server '{$server->name}' has been removed from your favorites.")
                ->warning()
                ->send();
        } else {
            $favorites[] = (int) $serverId;
            $favorites = array_values(array_unique($favorites));
            session()->put($key, $favorites);
            Notification::make()
                ->title('Added to favorites')
                ->body("Server '{$server->name}' has been added to your favorites.")
                ->success()
                ->send();
        }

        // Reload servers to update favorite status
        $this->loadServers();
    }

    public function isFavorite($serverId)
    {
    $customerId = Auth::guard('customer')->id();
    $favorites = (array) (session()->get("favorites.customer_{$customerId}") ?? []);
    return in_array((int) $serverId, $favorites, true);
    }

    public function selectServer($serverId)
    {
        $server = Server::find($serverId);

        if (!$server) {
            Notification::make()
                ->title('Server not found')
                ->body('The selected server could not be found.')
                ->danger()
                ->send();
            return;
        }

    // Store selected server in session for checkout
        session(['selected_server_id' => $serverId]);

        Notification::make()
            ->title('Server Selected')
            ->body("Server '{$server->name}' has been selected. Proceed to checkout.")
            ->success()
            ->send();

    // Redirect to checkout (web route)
    return redirect()->route('checkout');
    }

    /**
     * Add the cheapest active plan for a server to cart and go to checkout.
     */
    public function orderServer($serverId)
    {
        $server = Server::with(['plans' => function ($q) {
            $q->where('is_active', true)->orderBy('price', 'asc');
        }])->find($serverId);

        if (!$server) {
            Notification::make()
                ->title('Server not found')
                ->danger()
                ->send();
            return;
        }

    // Use selected plan if provided, else fallback to cheapest
    $selectedPlanId = isset($this->selectedPlans[$server->id]) ? (int) $this->selectedPlans[$server->id] : 0;
    $plan = $server->plans->firstWhere('id', $selectedPlanId) ?? $server->plans->first();
        if (!$plan) {
            Notification::make()
                ->title('No active plans')
                ->body('This server has no purchasable plans at the moment.')
                ->warning()
                ->send();
            return;
        }

        // Add to cart using existing cart helper
        try {
            CartManagement::addItemToCart($plan->id);
        } catch (\Throwable $e) {
            Notification::make()
                ->title('Add to cart failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
            return;
        }

        Notification::make()
            ->title('Plan added to cart')
            ->body("{$plan->name} added. Redirecting to checkout…")
            ->success()
            ->send();

        return redirect()->route('checkout');
    }

    public function viewServerDetails($serverId)
    {
        $server = Server::with(['reviews.user'])->find($serverId);

        if (!$server) {
            return;
        }

        // You could emit an event to open a modal or navigate to a details page
        $this->dispatch('open-server-details', ['server' => $server]);
    }

    public function getServerRating($serverId)
    {
    $server = collect($this->servers)->firstWhere('id', $serverId);
    return $server ? ($server->server_ratings_avg_rating ?? 0) : 0;
    }

    public function filterByCountry($country)
    {
        $this->filters['country'] = $country;
        $this->page = 1;
        $this->loadServers();

        Notification::make()
            ->title('Filtered by Country')
            ->body("Showing servers from {$country}")
            ->info()
            ->send();
    }

    public function showOnlyFavorites()
    {
        $this->filters['favorites_only'] = !$this->filters['favorites_only'];
        $this->page = 1;
        $this->loadServers();
    $this->computeStats();

        if ($this->filters['favorites_only']) {
            Notification::make()
                ->title('Showing Favorites')
                ->body('Displaying your favorite servers.')
                ->success()
                ->send();
        }
    }

    public function getServerRecommendations()
    {
        // Get user's country or preferences for recommendations
        $user = Auth::user();
        
        // Simple recommendation logic - get high-rated servers with good prices
        $recommendedServers = Server::query()
            ->with(['plans' => function($q) {
                $q->where('is_active', true)->orderBy('price', 'asc');
            }])
            ->withAvg('serverRatings', 'rating')
            ->where(function($q){
                $q->where('status','active')->orWhere('status','up')->orWhere('servers.is_active', true)->orWhereNull('status');
            })
            ->whereHas('plans', function($q) {
                $q->where('is_active', true);
            })
            ->inRandomOrder()
            ->limit(6)
            ->get();

        // Keep Eloquent models for Blade relation access
        $this->servers = $recommendedServers->all();
        // Preselect first/cheapest plan per server
        foreach ($recommendedServers as $srv) {
            $plan = $srv->plans->first();
            if ($plan && empty($this->selectedPlans[$srv->id])) {
                $this->selectedPlans[$srv->id] = $plan->id;
            }
        }
        $this->hasMore = false;
        $this->page = 1;
    // Keep stats aligned with current filters/view
    $this->computeStats();

        Notification::make()
            ->title('Recommendations Updated')
            ->body('Showing personalized server recommendations based on your preferences.')
            ->success()
            ->send();
    }

    /**
     * Helper to format rating to one decimal.
     */
    public function formatRating($server): string
    {
    $rating = $server->server_ratings_avg_rating ?? 0;
        return number_format($rating, 1);
    }

    /**
     * Return an array of bools for filled stars based on average rating.
     */
    public function starArray($server): array
    {
    $rating = (float) ($server->server_ratings_avg_rating ?? 0);
        $filled = floor($rating);
        return array_map(function($i) use ($filled) { return $i <= $filled; }, range(1,5));
    }
}
