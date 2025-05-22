@php
use Illuminate\Support\Str;
@endphp

<div class="w-full font-mono bg-gradient-to-r from-green-900 to-green-600 py-10 px-4 sm:px-6 lg:px-8">
    <section class="py-10 rounded-lg">
        <div class="mx-auto max-w-7xl">
        <div class="flex flex-col lg:flex-row gap-6 lg:gap-10">
        <!-- Filters -->
                <div class="w-full lg:w-1/4 space-y-6">
                    <!-- Category Filter -->
                    <div class="p-4 border-2 border-double border-yellow-600 bg-green-900 rounded-lg">
                        <h2 class="text-2xl font-bold text-white">Category</h2>
                        <div class="w-16 border-b border-yellow-600 mb-4"></div>
                        <ul>
                            @foreach($categories as $category)
                                <li wire:key="category-{{ $category->id }}" class="mb-3">
                                    <label class="flex items-center space-x-2 text-white">
                                        <input type="checkbox" wire:model.lazy="selected_categories" value="{{ $category->id }}"
                                            class="w-4 h-4 border-yellow-600 rounded-lg border-2">
                                        <span class="text-lg uppercase">{{ $category->name }}</span>
                                    </label>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <!-- Brand Filter -->
                    <div class="p-4 border-2 border-double border-yellow-600 bg-green-900 rounded-lg">
                        <h2 class="text-2xl font-bold text-white">Brand</h2>
                        <div class="w-16 border-b border-yellow-600 mb-4"></div>
                        <ul>
                            @foreach($brands as $brand)
                                <li wire:key="brand-{{ $brand->id }}" class="mb-3">
                                    <label class="flex items-center space-x-2 text-white">
                                        <input type="checkbox" wire:model.lazy="selected_brands" value="{{ $brand->id }}"
                                            class="w-4 h-4 border-yellow-600 rounded-lg border-2">
                                        <span class="text-lg uppercase">{{ $brand->name }}</span>
                                    </label>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <!-- Country Filter -->
                    <div class="p-4 border-2 border-double border-yellow-600 bg-green-900 rounded-lg">
                        <h2 class="text-2xl font-bold text-white">Location</h2>
                        <div class="w-16 border-b border-yellow-600 mb-4"></div>
                        <ul>
                            @foreach($servers->pluck('country')->unique() as $country)
                                <li wire:key="country-{{ $country }}" class="mb-3">
                                    <label class="flex items-center space-x-2 text-white">
                                        <input type="checkbox" wire:model.lazy="selected_countries" value="{{ $country }}"
                                            class="w-4 h-4 border-yellow-600 rounded-lg border-2">
                                        <span class="text-lg uppercase">{{ $country }}</span>
                                    </label>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <!-- Product Status -->
                    <div class="p-4 border-2 border-double border-yellow-600 bg-green-900 rounded-lg">
                        <h2 class="text-2xl font-bold text-white">Product Status</h2>
                        <div class="w-16 border-b border-yellow-600 mb-4"></div>
                        <ul>
                            <li class="mb-3">
                                <label class="flex items-center space-x-2 text-white">
                                    <input type="checkbox" wire:model.lazy="featured"
                                        class="w-4 h-4 border-yellow-600 rounded-lg border-2">
                                    <span class="text-lg">Featured</span>
                                </label>
                            </li>
                            <li class="mb-3">
                                <label class="flex items-center space-x-2 text-white">
                                    <input type="checkbox" wire:model.lazy="on_sale"
                                        class="w-4 h-4 border-yellow-600 rounded-lg border-2">
                                    <span class="text-lg">On Sale</span>
                                </label>
                            </li>
                        </ul>
                    </div>

                    <!-- Price Slider -->
                    <div class="p-4 border-2 border-double border-yellow-600 bg-green-900 rounded-lg">
                        <h2 class="text-2xl font-bold text-white">Price</h2>
                        <div class="w-16 border-b border-yellow-600 mb-4"></div>
                        <div>
                            <div class="text-white font-bold mb-2">
                                <span id="price-display">${{ $price }}</span>
                            </div>
                            <input type="range" wire:model.lazy="price" min="0" max="500" step="1"
                                oninput="document.getElementById('price-display').textContent = '$' + this.value"
                                class="w-full h-2 bg-yellow-600 rounded-lg appearance-none cursor-pointer">
                            <div class="flex justify-between mt-2 text-white font-bold">
                                <span>$0</span>
                                <span>$500</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Products -->
                <div class="w-full lg:w-3/4">
                    <!-- Sorting -->
                    <div class="mb-4 flex justify-end">
                        <select wire:model.lazy="sortOrder"
                            class="w-60 px-3 py-2 bg-yellow-600 text-white font-bold rounded-lg focus:outline-none focus:ring">
                            <option value="latest">Sort by Latest</option>
                            <option value="price">Sort by Price</option>
                            <option value="brand">Sort by Brand</option>
                            <option value="category">Sort by Category</option>
                        </select>
                    </div>

                    <!-- Products Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
                        @forelse($serverPlans as $plan)
                            <div class="border-2 border-yellow-600 rounded-lg overflow-hidden" wire:key="plan-{{ $plan->id }}">
                                <a href="/servers/{{ $plan->slug }}" class="block bg-green-900 p-4 h-60 flex items-center justify-center">
                                    <img src="{{ url('storage/' . $plan->product_image) }}" class="object-contain h-full w-full" alt="{{ $plan->name }}">
                                </a>
                                <div class="p-4 bg-white">
                                    <h3 class="text-lg font-bold text-green-900 truncate">{{ $plan->name }}</h3>
                                    <p class="text-xl text-yellow-600 font-bold">{{ Number::currency($plan->price) }}</p>
                                </div>
                                <div class="bg-green-900 p-3 text-center">
                                    <a wire:click.prevent="addToCart({{ $plan->id }})" href="#"
                                        class="inline-flex items-center justify-center gap-2 text-white font-bold bg-green-900 hover:bg-yellow-600 px-4 py-2 rounded transition">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 16 16">
                                            <path d="M0 1.5A.5.5 0 0 1 .5 1H2l.89 2H14.5a.5.5 0 0 1 .49.598l-1 5a.5.5 0 0 1-.465.401L4.415 9.07 4 11h9a.5.5 0 0 1 0 1H3.5a.5.5 0 0 1-.491-.408L1.01 3.607 0.5 2H.5a.5.5 0 0 1-.5-.5zM5 13a1 1 0 1 1 0 2 1 1 0 0 1 0-2zm7 1a1 1 0 1 0-2 0 1 1 0 0 0 2 0z"/>
                                        </svg>
                                        <span wire:loading.remove wire:target="addToCart({{ $plan->id }})">Add to Cart</span>
                                        <span wire:loading wire:target="addToCart({{ $plan->id }})">Adding...</span>
                                    </a>
                                </div>
                            </div>
                        @empty
                            <div class="col-span-full text-center text-white text-lg">
                                No products found.
                            </div>
                        @endforelse
                    </div>

                    <!-- Pagination -->
                    <div class="mt-6">
                        {{ $serverPlans->links() }}
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>