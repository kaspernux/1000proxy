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

<div class="min-h-screen bg-gradient-to-br from-gray-900 via-gray-800 to-blue-900 py-8 px-4 sm:px-6 lg:px-8 relative overflow-hidden">
    <!-- Animated background elements -->
    <div class="absolute inset-0">
        <div class="absolute inset-0 bg-gradient-to-r from-blue-600/15 to-yellow-500/15 animate-pulse"></div>
        <div class="absolute top-0 left-0 w-full h-full bg-gradient-to-b from-transparent to-gray-900/60"></div>
    </div>

    <!-- Floating shapes with enhanced animations -->
    <div class="absolute inset-0 overflow-hidden">
        <div class="absolute -top-40 -right-32 w-80 h-80 bg-gradient-to-br from-yellow-400/25 to-blue-400/25 rounded-full blur-3xl animate-bounce duration-[6000ms]"></div>
        <div class="absolute -bottom-40 -left-32 w-80 h-80 bg-gradient-to-br from-blue-400/20 to-yellow-400/15 rounded-full blur-3xl animate-pulse duration-[8000ms]"></div>
        <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-96 h-96 bg-gradient-to-r from-purple-400/10 to-pink-400/10 rounded-full blur-2xl animate-spin duration-[20000ms]"></div>
    </div>

    <div class="max-w-7xl mx-auto relative z-10">

        <!-- Enhanced Success Header -->
        <div class="text-center mb-12">
            <!-- Success Animation -->
            <div class="mx-auto flex items-center justify-center w-24 h-24 mb-8 relative">
                <div class="absolute inset-0 bg-gradient-to-r from-green-400 to-blue-500 rounded-full animate-pulse"></div>
                <div class="relative flex items-center justify-center w-20 h-20 bg-gradient-to-r from-green-500 to-green-400 rounded-full shadow-2xl">
                    <svg class="w-12 h-12 text-white animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
            </div>

            <!-- Enhanced Titles -->
            <h1 class="text-4xl md:text-6xl lg:text-7xl font-extrabold text-white mb-4 leading-tight">
                <span class="bg-gradient-to-r from-green-400 via-blue-400 to-green-500 bg-clip-text text-transparent animate-pulse">
                    Order Successful!
                </span>
            </h1>
            <p class="text-lg md:text-xl text-gray-300 font-light max-w-3xl mx-auto">
                Thank you for choosing our premium proxy services. Your order has been received and is being processed with the highest priority.
            </p>

            <!-- Enhanced breadcrumb -->
            <nav class="flex justify-center items-center space-x-2 text-sm mt-6">
                <a href="/" wire:navigate class="text-gray-400 hover:text-white transition-colors duration-200">Home</a>
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
                <a href="/checkout" wire:navigate class="text-gray-400 hover:text-white transition-colors duration-200">Checkout</a>
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
                <span class="text-green-400 font-medium">Success</span>
            </nav>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

            <!-- Enhanced Main Order Details -->
            <div class="lg:col-span-2 space-y-6">

                <!-- Enhanced Order Summary Card -->
                <div class="bg-white/5 backdrop-blur-lg border border-white/10 rounded-3xl p-8 shadow-2xl relative overflow-hidden">
                    <!-- Background decoration -->
                    <div class="absolute top-0 right-0 w-64 h-64 bg-gradient-to-br from-green-400/10 to-blue-400/10 rounded-full blur-3xl"></div>
                    
                    <div class="relative z-10">
                        <div class="flex items-center justify-between mb-8">
                            <h2 class="text-2xl font-bold text-white">Order Summary</h2>
                            <div class="flex items-center space-x-2">
                                <span class="px-4 py-2 text-sm font-medium rounded-xl shadow-lg
                                    {{ $order->payment_status === 'paid' ? 'bg-gradient-to-r from-green-500 to-green-400 text-white' : 'bg-gradient-to-r from-yellow-500 to-yellow-400 text-white' }}">
                                    {{ ucfirst($order->payment_status) }}
                                </span>
                            </div>
                        </div>

                        <!-- Enhanced Order Info Grid -->
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                            <div class="group text-center p-6 bg-white/5 backdrop-blur-sm hover:bg-white/10 rounded-2xl transition-all duration-300 hover:scale-105 border border-white/10">
                                <p class="text-sm text-gray-400 mb-2">Order Number</p>
                                <p class="font-bold text-white text-lg group-hover:text-blue-300 transition-colors">#{{ $order->id }}</p>
                            </div>
                            <div class="group text-center p-6 bg-white/5 backdrop-blur-sm hover:bg-white/10 rounded-2xl transition-all duration-300 hover:scale-105 border border-white/10">
                                <p class="text-sm text-gray-400 mb-2">Order Date</p>
                                <p class="font-bold text-white text-lg group-hover:text-blue-300 transition-colors">{{ $order->created_at->format('M d, Y') }}</p>
                            </div>
                        <div class="text-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Payment Method</p>
                            <p class="font-semibold text-gray-900 dark:text-white">{{ $order->paymentMethod?->name ?? 'N/A' }}</p>
                        </div>
                        <div class="text-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                            <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Total Amount</p>
                            <p class="font-bold text-green-600 dark:text-green-400 text-lg">{{ Number::currency($order->grand_amount) }}</p>
                        </div>
                    </div>

                    <!-- Order Progress -->
                    @if($orderProgress)
                    <div class="mb-6">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="text-sm font-medium text-gray-900 dark:text-white">Order Progress</h3>
                            <span class="text-sm text-gray-500 dark:text-gray-400">{{ $orderProgress['current_step'] }}/{{ $orderProgress['total_steps'] }}</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                            <div class="bg-green-600 h-2 rounded-full transition-all duration-300" style="width: {{ $orderProgress['percentage'] }}%"></div>
                        </div>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">Estimated delivery: {{ $estimatedDelivery }}</p>
                    </div>
                    @endif

                    <!-- Payment Details -->
                    @if(!empty($paymentDetails))
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Payment Details</h3>
                        <div class="space-y-2">
                            @foreach($paymentDetails as $key => $value)
                                @if($value)
                                <div class="flex justify-between">
                                    <span class="text-gray-600 dark:text-gray-400">{{ ucfirst(str_replace('_', ' ', $key)) }}:</span>
                                    <span class="font-medium text-gray-900 dark:text-white">{{ $value }}</span>
                                </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>

                <!-- Order Items -->
                <div class="backdrop-blur-sm bg-white/80 dark:bg-gray-800/80 border border-gray-200 dark:border-gray-700 rounded-2xl p-6 shadow-lg">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-6">Order Items</h3>
                    <div class="space-y-4">
                        @foreach($orderItems as $item)
                        <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                            <div class="flex-1">
                                <h4 class="font-semibold text-gray-900 dark:text-white">{{ $item->serverPlan?->name ?? 'Server Plan' }}</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Quantity: {{ $item->quantity }}</p>
                                @if($item->serverPlan?->description)
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ Str::limit($item->serverPlan->description, 100) }}</p>
                                @endif
                            </div>
                            <div class="text-right">
                                <p class="font-semibold text-gray-900 dark:text-white">{{ Number::currency($item->total_amount) }}</p>
                                <p class="text-sm text-gray-500 dark:text-gray-400">{{ Number::currency($item->unit_amount) }} each</p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                <!-- QR Code Section -->
                @if ($serverClient && $serverClient->qr_code_client)
                <div class="backdrop-blur-sm bg-white/80 dark:bg-gray-800/80 border border-gray-200 dark:border-gray-700 rounded-2xl p-6 shadow-lg text-center">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Quick Access</h3>
                    <div class="inline-block p-4 bg-white dark:bg-gray-900 rounded-lg shadow-md">
                        <img src="{{ asset('storage/' . $serverClient->qr_code_client) }}" class="h-32 w-32 mx-auto" alt="Client QR Code">
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-4 mb-4">Scan this QR code to quickly access your server configuration</p>
                    <a href="{{ asset('storage/' . $serverClient->qr_code_client) }}" download
                       class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                        <x-heroicon-o-arrow-down-tray class="w-4 h-4 mr-2" />
                        Download QR Code
                    </a>
                </div>
                @endif
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">

                <!-- Customer Info -->
                <div class="backdrop-blur-sm bg-white/80 dark:bg-gray-800/80 border border-gray-200 dark:border-gray-700 rounded-2xl p-6 shadow-lg">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Customer Information</h3>
                    <div class="space-y-3">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Name</p>
                            <p class="font-medium text-gray-900 dark:text-white">{{ $order->customer?->name ?? 'Customer' }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Email</p>
                            <p class="font-medium text-gray-900 dark:text-white">{{ $order->customer?->email ?? 'N/A' }}</p>
                        </div>
                        @if($order->customer?->telegram_id)
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Telegram ID</p>
                            <p class="font-medium text-gray-900 dark:text-white">{{ $order->customer->telegram_id }}</p>
                        </div>
                        @endif
                        @if($order->invoice)
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Invoice #</p>
                            <p class="font-medium text-gray-900 dark:text-white">{{ $order->invoice->id }}</p>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="backdrop-blur-sm bg-white/80 dark:bg-gray-800/80 border border-gray-200 dark:border-gray-700 rounded-2xl p-6 shadow-lg">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Quick Actions</h3>
                    <div class="space-y-3">
                        <button wire:click="trackOrder"
                                class="w-full flex items-center justify-center px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                            <x-heroicon-o-magnifying-glass class="w-4 h-4 mr-2" />
                            Track Order
                        </button>

                        <button wire:click="downloadInvoice"
                                class="w-full flex items-center justify-center px-4 py-3 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition-colors">
                            <x-heroicon-o-document-arrow-down class="w-4 h-4 mr-2" />
                            Download Invoice
                        </button>

                        <button wire:click="reorderItems"
                                class="w-full flex items-center justify-center px-4 py-3 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors">
                            <x-heroicon-o-arrow-path class="w-4 h-4 mr-2" />
                            Reorder Items
                        </button>

                        <button wire:click="contactSupport"
                                class="w-full flex items-center justify-center px-4 py-3 bg-orange-600 hover:bg-orange-700 text-white rounded-lg transition-colors">
                            <x-heroicon-o-chat-bubble-left-right class="w-4 h-4 mr-2" />
                            Contact Support
                        </button>
                    </div>
                </div>

                <!-- Follow-up Actions -->
                <div class="backdrop-blur-sm bg-white/80 dark:bg-gray-800/80 border border-gray-200 dark:border-gray-700 rounded-2xl p-6 shadow-lg">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Stay Connected</h3>
                    <div class="space-y-4">
                        <div class="flex items-center">
                            <input type="checkbox" wire:model="subscribeToNewsletter" id="newsletter"
                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <label for="newsletter" class="ml-3 text-sm text-gray-700 dark:text-gray-300">
                                Subscribe to newsletter for deals and updates
                            </label>
                        </div>

                        <div class="flex items-center">
                            <input type="checkbox" wire:model="joinLoyaltyProgram" id="loyalty"
                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <label for="loyalty" class="ml-3 text-sm text-gray-700 dark:text-gray-300">
                                Join loyalty program for exclusive benefits
                            </label>
                        </div>

                        <div class="pt-2">
                            @if($subscribeToNewsletter)
                            <button wire:click="subscribeNewsletter"
                                    class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                                Subscribe to Newsletter
                            </button>
                            @endif

                            @if($joinLoyaltyProgram)
                            <button wire:click="joinLoyalty"
                                    class="w-full px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition-colors mt-2">
                                Join Loyalty Program
                            </button>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Order Feedback -->
                @if(!$showFeedbackForm)
                <div class="backdrop-blur-sm bg-white/80 dark:bg-gray-800/80 border border-gray-200 dark:border-gray-700 rounded-2xl p-6 shadow-lg">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">Share Your Experience</h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">How was your ordering experience?</p>
                    <button wire:click="$set('showFeedbackForm', true)"
                            class="w-full px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white rounded-lg transition-colors">
                        Leave Feedback
                    </button>
                </div>
                @endif
            </div>
        </div>

        <!-- Related Products -->
        @if($showProductRecommendations && ($relatedProducts->count() > 0 || $recommendedProducts->count() > 0))
        <div class="mt-12">
            @if($relatedProducts->count() > 0)
            <div class="backdrop-blur-sm bg-white/80 dark:bg-gray-800/80 border border-gray-200 dark:border-gray-700 rounded-2xl p-6 shadow-lg mb-8">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-2xl font-bold text-gray-900 dark:text-white">Related Products</h3>
                    <button wire:click="$set('showProductRecommendations', false)"
                            class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                        <x-heroicon-o-x-mark class="w-5 h-5" />
                    </button>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    @foreach($relatedProducts as $product)
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-2">{{ $product->name }}</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">{{ Str::limit($product->description, 60) }}</p>
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-green-600 dark:text-green-400">{{ Number::currency($product->price) }}</span>
                            <button wire:click="addToCart({{ $product->id }})"
                                    class="px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded transition-colors">
                                Add to Cart
                            </button>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            @if($recommendedProducts->count() > 0)
            <div class="backdrop-blur-sm bg-white/80 dark:bg-gray-800/80 border border-gray-200 dark:border-gray-700 rounded-2xl p-6 shadow-lg">
                <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Recommended for You</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    @foreach($recommendedProducts as $product)
                    <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4">
                        <div class="flex items-center mb-2">
                            <span class="px-2 py-1 text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400 rounded">
                                Featured
                            </span>
                        </div>
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-2">{{ $product->name }}</h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">{{ Str::limit($product->description, 60) }}</p>
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-green-600 dark:text-green-400">{{ Number::currency($product->price) }}</span>
                            <button wire:click="addToCart({{ $product->id }})"
                                    class="px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded transition-colors">
                                Add to Cart
                            </button>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
        @endif

        <!-- Continue Shopping -->
        <div class="mt-8 text-center">
            <div class="inline-flex space-x-4">
                <a href="{{ route('my.orders') }}"
                   class="px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition-colors">
                    View My Orders
                </a>
                <a href="{{ route('servers') }}"
                   class="px-6 py-3 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors">
                    Continue Shopping
                </a>
            </div>
        </div>
    </div>

    <!-- Feedback Modal -->
    @if($showFeedbackForm)
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center p-4 z-50">
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 w-full max-w-md">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Order Feedback</h3>
                <button wire:click="$set('showFeedbackForm', false)"
                        class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                    <x-heroicon-o-x-mark class="w-5 h-5" />
                </button>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Rating</label>
                    <div class="flex space-x-1">
                        @for($i = 1; $i <= 5; $i++)
                        <button wire:click="$set('orderRating', {{ $i }})"
                                class="text-2xl {{ $orderRating >= $i ? 'text-yellow-400' : 'text-gray-300 dark:text-gray-600' }}">
                            ★
                        </button>
                        @endfor
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Comments (Optional)</label>
                    <textarea wire:model="orderFeedback"
                              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                              rows="3"
                              placeholder="Share your experience..."></textarea>
                </div>

                <div class="flex space-x-3">
                    <button wire:click="$set('showFeedbackForm', false)"
                            class="flex-1 px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-700 rounded-lg transition-colors">
                        Cancel
                    </button>
                    <button wire:click="submitFeedback"
                            class="flex-1 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                        Submit
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Support Modal -->
    @if($showSupportModal)
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center p-4 z-50">
        <div class="bg-white dark:bg-gray-800 rounded-2xl p-6 w-full max-w-md">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Contact Support</h3>
                <button wire:click="$set('showSupportModal', false)"
                        class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                    <x-heroicon-o-x-mark class="w-5 h-5" />
                </button>
            </div>

            <div class="space-y-4">
                <p class="text-gray-600 dark:text-gray-400">Need help with your order? Here are your options:</p>

                <div class="space-y-2">
                    <a href="mailto:support@1000proxies.com?subject=Order%20{{ $order->id }}%20Support"
                       class="flex items-center p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/30 transition-colors">
                        <x-heroicon-o-envelope class="w-5 h-5 text-blue-600 dark:text-blue-400 mr-3" />
                        <span class="text-blue-600 dark:text-blue-400 font-medium">Email Support</span>
                    </a>

                    @if($order->customer?->telegram_id)
                    <a href="https://t.me/proxysupport"
                       class="flex items-center p-3 bg-green-50 dark:bg-green-900/20 rounded-lg hover:bg-green-100 dark:hover:bg-green-900/30 transition-colors">
                        <x-heroicon-o-chat-bubble-left-right class="w-5 h-5 text-green-600 dark:text-green-400 mr-3" />
                        <span class="text-green-600 dark:text-green-400 font-medium">Telegram Support</span>
                    </a>
                    @endif

                    <div class="flex items-center p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                        <x-heroicon-o-clock class="w-5 h-5 text-gray-600 dark:text-gray-400 mr-3" />
                        <span class="text-gray-600 dark:text-gray-400">Response time: 2-4 hours</span>
                    </div>
                </div>

                <button wire:click="$set('showSupportModal', false)"
                        class="w-full px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-700 rounded-lg transition-colors">
                    Close
                </button>
            </div>
        </div>
    </div>
    @endif
