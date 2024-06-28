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
use Jantinnerezo\LivewireAlert\LivewireAlert;

#[Title('Products Page - 1000 PROXIES')]
class ProductsPage extends Component
{
    use WithPagination;
    use LivewireAlert;

    #[Url]
    public $selected_categories = [];

    #[Url]
    public $selected_brands = [];

    #[Url]
    public $selected_countries = [];

    #[Url]
    public $featured = [];

    #[Url]
    public $on_sale = [];

    public $price = 500;
    public $sortOrder = 'latest';

    protected $queryString = [
        'selected_categories' => ['except' => []],
        'selected_brands' => ['except' => []],
        'selected_countries' => ['except' => []],
        'featured' => ['except' => []],
        'on_sale' => ['except' => []],
        'price' => ['except' => 500],
        'sortOrder' => ['except' => 'latest'],
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

    public function render()
    {
        $serverQuery = ServerPlan::query()->where('is_active', 1);

        if (!empty($this->selected_categories)) {
            $serverQuery->whereIn('server_category_id', $this->selected_categories);
        }

        if (!empty($this->selected_brands)) {
            $serverQuery->whereIn('server_brand_id', $this->selected_brands);
        }

        if (!empty($this->selected_countries)) {
            $serverQuery->whereHas('category.servers', function ($query) {
                $query->whereIn('country', $this->selected_countries);
            });
        }

        if ($this->price !== null) {
            $serverQuery->where('price', '<=', $this->price);
        }

        if ($this->sortOrder === 'latest') {
            $serverQuery->orderBy('created_at', 'desc');
        } elseif ($this->sortOrder === 'price') {
            $serverQuery->orderBy('price', 'asc');
        }

        if ($this->featured) {
            $serverQuery->where('is_featured', 1);
        }

        if ($this->on_sale) {
            $serverQuery->where('on_sale', 1);
        }

        return view('livewire.products-page', [
            'serverPlans' => $serverQuery->paginate(9),
            'brands' => ServerBrand::where('is_active', 1)->get(['id', 'name', 'slug']),
            'categories' => ServerCategory::where('is_active', 1)->get(['id', 'name', 'slug', 'image']),
            'servers' => Server::where('status', 'up')->get(['id', 'country']),
        ]);
    }
}