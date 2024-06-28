<div
    class="w-full bg-gradient-to-r from-green-900 to-green-600 py-12 px-6 sm:px-8 lg:px-10 mx-auto max-w-[auto] flex justify-center">
    <div class="container mx-auto px-4 max-w-7xl">
        <h1 class="text-4xl my-10 font-bold text-white text-left">Shopping Cart</h1>
        <div class="flex flex-col md:flex-row gap-4">
            <div class="md:w-3/4">
                <div class="bg-white overflow-x-auto rounded-lg shadow-md p-6 mb-4">
                    <table class="w-full">
                        <thead>
                            <tr>
                                <th class="text-left font-semibold">Product</th>
                                <th class="text-left font-semibold">Price</th>
                                <th class="text-left font-semibold">Quantity</th>
                                <th class="text-left font-semibold">Total</th>
                                <th class="text-left font-semibold">Remove</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($order_items as $item)
                            <tr wire:key='{{ $item['server_plan_id'] }}'>
                                <td class="py-4">
                                    <div class="flex items-center">
                                        <img class="h-16 w-16 mr-4" src="{{ url('storage/' . $item['product_image']) }}"
                                            alt="{{ $item['name'] }}">
                                        <span class="font-semibold">{{ $item['name'] }}</span>
                                    </div>
                                </td>
                                <td class="py-4">{{ Number::currency($item['price']) }}</td>
                                <td class="py-4">
                                    <div class="flex items-center">
                                        <button wire:click='decreaseQty({{ $item['server_plan_id'] }})'
                                            class="border rounded-md py-2 px-4 mr-2">-</button>
                                        <span class="text-center w-8">{{ $item['quantity'] }}</span>
                                        <button wire:click='increaseQty({{ $item['server_plan_id'] }})'
                                            class="border rounded-md py-2 px-4 ml-2">+</button>
                                    </div>
                                </td>
                                <td class="py-4">{{ Number::currency($item['total_amount']) }}</td>
                                <td>
                                    <button wire:click='removeItem({{ $item['server_plan_id'] }})'
                                        class="bg-green-400 border-2 border-green-600 rounded-lg px-3 py-1 hover:bg-red-500 hover:text-white hover:border-yellow-600">
                                        <span wire:loading.remove wire:target='removeItem({{ $item['server_plan_id'] }})'>Remove</span>
                                        <span wire:loading wire:target='removeItem({{ $item['server_plan_id'] }})'>Removing...</span>
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-4xl font-bold text-mono">No items available in cart</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="md:w-1/4">
                <div class="bg-white rounded-lg shadow-md p-6">
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
                    <hr class="my-2">
                    <div class="flex justify-between mb-2">
                        <span class="font-semibold">Total</span>
                        <span class="font-semibold">{{ Number::currency($grand_amount) }}</span>
                    </div>
                    @if ($order_items)
                    <a href="/checkout"
                        class="bg-yellow-600 block text-center hover:bg-green-400 text-white py-2 px-4 rounded-lg mt-4 w-full">Checkout</a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
