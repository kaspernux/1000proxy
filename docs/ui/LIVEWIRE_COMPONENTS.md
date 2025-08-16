# Livewire Components Guide

This guide documents the Livewire 3.x reactive components implemented in the 1000proxy application, providing dynamic, real-time user interactions without page reloads.

## 🎯 Overview

The 1000proxy application leverages Livewire 3.x to create a modern, reactive user interface with seamless interactions. All major pages are built as Livewire components, providing real-time updates, dynamic filtering, and smooth user experiences.

### Benefits of Livewire Integration

- **Real-time Interactions**: Update content without page reloads
- **Server-side Rendering**: Full Laravel backend integration
- **Reactive Components**: Automatic DOM updates on data changes
- **Alpine.js Integration**: Enhanced client-side interactions
- **Form Handling**: Advanced form validation and submission
- **Performance**: Optimized updates with minimal data transfer

## 🏗️ Component Architecture

### Core Application Components

#### 1. HomePage Component (`livewire/home-page.blade.php`)

**Purpose**: Main landing page with dynamic content and interactive elements

```php
class HomePage extends Component
{
    public $featuredProducts = [];
    public $testimonials = [];
    public $stats = [];
    
    public function mount()
    {
        $this->loadFeaturedProducts();
        $this->loadTestimonials();
        $this->loadStats();
    }
    
    public function render()
    {
        return view('livewire.home-page');
    }
}
```

**Features**:
- Dynamic hero section with real-time statistics
- Featured products carousel
- Interactive service highlights
- Testimonials slider
- Trust indicators with animations

#### 2. ProductsPage Component (`livewire/products-page.blade.php`)

**Purpose**: Advanced product browsing with real-time filtering

```php
class ProductsPage extends Component
{
    public $selectedCategory = null;
    public $searchTerm = '';
    public $priceRange = [0, 1000];
    public $sortBy = 'name';
    public $perPage = 12;
    
    protected $queryString = [
        'selectedCategory' => ['except' => null],
        'searchTerm' => ['except' => ''],
        'sortBy' => ['except' => 'name']
    ];
    
    public function updatedSearchTerm()
    {
        $this->resetPage();
    }
    
    public function updatedSelectedCategory()
    {
        $this->resetPage();
    }
    
    public function getFilteredProducts()
    {
        return Product::query()
            ->when($this->selectedCategory, function ($query) {
                $query->where('category_id', $this->selectedCategory);
            })
            ->when($this->searchTerm, function ($query) {
                $query->where('name', 'like', '%' . $this->searchTerm . '%');
            })
            ->whereBetween('price', $this->priceRange)
            ->orderBy($this->sortBy)
            ->paginate($this->perPage);
    }
    
    public function render()
    {
        return view('livewire.products-page', [
            'products' => $this->getFilteredProducts(),
            'categories' => Category::all()
        ]);
    }
}
```

**Features**:
- Real-time search with debounced input
- Category-based filtering
- Price range slider
- Dynamic sorting options
- Pagination with URL persistence
- Product grid with hover effects

#### 3. CartPage Component (`livewire/cart-page.blade.php`)

**Purpose**: Shopping cart management with real-time updates

```php
class CartPage extends Component
{
    public $cartItems = [];
    public $total = 0;
    public $tax = 0;
    public $discount = 0;
    public $promoCode = '';
    
    public function mount()
    {
        $this->loadCartItems();
        $this->calculateTotal();
    }
    
    public function updateQuantity($itemId, $quantity)
    {
        if ($quantity <= 0) {
            $this->removeItem($itemId);
            return;
        }
        
        Cart::where('id', $itemId)->update(['quantity' => $quantity]);
        $this->loadCartItems();
        $this->calculateTotal();
        
        $this->dispatch('cart-updated');
    }
    
    public function removeItem($itemId)
    {
        Cart::destroy($itemId);
        $this->loadCartItems();
        $this->calculateTotal();
        
        $this->dispatch('cart-updated');
        session()->flash('message', 'Item removed from cart');
    }
    
    public function applyPromoCode()
    {
        // Promo code logic
        $this->calculateTotal();
    }
}
```

**Features**:
- Real-time quantity updates
- Instant item removal
- Promo code application
- Dynamic total calculation
- Wishlist integration
- Checkout progression

#### 4. CheckoutPage Component (`livewire/checkout-page.blade.php`)

**Purpose**: Multi-step checkout process with validation

