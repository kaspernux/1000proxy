<div class="min-h-screen bg-gradient-to-b from-green-50 via-white to-green-100 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900 py-10 px-2 sm:px-6 lg:px-10 flex justify-center">
    <div class="w-full max-w-7xl">
        <h1 class="text-3xl sm:text-4xl font-extrabold text-green-700 dark:text-green-300 text-center mb-8 tracking-tight drop-shadow-lg">Order Details</h1>

        <!-- Order Info Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
            <!-- Card: Order Date -->
            <div class="flex flex-col bg-white/90 dark:bg-green-900 border border-green-100 dark:border-green-800 shadow-lg rounded-2xl p-5 items-center">
                <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-yellow-600 dark:bg-green-800 mb-3">
                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 22h14M5 2h14M17 22v-4.172a2 2 0 0 0-.586-1.414L12 12l-4.414 4.414A2 2 0 0 0 7 17.828V22M7 2v4.172a2 2 0 0 0 .586 1.414L12 12l4.414-4.414A2 2 0 0 0 17 6.172V2"/></svg>
                </div>
                <p class="text-xs uppercase tracking-wide text-green-600">Order Date</p>
                <h3 class="text-lg font-semibold text-green-800 dark:text-green-400 mt-1">{{$order_items[0]->created_at->format('d-m-Y')}}</h3>
            </div>
            <!-- Card: Order Status -->
            <div class="flex flex-col bg-white/90 dark:bg-green-900 border border-green-100 dark:border-green-800 shadow-lg rounded-2xl p-5 items-center">
                <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-yellow-600 dark:bg-green-800 mb-3">
                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M21 11V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h6"/><path d="m12 12 4 10 1.7-4.3L22 16Z"/></svg>
                </div>
                <p class="text-xs uppercase tracking-wide text-green-600">Order Status</p>
                <div class="mt-1">
                    @php
                        $order_status = '';
                        if ($order->order_status == 'new') {
                            $order_status = '<span class="bg-blue-500 py-1 px-3 rounded text-white shadow">New</span>';
                        }
                        if ($order->order_status == 'processing') {
                            $order_status = '<span class="bg-orange-800 py-1 px-3 rounded text-white shadow">Processing</span>';
                        }
                        if ($order->order_status == 'completed') {
                            $order_status = '<span class="bg-green-700 py-1 px-3 rounded text-white shadow">Completed</span>';
                        }
                        if ($order->order_status == 'dispute') {
                            $order_status = '<span class="bg-red-700 py-1 px-3 rounded text-white shadow">Dispute</span>';
                        }
                    @endphp
                    {!! $order_status !!}
                </div>
            </div>
            <!-- Card: Customer -->
            <div class="flex flex-col bg-white/90 dark:bg-green-900 border border-green-100 dark:border-green-800 shadow-lg rounded-2xl p-5 items-center">
                <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-yellow-600 dark:bg-green-800 mb-3">
                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                </div>
                <p class="text-xs uppercase tracking-wide text-green-600">Customer</p>
                <h3 class="text-lg font-semibold text-green-800 dark:text-green-400 mt-1">{{$order->customer->name}}</h3>
            </div>
            <!-- Card: Payment Status -->
            <div class="flex flex-col bg-white/90 dark:bg-green-900 border border-green-100 dark:border-green-800 shadow-lg rounded-2xl p-5 items-center">
                <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-yellow-600 dark:bg-green-800 mb-3">
                    <svg class="w-6 h-6 text-green-600 dark:text-green-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M17 9V7a5 5 0 0 0-10 0v2a5 5 0 0 0 10 0zm-2 4v2a3 3 0 0 1-6 0v-2"/></svg>
                </div>
                <p class="text-xs uppercase tracking-wide text-green-600">Payment Status</p>
                <div class="mt-1">
                    @php
                        $payment_status = '';
                        if ($order->payment_status == 'pending') {
                            $payment_status = '<span class="bg-orange-800 py-1 px-3 rounded text-white shadow">Pending</span>';
                        }
                        if ($order->payment_status == 'paid') {
                            $payment_status = '<span class="bg-green-700 py-1 px-3 rounded text-white shadow">Paid</span>';
                        }
                        if ($order->payment_status == 'failed') {
                            $payment_status = '<span class="bg-red-700 py-1 px-3 rounded text-white shadow">Failed</span>';
                        }
                    @endphp
                    {!! $payment_status !!}
                </div>
            </div>
        </div>

        <!-- Product Table & Summary -->
        <div class="flex flex-col md:flex-row gap-8 mt-6">
            <div class="md:w-3/4 w-full">
                <div class="bg-white/90 dark:bg-green-900 rounded-2xl shadow-lg p-6 mb-6 overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr>
                                <th class="py-2 px-2 font-semibold text-green-700 dark:text-green-300">Product</th>
                                <th class="py-2 px-2 font-semibold text-green-700 dark:text-green-300">Price</th>
                                <th class="py-2 px-2 font-semibold text-green-700 dark:text-green-300">Quantity</th>
                                <th class="py-2 px-2 font-semibold text-green-700 dark:text-green-300">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($order_items as $item)
                                <tr wire:key="{{$item->id}}" class="border-b border-green-100 dark:border-green-800 last:border-0">
                                    <td class="py-4 flex items-center gap-4">
                                        <img class="h-14 w-14 rounded-lg object-cover" src="{{ url('storage/' .$item->serverPlan->product_image) }}" alt="Product image">
                                        <span class="font-semibold text-green-900 dark:text-green-200">{{$item->name}}</span>
                                    </td>
                                    <td class="py-4">{{Number::currency($item->unit_amount)}}</td>
                                    <td class="py-4 text-center">{{$item->quantity}}</td>
                                    <td class="py-4 font-semibold">{{Number::currency($item->total_amount)}}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="md:w-1/4 w-full">
                <div class="bg-white/90 dark:bg-green-900 rounded-2xl shadow-lg p-6">
                    <h2 class="text-lg font-bold text-green-700 dark:text-green-300 mb-4">Summary</h2>
                    <div class="flex justify-between mb-2">
                        <span>Subtotal</span>
                        <span>{{Number::currency($item->order->grand_amount)}}</span>
                    </div>
                    <div class="flex justify-between mb-2">
                        <span>Taxes</span>
                        <span>{{Number::currency(0)}}</span>
                    </div>
                    <div class="flex justify-between mb-2">
                        <span>Shipping</span>
                        <span>{{Number::currency(0)}}</span>
                    </div>
                    <hr class="my-2 border-green-100 dark:border-green-800">
                    <div class="flex justify-between mb-2">
                        <span class="font-semibold">Grand Total</span>
                        <span class="font-semibold">{{Number::currency($item->order->grand_amount)}}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
