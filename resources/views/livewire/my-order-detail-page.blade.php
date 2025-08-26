<div class="min-h-screen bg-gradient-to-br from-gray-900 via-blue-900/30 to-gray-800 py-10 px-2 sm:px-6 lg:px-10 flex justify-center relative overflow-hidden">
    <!-- Animated Background Elements -->
    <div class="absolute inset-0 overflow-hidden">
        <div class="absolute -top-40 -right-32 w-80 h-80 bg-gradient-to-br from-blue-500/20 to-purple-600/20 rounded-full animate-pulse"></div>
        <div class="absolute -bottom-32 -left-32 w-64 h-64 bg-gradient-to-tr from-yellow-500/20 to-green-500/20 rounded-full animate-bounce" style="animation-duration: 3s;"></div>
        <div class="absolute top-1/2 left-1/3 w-32 h-32 bg-gradient-to-br from-pink-500/10 to-purple-600/10 rounded-full animate-ping" style="animation-duration: 4s;"></div>
    </div>

    <div class="w-full max-w-7xl relative z-10">
        <div class="text-center mb-8">
            <div class="flex items-center justify-center w-16 h-16 rounded-full bg-gradient-to-br from-blue-500/30 to-purple-600/30 backdrop-blur-md mx-auto mb-4 border border-blue-400/30">
                <svg class="w-8 h-8 text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
            </div>
            <h1 class="text-3xl sm:text-4xl font-extrabold text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-purple-400 text-center mb-2 tracking-tight">Order Details</h1>
        </div>

        <!-- Order Info Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
            <!-- Card: Order Date -->
            <div class="flex flex-col bg-white/10 backdrop-blur-md border border-white/20 shadow-xl rounded-2xl p-5 items-center hover:shadow-2xl hover:scale-105 transition-all duration-300">
                <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-gradient-to-br from-blue-500/30 to-purple-600/30 backdrop-blur-md mb-3 border border-blue-400/30">
                    <svg class="w-6 h-6 text-blue-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 22h14M5 2h14M17 22v-4.172a2 2 0 0 0-.586-1.414L12 12l-4.414 4.414A2 2 0 0 0 7 17.828V22M7 2v4.172a2 2 0 0 0 .586 1.414L12 12l4.414-4.414A2 2 0 0 0 17 6.172V2"/></svg>
                </div>
                <p class="text-xs uppercase tracking-wide text-blue-300">Order Date</p>
                <h3 class="text-lg font-semibold text-white mt-1">{{$order_items[0]->created_at->format('d-m-Y')}}</h3>
            </div>
            <!-- Card: Order Status -->
            <div class="flex flex-col bg-white/10 backdrop-blur-md border border-white/20 shadow-xl rounded-2xl p-5 items-center hover:shadow-2xl hover:scale-105 transition-all duration-300">
                <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-gradient-to-br from-green-500/30 to-blue-600/30 backdrop-blur-md mb-3 border border-green-400/30">
                    <svg class="w-6 h-6 text-green-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M21 11V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h6"/><path d="m12 12 4 10 1.7-4.3L22 16Z"/></svg>
                </div>
                <p class="text-xs uppercase tracking-wide text-green-300">Order Status</p>
                <div class="mt-1">
                    @php
                        $order_status = '';
                        if ($order->order_status == 'new') {
                            $order_status = '<span class="bg-blue-500/80 backdrop-blur-md py-1 px-3 rounded-full text-white shadow border border-blue-400/30">New</span>';
                        }
                        if ($order->order_status == 'processing') {
                            $order_status = '<span class="bg-orange-500/80 backdrop-blur-md py-1 px-3 rounded-full text-white shadow border border-orange-400/30">Processing</span>';
                        }
                        if ($order->order_status == 'completed') {
                            $order_status = '<span class="bg-green-500/80 backdrop-blur-md py-1 px-3 rounded-full text-white shadow border border-green-400/30">Completed</span>';
                        }
                        if ($order->order_status == 'dispute') {
                            $order_status = '<span class="bg-red-500/80 backdrop-blur-md py-1 px-3 rounded-full text-white shadow border border-red-400/30">Dispute</span>';
                        }
                    @endphp
                    {!! $order_status !!}
                </div>
            </div>
            <!-- Card: Customer -->
            <div class="flex flex-col bg-white/10 backdrop-blur-md border border-white/20 shadow-xl rounded-2xl p-5 items-center hover:shadow-2xl hover:scale-105 transition-all duration-300">
                <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-gradient-to-br from-purple-500/30 to-pink-600/30 backdrop-blur-md mb-3 border border-purple-400/30">
                    <svg class="w-6 h-6 text-purple-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                </div>
                <p class="text-xs uppercase tracking-wide text-purple-300">Customer</p>
                <h3 class="text-lg font-semibold text-white mt-1">{{$order->customer->name}}</h3>
            </div>
            <!-- Card: Payment Status -->
            <div class="flex flex-col bg-white/10 backdrop-blur-md border border-white/20 shadow-xl rounded-2xl p-5 items-center hover:shadow-2xl hover:scale-105 transition-all duration-300">
                <div class="flex items-center justify-center w-12 h-12 rounded-lg bg-gradient-to-br from-yellow-500/30 to-orange-600/30 backdrop-blur-md mb-3 border border-yellow-400/30">
                    <svg class="w-6 h-6 text-yellow-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M17 9V7a5 5 0 0 0-10 0v2a5 5 0 0 0 10 0zm-2 4v2a3 3 0 0 1-6 0v-2"/></svg>
                </div>
                <p class="text-xs uppercase tracking-wide text-yellow-300">Payment Status</p>
                <div class="mt-1">
                    @php
                        $payment_status = '';
                        if ($order->payment_status == 'pending') {
                            $payment_status = '<span class="bg-orange-500/80 backdrop-blur-md py-1 px-3 rounded-full text-white shadow border border-orange-400/30">Pending</span>';
                        }
                        if ($order->payment_status == 'paid') {
                            $payment_status = '<span class="bg-green-500/80 backdrop-blur-md py-1 px-3 rounded-full text-white shadow border border-green-400/30">Paid</span>';
                        }
                        if ($order->payment_status == 'failed') {
                            $payment_status = '<span class="bg-red-500/80 backdrop-blur-md py-1 px-3 rounded-full text-white shadow border border-red-400/30">Failed</span>';
                        }
                    @endphp
                    {!! $payment_status !!}
                </div>
            </div>
        </div>

        <!-- Quick setup tips and dashboard link -->
        <div class="mb-8">
            <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="flex items-start gap-3">
                    <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-gradient-to-br from-blue-500/30 to-purple-600/30 border border-blue-400/30">
                        <svg class="w-6 h-6 text-blue-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <p class="text-white font-semibold mb-1">Setup tips</p>
                        <ul class="text-sm text-blue-100 space-y-1 list-disc list-inside">
                            <li>iOS/Android: V2Box is preferred.</li>
                            <li>Windows: v2rayN is preferred.</li>
                        </ul>
                        <p class="text-xs text-gray-300 mt-2">Scan the QR code to import, or use “Download QR code” to save the image and import it in your app.</p>
                    </div>
                </div>
                @php
                    $dashboardUrl = \Illuminate\Support\Facades\Route::has('filament.customer.pages.dashboard')
                        ? route('filament.customer.pages.dashboard')
                        : url('/account');
                @endphp
                <a href="{{$dashboardUrl}}" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-gradient-to-r from-green-500/20 to-blue-500/20 text-green-300 border border-green-400/30 hover:from-green-500/30 hover:to-blue-500/30 transition-colors">
                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0h6"/></svg>
                    <span>Go to Customer Dashboard</span>
                </a>
            </div>
        </div>

        <!-- Product Table & Summary -->
        <div class="flex flex-col md:flex-row gap-8 mt-6">
            <div class="md:w-3/4 w-full">
                <div class="bg-white/90 dark:bg-green-900 rounded-2xl shadow-lg p-6 mb-6 overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="border-b border-white/20">
                                <th class="py-4 px-2 font-semibold text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-purple-400">Product</th>
                                <th class="py-4 px-2 font-semibold text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-purple-400">Price</th>
                                <th class="py-4 px-2 font-semibold text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-purple-400">Quantity</th>
                                <th class="py-4 px-2 font-semibold text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-purple-400">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($order_items as $item)
                                <tr wire:key="{{$item->id}}" class="border-b border-white/10 last:border-0 hover:bg-white/5 transition-all duration-300 group">
                                    <td class="py-6 flex items-center gap-4">
                                        @php
                                            // Prefer the first associated client for QR rendering
                                            $client = $item->serverClients->first();
                                            $qrDataUrl = $client?->qr_code; // data:image/png;base64,... generated accessor
                                        @endphp
                                        <div class="relative">
                                            @if($qrDataUrl)
                                                <img
                                                    class="h-56 w-56 sm:h-64 sm:w-64 rounded-xl object-contain bg-white p-3 border border-white/30 shadow-md group-hover:scale-105 transition-transform duration-300"
                                                    src="{{ $qrDataUrl }}"
                                                    alt="Connection QR code for {{ $item->name }}"
                                                >
                                            @else
                                                <img
                                                    class="h-24 w-24 rounded-xl object-cover border border-white/20 group-hover:scale-110 transition-transform duration-300"
                                                    src="{{ url('storage/' . $item->serverPlan->product_image) }}"
                                                    alt="Product image"
                                                >
                                            @endif
                                            <div class="absolute -top-2 -right-2 w-6 h-6 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center border-2 border-white/20">
                                                <span class="text-xs font-bold text-white">{{$item->quantity}}</span>
                                            </div>
                                        </div>
                                        <div class="flex flex-col">
                                            <span class="font-semibold text-white group-hover:text-blue-300 transition-colors duration-300">{{$item->name}}</span>
                                            @if($client && $qrDataUrl)
                                                <a href="{{$qrDataUrl}}" download="client-qr-{{$client->id}}.png" class="text-xs text-blue-300 hover:text-blue-200 underline mt-1">Download QR code</a>
                                            @elseif($client && $client->qr_code_client)
                                                @php
                                                    $qrPath = $client->qr_code_client;
                                                    if (is_string($qrPath) && str_starts_with($qrPath, 'public/')) {
                                                        $qrPath = substr($qrPath, 7);
                                                    }
                                                    $qrUrl = url('storage/' . ltrim($qrPath, '/'));
                                                @endphp
                                                <a href="{{$qrUrl}}" download="client-qr-{{$client->id}}.png" class="text-xs text-blue-300 hover:text-blue-200 underline mt-1">Download QR code</a>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="py-6 text-blue-300 font-medium">{{Number::currency($item->unit_amount)}}</td>
                                    <td class="py-6 text-center">
                                        <span class="inline-flex items-center justify-center w-8 h-8 bg-gradient-to-br from-purple-500/20 to-pink-500/20 backdrop-blur-md rounded-full text-purple-300 font-bold border border-purple-400/30">
                                            {{$item->quantity}}
                                        </span>
                                    </td>
                                    <td class="py-6 font-bold text-transparent bg-clip-text bg-gradient-to-r from-green-400 to-blue-400">{{Number::currency($item->total_amount)}}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="md:w-1/4 w-full">
                <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl shadow-2xl p-6 hover:shadow-3xl transition-all duration-500">
                    <h2 class="text-xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-blue-400 to-purple-400 mb-6 flex items-center gap-2">
                        <svg class="w-6 h-6 text-blue-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                        Order Summary
                    </h2>
                    <div class="space-y-4">
                        <div class="flex justify-between items-center py-2 border-b border-white/10">
                            <span class="text-gray-300">Subtotal</span>
                            <span class="text-white font-medium">{{Number::currency($item->order->grand_amount)}}</span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-white/10">
                            <span class="text-gray-300">Taxes</span>
                            <span class="text-green-300 font-medium">{{Number::currency(0)}}</span>
                        </div>
                        <div class="flex justify-between items-center py-2 border-b border-white/10">
                            <span class="text-gray-300">Shipping</span>
                            <span class="text-green-300 font-medium">{{Number::currency(0)}}</span>
                        </div>
                        <div class="bg-gradient-to-r from-blue-500/10 to-purple-500/10 backdrop-blur-sm rounded-xl p-4 border border-blue-400/20">
                            <div class="flex justify-between items-center">
                                <span class="text-lg font-bold text-white">Grand Total</span>
                                <span class="text-xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-green-400 to-blue-400">{{Number::currency($item->order->grand_amount)}}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