```php
class CheckoutPage extends Component
{
    public $currentStep = 1;
    public $billingInfo = [];
    public $paymentMethod = '';
    public $orderNotes = '';
    
    protected $rules = [
        'billingInfo.name' => 'required|string|max:255',
        'billingInfo.email' => 'required|email',
        'billingInfo.address' => 'required|string',
        'paymentMethod' => 'required|in:stripe,crypto,wallet'
    ];
    
    public function nextStep()
    {
        $this->validateStep();
        $this->currentStep++;
    }
    
    public function previousStep()
    {
        $this->currentStep--;
    }
    
    public function validateStep()
    {
        switch ($this->currentStep) {
            case 1:
                $this->validate(['billingInfo.*' => $this->rules['billingInfo.*']]);
                break;
            case 2:
                $this->validate(['paymentMethod' => $this->rules['paymentMethod']]);
                break;
        }
    }
    
    public function submitOrder()
    {
        $this->validate();
        
        // Process order
        $order = Order::create([
            'user_id' => auth()->id(),
            'billing_info' => $this->billingInfo,
            'payment_method' => $this->paymentMethod,
            'notes' => $this->orderNotes
        ]);
        
        return redirect()->route('order.confirmation', $order);
    }
}
```

**Features**:
- Progressive multi-step form
- Real-time validation
- Payment method selection
- Order summary updates
- Error handling
- Success notifications

#### 5. Account Settings Component (`livewire/account-settings.blade.php`)

**Purpose**: User account management dashboard

```php
class AccountSettings extends Component
{
    public $activeTab = 'profile';
    public $user;
    public $orders = [];
    public $services = [];
    
    protected $listeners = ['refreshOrders' => 'loadOrders'];
    
    public function mount()
    {
        $this->user = auth()->user();
        $this->loadOrders();
        $this->loadServices();
    }
    
    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
        
        switch ($tab) {
            case 'orders':
                $this->loadOrders();
                break;
            case 'services':
                $this->loadServices();
                break;
        }
    }
    
    public function updateProfile()
    {
        $this->validate([
            'customer.name' => 'required|string|max:255',
            'customer.email' => 'required|email|unique:customers,email,' . $this->customer->id
        ]);
        
        $this->user->save();
        session()->flash('message', 'Profile updated successfully');
    }
}
```

**Features**:
- Tabbed navigation interface
- Profile editing with validation
- Order history display
- Service management
- Real-time status updates
- Wallet balance tracking

### Specialized Components

#### Server Status Monitor (`livewire/components/server-status-monitor.blade.php`)

```php
class ServerStatusMonitor extends Component
{
    public $servers = [];
    public $refreshInterval = 30; // seconds
    
    public function mount()
    {
        $this->loadServerStatus();
    }
    
    public function loadServerStatus()
    {
        $this->servers = Server::with('status')->get()->map(function ($server) {
            return [
                'id' => $server->id,
                'name' => $server->name,
                'status' => $server->status->is_online ? 'online' : 'offline',
                'uptime' => $server->status->uptime,
                'last_check' => $server->status->updated_at
            ];
        });
    }
    
    public function refreshStatus()
    {
        $this->loadServerStatus();
        $this->dispatch('status-refreshed');
    }
}
```

## 🎨 Template Structure

### Blade Template Organization

Each Livewire component follows a consistent template structure:

```blade
<div>
    {{-- Header Section --}}
    <div class="bg-gradient-to-r from-blue-600 to-purple-700 text-white py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-bold mb-4">{{ $pageTitle }}</h1>
            <p class="text-xl opacity-90">{{ $pageDescription }}</p>
        </div>
    </div>
    
    {{-- Main Content --}}
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {{-- Component-specific content --}}
    </div>
    
    {{-- Footer/Actions --}}
    <div class="bg-gray-50 dark:bg-gray-800 py-8">
        {{-- Action buttons or additional content --}}
    </div>
</div>
```

### Component Communication

#### Parent-Child Communication

```php
// Parent Component
class ParentComponent extends Component
{
    protected $listeners = ['child-event' => 'handleChildEvent'];
    
    public function handleChildEvent($data)
    {
        // Handle event from child
    }
}

// Child Component
class ChildComponent extends Component
{
    public function triggerEvent()
    {
        $this->dispatch('child-event', ['data' => 'value']);
    }
}
```

#### Browser Events

```javascript
// Listen for Livewire events in JavaScript
document.addEventListener('livewire:init', () => {
    Livewire.on('cart-updated', (event) => {
        // Update cart indicator
        updateCartBadge(event.count);
    });
});
```

## 🔧 Advanced Features

### Real-time Search Implementation

```php
class ProductsPage extends Component
{
    public $searchTerm = '';
    
    public function updatedSearchTerm()
    {
        // Debounced search with loading state
        $this->dispatch('search-loading', true);
        
        // Reset pagination
        $this->resetPage();
        
        // Update results
        $this->dispatch('search-complete');
    }
}
```

