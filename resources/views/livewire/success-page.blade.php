@php
    use App\Models\ServerClient;
    use Illuminate\Support\Str;
    $publicPath = $publicPath ?? null;
    $filename = $filename ?? 'qr-code.png';
    $serverClient = ServerClient::where('email', 'like', "%#ID {$order->customer_id}")
        ->whereNotNull('qr_code_client')
        ->latest()
        ->first();
@endphp

<div class="w-full bg-gradient-to-r from-green-900 to-green-600 py-12 px-4 sm:px-6 lg:px-10 flex justify-center">
    <div class="container max-w-7xl w-full">
        <section class="flex flex-col items-center font-mono dark:bg-gray-800">
            <div class="w-full bg-white dark:bg-gray-900 border rounded-md p-4 sm:p-6 md:p-8 lg:p-10">
                <h1 class="mb-6 text-xl sm:text-2xl font-semibold text-gray-700 dark:text-gray-300 text-center">Thank you. Your order has been received.</h1>

                <!-- Customer Details -->
                <div class="flex flex-col md:flex-row md:space-x-6 border-b border-gray-200 dark:border-gray-700 mb-8 px-2">
                    <div class="flex-1 space-y-2 text-sm text-gray-600 dark:text-gray-400">
                        <p>Invoice #: <strong>{{ $order->invoice?->id ?? 'N/A' }}</strong></p>
                        <p>Name: <strong>{{ $order->customer?->name ?? 'Customer' }}</strong></p>
                        <p>Email: {{ $order->customer?->email ?? 'N/A' }}</p>
                        <p>Telegram ID: {{ $order->customer?->telegram_id ?? 'N/A' }}</p>
                        <p>Is affiliated: {{ $order->customer?->is_agent ? 'true' : 'false' }}</p>
                    </div>
                </div>

                <!-- Order Summary -->
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 border-b border-gray-200 dark:border-gray-700 mb-10 pb-4">
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Order Number:</p>
                        <p class="text-base font-semibold text-gray-800 dark:text-gray-400">{{ $order->id }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Date:</p>
                        <p class="text-base font-semibold text-gray-800 dark:text-gray-400">{{ $order->created_at }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Total:</p>
                        <p class="text-base font-semibold text-green-400 dark:text-gray-400">{{ Number::currency($order->grand_amount) }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Payment Method:</p>
                        <p class="text-base font-semibold text-gray-800 dark:text-gray-400">{{ $order->paymentMethod?->name ?? 'N/A' }}</p>
                    </div>
                </div>

                <!-- Order Details -->
                <div class="flex flex-col md:flex-row md:space-x-8 space-y-6 md:space-y-0 px-2 mb-10">
                    <div class="flex-1 space-y-4">
                        <h2 class="text-xl font-semibold text-gray-700 dark:text-gray-400">Order details</h2>
                        <div class="space-y-3 border-b border-gray-200 dark:border-gray-700 pb-4">
                            <div class="flex justify-between">
                                <p class="text-base text-gray-800 dark:text-gray-400">Subtotal</p>
                                <p class="text-base text-gray-600 dark:text-gray-400">{{ Number::currency($order->grand_amount) }}</p>
                            </div>
                            <div class="flex justify-between">
                                <p class="text-base text-gray-800 dark:text-gray-400">Discount</p>
                                <p class="text-base text-gray-600 dark:text-gray-400">{{ Number::currency(0) }}</p>
                            </div>
                            <div class="flex justify-between">
                                <p class="text-base text-gray-800 dark:text-gray-400">Shipping</p>
                                <p class="text-base text-gray-600 dark:text-gray-400">{{ Number::currency(0) }}</p>
                            </div>
                        </div>
                        <div class="flex justify-between">
                            <p class="text-base font-semibold text-gray-800 dark:text-gray-400">Total</p>
                            <p class="text-base font-semibold text-gray-600 dark:text-gray-400">{{ Number::currency($order->grand_amount) }}</p>
                        </div>
                    </div>

                    <div class="flex-1 space-y-4">
                        <h2 class="text-xl font-semibold text-gray-700 dark:text-gray-400">No Shipping</h2>
                        <div class="flex items-start space-x-3">
                            <x-heroicon-o-arrow-down-tray class="w-8 h-8 text-green-400 dark:text-green-400" />
                            <div>
                                <p class="text-lg font-semibold text-gray-800 dark:text-gray-400">Downloadable</p>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Check your orders page</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex flex-col sm:flex-row gap-4 px-2">
                    <a href="/servers" class="w-full sm:w-auto text-center px-4 py-2 border border-yellow-600 text-green-400 rounded-md hover:text-white hover:bg-yellow-600 dark:border-gray-700 dark:hover:bg-gray-700 dark:text-gray-300">Go back shopping</a>
                    <a href="/my-orders" class="w-full sm:w-auto text-center px-4 py-2 bg-green-400 text-white rounded-md hover:bg-yellow-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">View My Orders</a>
                </div>

                <!-- QR Code / Download -->
                @if ($serverClient && $serverClient->qr_code_client)
                    <div class="text-center mt-6">
                        <img src="{{ asset('storage/' . $serverClient->qr_code_client) }}" class="h-32 w-32 mx-auto rounded shadow-md" alt="Client QR Code">
                        <br>
                        <a href="{{ asset('storage/' . $serverClient->qr_code_client) }}" download class="text-sm text-primary hover:underline">Download QR Code</a>
                    </div>
                @else
                    <div class="text-center mt-6 text-gray-400 italic">No QR</div>
                @endif
            </div>
        </section>
    </div>
</div>