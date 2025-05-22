<div class="w-full bg-gradient-to-r from-green-900 to-green-600 py-12 px-4 sm:px-6 md:px-8 lg:px-10">
    <div class="max-w-7xl mx-auto">
        <h1 class="text-2xl sm:text-3xl md:text-4xl my-6 font-bold text-white text-left">Shopping Cart</h1>

        <div class="flex flex-col md:flex-row gap-4">
            <!-- Cart Items -->
            <div class="w-full md:w-3/4">
                <div class="bg-white rounded-lg shadow-md p-4 sm:p-6 mb-4 overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left whitespace-nowrap">
                                <th class="pb-2 font-semibold">Product</th>
                                <th class="pb-2 font-semibold">Price</th>
                                <th class="pb-2 font-semibold">Quantity</th>
                                <th class="pb-2 font-semibold">Total</th>
                                <th class="pb-2 font-semibold">Remove</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($order_items as $item)
                            <tr class="border-t text-sm sm:text-base whitespace-nowrap" wire:key='{{ $item["server_plan_id"] }}'>
    <!-- Product -->
    <td class="py-2 pr-2 max-w-[160px] sm:max-w-xs">
        <div class="flex items-center space-x-2">
            <img class="h-9 w-9 sm:h-10 sm:w-10 object-cover rounded" src="{{ url('storage/' . $item['product_image']) }}" alt="{{ $item['name'] }}">
            <span class="truncate text-sm font-medium" title="{{ $item['name'] }}">
                {{ \Illuminate\Support\Str::limit($item['name'], 28) }}
            </span>
        </div>
    </td>

    <!-- Price -->
    <td class="py-2 px-2 text-sm font-medium text-gray-700">
        {{ Number::currency($item['price']) }}
    </td>

    <!-- Quantity -->
    <td class="py-2 px-2">
        <div class="flex items-center space-x-1">
            <button wire:click='decreaseQty({{ $item["server_plan_id"] }})'
                class="w-6 h-6 flex items-center justify-center rounded border text-xs">-</button>

            <span class="w-6 text-center text-sm">{{ $item['quantity'] }}</span>

            <button wire:click='increaseQty({{ $item["server_plan_id"] }})'
                class="w-6 h-6 flex items-center justify-center rounded border text-xs">+</button>
        </div>
    </td>

    <!-- Total -->
    <td class="py-2 px-2 text-sm font-medium text-gray-700">
        {{ Number::currency($item['total_amount']) }}
    </td>

    <!-- Remove -->
    <td class="py-2 pl-2">
        <button wire:click='removeItem({{ $item["server_plan_id"] }})'
            class="px-2 py-1 text-xs sm:text-sm bg-green-400 border border-green-600 rounded hover:bg-red-500 hover:text-white hover:border-yellow-600">
            <span wire:loading.remove wire:target='removeItem({{ $item["server_plan_id"] }})'>Remove</span>
            <span wire:loading wire:target='removeItem({{ $item["server_plan_id"] }})'>Removing...</span>
        </button>
    </td>
</tr>


                            @empty
                            <tr>
                                <td colspan="5" class="text-center py-6 text-xl font-semibold text-gray-500">No items available in cart</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Summary -->
            <div class="w-full md:w-1/4">
                <div class="bg-white rounded-lg shadow-md p-4 sm:p-6">
                    <h2 class="text-lg font-semibold mb-4">Summary</h2>
                    <div class="flex justify-between mb-2">
                        <span>Subtotal</span>
                        <span>{{ Number::currency($grand_amount) }}</span>
                    </div>
                    <div class="flex justify-between mb-2">
                        <span>Taxes</span>
                        <span>{{ Number::currency(0) }}</span>
                    </div>
                    <div class="flex justify-between mb-2">
                        <span>Shipping</span>
                        <span>{{ Number::currency(0) }}</span>
                    </div>
                    <hr class="my-3">
                    <div class="flex justify-between mb-2 font-semibold">
                        <span>Total</span>
                        <span>{{ Number::currency($grand_amount) }}</span>
                    </div>
                    @if ($order_items)
                    <a href="/checkout"
                        class="bg-yellow-600 text-center hover:bg-green-500 text-white py-2 px-4 rounded-lg mt-4 block w-full transition duration-300">
                        Checkout
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