```blade
<div>
    <input 
        type="text" 
        wire:model.debounce.300ms="searchTerm"
        wire:loading.attr="disabled"
        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
        placeholder="Search products..."
    >
    
    <div wire:loading wire:target="searchTerm" class="text-center py-4">
        <x-custom-icon name="loading" class="w-6 h-6 animate-spin mx-auto" />
        Searching...
    </div>
</div>
```

### Dynamic Form Validation

```php
public function updatedBillingInfo($value, $key)
{
    $this->validateOnly("billingInfo.{$key}");
}

protected function rules()
{
    return [
        'billingInfo.name' => 'required|string|max:255',
        'billingInfo.email' => 'required|email',
        'billingInfo.phone' => 'required|string|max:20'
    ];
}
```

### Loading States

```blade
{{-- Button loading state --}}
<button 
    wire:click="submitForm"
    wire:loading.attr="disabled"
    wire:loading.class="opacity-50 cursor-not-allowed"
    class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors"
>
    <span wire:loading.remove wire:target="submitForm">Submit</span>
    <span wire:loading wire:target="submitForm" class="flex items-center">
        <x-custom-icon name="loading" class="w-4 h-4 animate-spin mr-2" />
        Processing...
    </span>
</button>

{{-- Content loading state --}}
<div wire:loading.remove>
    {{-- Normal content --}}
</div>

<div wire:loading class="text-center py-8">
    <x-custom-icon name="loading" class="w-8 h-8 animate-spin mx-auto mb-4 text-gray-400" />
    <p class="text-gray-600">Loading content...</p>
</div>
```

## 🎯 Performance Optimization

### Component Optimization

```php
class ProductsPage extends Component
{
    // Use computed properties for expensive operations
    public function getProductsProperty()
    {
        return cache()->remember(
            "products.{$this->selectedCategory}.{$this->searchTerm}",
            300, // 5 minutes
            fn() => $this->getFilteredProducts()
        );
    }
    
    // Lazy load heavy content
    public $loadStats = false;
    
    public function loadStatistics()
    {
        $this->loadStats = true;
    }
}
```

### Template Optimization

```blade
{{-- Lazy loading for heavy content --}}
@if($loadStats)
    @include('partials.statistics')
@else
    <button wire:click="loadStatistics" class="btn-primary">
        Load Statistics
    </button>
@endif

{{-- Conditional rendering for large lists --}}
@if(count($products) > 0)
    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-6">
        @foreach($products as $product)
            @include('partials.product-card', ['product' => $product])
        @endforeach
    </div>
@else
    <div class="text-center py-12">
        <x-custom-icon name="folder" class="w-16 h-16 mx-auto mb-4 text-gray-400" />
        <p class="text-gray-600">No products found</p>
    </div>
@endif
```

## 🧪 Testing Livewire Components

### Component Testing

```php
use Livewire\Livewire;
use Tests\TestCase;

class ProductsPageTest extends TestCase
{
    /** @test */
    public function it_can_search_products()
    {
        $product = Product::factory()->create(['name' => 'Test Product']);
        
        Livewire::test(ProductsPage::class)
            ->set('searchTerm', 'Test')
            ->assertSee('Test Product')
            ->set('searchTerm', 'Nonexistent')
            ->assertDontSee('Test Product');
    }
    
    /** @test */
    public function it_can_filter_by_category()
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);
        
        Livewire::test(ProductsPage::class)
            ->set('selectedCategory', $category->id)
            ->assertSee($product->name);
    }
}
```

### Browser Testing

```php
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ProductsPageBrowserTest extends DuskTestCase
{
    /** @test */
    public function it_filters_products_in_real_time()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/products')
                    ->type('@search-input', 'server')
                    ->waitForText('VPS Server')
                    ->assertSee('VPS Server')
                    ->clear('@search-input')
                    ->type('@search-input', 'proxy')
                    ->waitForText('Proxy Service')
                    ->assertSee('Proxy Service');
        });
    }
}
```

## 🔄 Future Enhancements

### Planned Component Improvements

1. **Infinite Scrolling**: Replace pagination with infinite scroll
2. **Real-time Notifications**: WebSocket integration for live updates
3. **Enhanced Filtering**: Advanced filter combinations
4. **Offline Support**: Progressive Web App capabilities
5. **Component Library**: Reusable component library

### Performance Targets

- **Initial Load**: <2s for component initialization
- **Update Speed**: <100ms for reactive updates
- **Memory Usage**: <50MB for complex page components
- **Network Efficiency**: Minimal data transfer on updates

---

This Livewire implementation provides a modern, reactive user experience while maintaining the robustness and security of server-side rendering.
