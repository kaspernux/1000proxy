<?php

namespace App\Filament\Customer\Pages;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use App\Models\Server;
use App\Models\Country;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class ServerBrowsing extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-server';
    protected static ?string $navigationLabel = 'Browse Servers';
    protected static string $view = 'filament.customer.pages.server-browsing';
    protected static ?int $navigationSort = 2;

    public $filters = [
        'country' => null,
        'type' => null,
        'status' => null,
        'price_min' => null,
        'price_max' => null,
        'search' => null,
        'sort' => 'price_asc',
        'favorites_only' => false,
    ];

    public $showFilters = false;
    public $perPage = 12;
    public $servers = [];
    public $countries = [];
    public $page = 1;
    public $hasMore = true;

    public function mount()
    {
        $this->loadCountries();
        $this->loadServers();
    }

    public function getFormSchema(): array
    {
        return [
            TextInput::make('filters.search')
                ->placeholder('Search servers...')
                ->suffixIcon('heroicon-m-magnifying-glass'),

            Select::make('filters.country')
                ->placeholder('All Countries')
                ->options($this->countries)
                ->searchable(),

            Select::make('filters.type')
                ->placeholder('All Types')
                ->options([
                    'dedicated' => 'Dedicated',
                    'shared' => 'Shared',
                    'rotating' => 'Rotating',
                    'static' => 'Static',
                ]),

            Select::make('filters.status')
                ->placeholder('All Status')
                ->options([
                    'online' => 'Online',
                    'offline' => 'Offline',
                    'maintenance' => 'Maintenance',
                ]),

            TextInput::make('filters.price_min')
                ->numeric()
                ->placeholder('Min Price'),

            TextInput::make('filters.price_max')
                ->numeric()
                ->placeholder('Max Price'),

            Select::make('filters.sort')
                ->options([
                    'price_asc' => 'Price: Low to High',
                    'price_desc' => 'Price: High to Low',
                    'name_asc' => 'Name: A to Z',
                    'name_desc' => 'Name: Z to A',
                    'country_asc' => 'Country: A to Z',
                    'rating_desc' => 'Rating: High to Low',
                ])
                ->default('price_asc'),
        ];
    }

    public function loadCountries()
    {
        $this->countries = Country::query()
            ->whereHas('servers', function (Builder $query) {
                $query->where('status', 'active');
            })
            ->pluck('name', 'id')
            ->toArray();
    }

    public function loadServers($append = false)
    {
        $query = Server::query()
            ->with(['country', 'reviews'])
            ->where('status', 'active');

        // Apply filters
        if ($this->filters['country']) {
            $query->where('country_id', $this->filters['country']);
        }

        if ($this->filters['type']) {
            $query->where('type', $this->filters['type']);
        }

        if ($this->filters['status']) {
            $query->where('server_status', $this->filters['status']);
        }

        if ($this->filters['price_min']) {
            $query->where('price', '>=', $this->filters['price_min']);
        }

        if ($this->filters['price_max']) {
            $query->where('price', '<=', $this->filters['price_max']);
        }

        if ($this->filters['search']) {
            $query->where(function (Builder $q) {
                $q->where('name', 'like', '%' . $this->filters['search'] . '%')
                  ->orWhere('description', 'like', '%' . $this->filters['search'] . '%')
                  ->orWhereHas('country', function (Builder $countryQuery) {
                      $countryQuery->where('name', 'like', '%' . $this->filters['search'] . '%');
                  });
            });
        }

        if ($this->filters['favorites_only']) {
            $query->whereHas('favorites', function (Builder $q) {
                $q->where('user_id', Auth::id());
            });
        }

        // Apply sorting
        switch ($this->filters['sort']) {
            case 'price_asc':
                $query->orderBy('price', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('price', 'desc');
                break;
            case 'name_asc':
                $query->orderBy('name', 'asc');
                break;
            case 'name_desc':
                $query->orderBy('name', 'desc');
                break;
            case 'country_asc':
                $query->join('countries', 'servers.country_id', '=', 'countries.id')
                      ->orderBy('countries.name', 'asc')
                      ->select('servers.*');
                break;
            case 'rating_desc':
                $query->withAvg('reviews', 'rating')
                      ->orderBy('reviews_avg_rating', 'desc');
                break;
        }

        $servers = $query->paginate($this->perPage, ['*'], 'page', $this->page);

        if ($append) {
            $this->servers = array_merge($this->servers, $servers->items());
        } else {
            $this->servers = $servers->items();
        }

        $this->hasMore = $servers->hasMorePages();
    }

    public function updatedFilters()
    {
        $this->page = 1;
        $this->loadServers();
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
        ];
        $this->page = 1;
        $this->loadServers();
    }

    public function toggleFavorite($serverId)
    {
        $user = Auth::user();
        $server = Server::find($serverId);

        if (!$server) {
            return;
        }

        $favorite = $user->favoriteServers()->where('server_id', $serverId)->first();

        if ($favorite) {
            $user->favoriteServers()->detach($serverId);

            Notification::make()
                ->title('Removed from favorites')
                ->body("Server '{$server->name}' has been removed from your favorites.")
                ->warning()
                ->send();
        } else {
            $user->favoriteServers()->attach($serverId);

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
        return Auth::user()->favoriteServers()->where('server_id', $serverId)->exists();
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

        // Redirect to checkout
        return redirect()->route('filament.customer.pages.checkout');
    }

    public function viewServerDetails($serverId)
    {
        $server = Server::with(['country', 'reviews.user'])->find($serverId);

        if (!$server) {
            return;
        }

        // You could emit an event to open a modal or navigate to a details page
        $this->dispatch('open-server-details', ['server' => $server]);
    }

    public function getServerRating($serverId)
    {
        $server = collect($this->servers)->firstWhere('id', $serverId);
        return $server ? $server->reviews_avg_rating ?? 0 : 0;
    }

    public function filterByCountry($countryId)
    {
        $this->filters['country'] = $countryId;
        $this->page = 1;
        $this->loadServers();

        $country = Country::find($countryId);

        Notification::make()
            ->title('Filtered by Country')
            ->body("Showing servers from {$country->name}")
            ->info()
            ->send();
    }

    public function showOnlyFavorites()
    {
        $this->filters['favorites_only'] = !$this->filters['favorites_only'];
        $this->page = 1;
        $this->loadServers();

        if ($this->filters['favorites_only']) {
            Notification::make()
                ->title('Showing Favorites')
                ->body('Displaying your favorite servers.')
                ->success()
                ->send();
        }
    }
}
