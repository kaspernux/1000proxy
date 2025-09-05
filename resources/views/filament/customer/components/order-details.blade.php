<div class="space-y-6 text-gray-900 dark:text-gray-100">
    <!-- Enhanced Order Header -->
    <div class="relative bg-gradient-to-r from-blue-900/50 to-purple-900/50 backdrop-blur-lg rounded-2xl p-6 border border-blue-500/20 overflow-hidden text-white">
        <div class="absolute inset-0 bg-gradient-to-r from-blue-600/5 to-purple-600/5"></div>
        <div class="relative z-10">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-2xl font-bold flex items-center">
                        <svg class="w-7 h-7 mr-3 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                        </svg>
                        Order #{{ $order->id }}
                    </h3>
                    <p class="mt-2 text-blue-100 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a4 4 0 118 0v4m-4 8a2 2 0 100-4 2 2 0 000 4zm6-10V7a6 6 0 10-12 0v4a2 2 0 002 2h8a2 2 0 002-2z"></path>
                        </svg>
                        Placed {{ $order->created_at->format('M j, Y \a\t H:i') }}
                        <span class="ml-2 text-blue-300">({{ $order->created_at->diffForHumans() }})</span>
                    </p>
                </div>
                <div class="flex items-center space-x-3">
                    <span class="inline-flex items-center px-4 py-2 rounded-xl text-sm font-semibold border
                        {{ $order->status === 'completed' ? 'bg-green-500/20 text-green-300 border-green-500/30' :
                           ($order->status === 'pending' ? 'bg-yellow-500/20 text-yellow-300 border-yellow-500/30' :
                            ($order->status === 'processing' ? 'bg-blue-500/20 text-blue-300 border-blue-500/30' :
                             ($order->status === 'cancelled' ? 'bg-red-500/20 text-red-300 border-red-500/30' : 'bg-gray-500/20 text-gray-300 border-gray-500/30'))) }}">
                        {{ ucfirst($order->status) }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Enhanced Order Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="group relative bg-gradient-to-br from-green-500/10 to-green-600/20 backdrop-blur-lg rounded-2xl p-6 border border-green-500/20 hover:border-green-400/40 transition-all duration-300 hover:scale-105 overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-br from-green-400/5 to-transparent group-hover:from-green-400/10 transition-all duration-300"></div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-green-500/20 rounded-xl">
                        <svg class="w-8 h-8 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                        </svg>
                    </div>
                </div>
                <h4 class="text-lg font-semibold text-green-300 mb-2">Order Total</h4>
                <p class="text-3xl font-bold text-white">${{ number_format($order->total_amount, 2) }}</p>
                <p class="text-green-300 mt-2 flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    {{ ucfirst($order->payment_method) }} payment
                </p>
            </div>
        </div>

        <div class="group relative bg-gradient-to-br from-blue-500/10 to-blue-600/20 backdrop-blur-lg rounded-2xl p-6 border border-blue-500/20 hover:border-blue-400/40 transition-all duration-300 hover:scale-105 overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-br from-blue-400/5 to-transparent group-hover:from-blue-400/10 transition-all duration-300"></div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-blue-500/20 rounded-xl">
                        <svg class="w-8 h-8 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"></path>
                        </svg>
                    </div>
                </div>
                <h4 class="text-lg font-semibold text-blue-300 mb-2">Items</h4>
                <p class="text-3xl font-bold text-white">{{ $order->items->count() }}</p>
                <p class="text-blue-300 mt-2">Server access{{ $order->items->count() > 1 ? 'es' : '' }}</p>
            </div>
        </div>

        <div class="group relative bg-gradient-to-br from-purple-500/10 to-pink-600/20 backdrop-blur-lg rounded-2xl p-6 border border-purple-500/20 hover:border-purple-400/40 transition-all duration-300 hover:scale-105 overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-br from-purple-400/5 to-transparent group-hover:from-purple-400/10 transition-all duration-300"></div>
            <div class="relative z-10">
                <div class="flex items-center justify-between mb-4">
                    <div class="p-3 bg-purple-500/20 rounded-xl">
                        <svg class="w-8 h-8 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
                <h4 class="text-lg font-semibold text-purple-300 mb-2">Next Expiry</h4>
                @php
                    // expiry_time is stored in milliseconds (int) per 3X-UI
                    $nextExpiryMs = $order->items
                        ->map(fn($item) => $item->server_client?->expiry_time)
                        ->filter(fn($v) => !is_null($v) && (int) $v > 0)
                        ->sort()
                        ->first();
                    $nextExpiry = $nextExpiryMs ? \Carbon\Carbon::createFromTimestampMs((int) $nextExpiryMs) : null;
                @endphp
                @if($nextExpiry)
                    <p class="text-3xl font-bold text-white">{{ $nextExpiry->format('M j') }}</p>
                    <p class="text-purple-300 mt-2">{{ $nextExpiry->diffForHumans() }}</p>
                @else
                    <p class="text-3xl font-bold text-gray-400">N/A</p>
                    <p class="text-purple-300 mt-2">No expiry data</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Order Items -->
    <div>
        <h4 class="text-lg font-medium text-white mb-4">Order Items</h4>
        <div class="space-y-4">
            @foreach($order->items as $item)
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <h5 class="font-medium text-gray-900">
                                {{ $item->server->name ?? 'Server Access' }}
                            </h5>
                            <div class="mt-1 text-sm text-gray-500 space-y-1">
                                @if($item->server)
                                    <p>Location: {{ $item->server->country ?? 'Unknown' }}</p>
                                    <p>IP: {{ $item->server->ip ?? 'N/A' }}</p>
                                    <p>Port: {{ $item->server->port ?? 'N/A' }}</p>
                                @endif
                                @if($item->server_client)
                                    <p>UUID: <code class="bg-gray-100 px-1 rounded">{{ $item->server_client->uuid }}</code></p>
                                    <p>Status:
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                            {{ $item->server_client->enable ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                            {{ $item->server_client->enable ? 'Active' : 'Inactive' }}
                                        </span>
                                    </p>
                                    <p>
                                        @php
                                            $ms = $item->server_client?->expiry_time;
                                        @endphp
                                        Expires:
                                        {{ ($ms && (int) $ms > 0) ? \Carbon\Carbon::createFromTimestampMs((int) $ms)->format('M j, Y H:i') : 'Never' }}
                                    </p>
                                @endif
                            </div>
                        </div>
                        <div class="ml-4 text-right">
                            <p class="font-medium text-gray-900">${{ number_format($item->price, 2) }}</p>
                            <p class="text-sm text-gray-500">Qty: {{ $item->quantity }}</p>
                        </div>
                    </div>

                    @if($item->server_client && $order->status === 'completed')
                        <div class="mt-4 pt-4 border-t border-gray-200">
                            <h6 class="text-sm font-medium text-gray-900 mb-2">Configuration</h6>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- VLESS Config -->
                                <div class="bg-gray-50 dark:bg-gray-800/60 rounded p-3 border border-gray-200 dark:border-gray-700">
                                    <h7 class="text-xs font-medium text-gray-700 uppercase tracking-wide">VLESS</h7>
                                    <div class="mt-1 flex items-center space-x-2">
                    <code data-config="vless-{{ $item->id }}" class="flex-1 text-xs bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 border border-gray-200 dark:border-gray-700 rounded px-2 py-1 truncate">
                                            vless://{{ $item->server_client->uuid }}@{{ $item->server->ip }}:{{ $item->server->port }}
                                        </code>
                                        <button onclick="copyToClipboard('vless-{{ $item->id }}')"
                                                class="flex-shrink-0 p-1 text-gray-400 hover:text-gray-600">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                <!-- VMess Config -->
                                <div class="bg-gray-50 dark:bg-gray-800/60 rounded p-3 border border-gray-200 dark:border-gray-700">
                                    <h7 class="text-xs font-medium text-gray-700 uppercase tracking-wide">VMess</h7>
                                    <div class="mt-1 flex items-center space-x-2">
                    <code data-config="vmess-{{ $item->id }}" class="flex-1 text-xs bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 border border-gray-200 dark:border-gray-700 rounded px-2 py-1 truncate">
                                            vmess://{{ base64_encode(json_encode([
                                                'v' => '2',
                                                'ps' => $item->server->name,
                                                'add' => $item->server->ip,
                                                'port' => $item->server->port,
                                                'id' => $item->server_client->uuid,
                                                'aid' => '0',
                                                'net' => 'tcp'
                                            ])) }}
                                        </code>
                                        <button onclick="copyToClipboard('vmess-{{ $item->id }}')"
                                                class="flex-shrink-0 p-1 text-gray-400 hover:text-gray-600">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Configuration Links (Client, Subscription, JSON) -->
                            <div class="mt-3 bg-gray-50 rounded p-3">
                                <h7 class="text-xs font-medium text-gray-700 uppercase tracking-wide">Configuration Links</h7>
                                @php
                                    $clientLink = $item->server_client?->client_link;
                                    $subLink = $item->server_client?->remote_sub_link;
                                    $jsonLink = $item->server_client?->remote_json_link;
                                @endphp

                                {{-- Client Link --}}
                                <div class="mt-1 flex items-center gap-2">
                                    <span class="text-[11px] font-medium text-gray-600 w-20">Client</span>
                                    @if($clientLink)
                                        <a href="{{ $clientLink }}" target="_blank" rel="noopener" class="text-blue-600 hover:text-blue-700 underline text-xs">Open</a>
                                        <code data-config="client-{{ $item->id }}" class="flex-1 text-xs bg-white text-gray-800 border rounded px-2 py-1 break-all">{{ $clientLink }}</code>
                                        <button onclick="copyToClipboard('client-{{ $item->id }}')"
                                                class="flex-shrink-0 p-1 text-gray-400 hover:text-gray-600" title="Copy">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                            </svg>
                                        </button>
                                    @else
                                        <span class="text-xs text-gray-500">Not available</span>
                                    @endif
                                </div>

                                {{-- Subscription Link --}}
                                <div class="mt-2 flex items-center gap-2">
                                    <span class="text-[11px] font-medium text-gray-600 w-20">Subscription</span>
                                    @if($subLink)
                                        <a href="{{ $subLink }}" target="_blank" rel="noopener" class="text-blue-600 hover:text-blue-700 underline text-xs">Open</a>
                                        <code data-config="sub-{{ $item->id }}" class="flex-1 text-xs bg-white text-gray-800 border rounded px-2 py-1 break-all">{{ $subLink }}</code>
                                        <button onclick="copyToClipboard('sub-{{ $item->id }}')"
                                                class="flex-shrink-0 p-1 text-gray-400 hover:text-gray-600" title="Copy">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                            </svg>
                                        </button>
                                    @else
                                        <span class="text-xs text-gray-500">Not available</span>
                                    @endif
                                </div>

                                {{-- JSON Subscription Link --}}
                                <div class="mt-2 flex items-center gap-2">
                                    <span class="text-[11px] font-medium text-gray-600 w-20">JSON</span>
                                    @if($jsonLink)
                                        <a href="{{ $jsonLink }}" target="_blank" rel="noopener" class="text-blue-600 hover:text-blue-700 underline text-xs">Open</a>
                                        <code data-config="json-{{ $item->id }}" class="flex-1 text-xs bg-white text-gray-800 border rounded px-2 py-1 break-all">{{ $jsonLink }}</code>
                                        <button onclick="copyToClipboard('json-{{ $item->id }}')"
                                                class="flex-shrink-0 p-1 text-gray-400 hover:text-gray-600" title="Copy">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                            </svg>
                                        </button>
                                    @else
                                        <span class="text-xs text-gray-500">Not available</span>
                                    @endif
                                </div>
                            </div>

                            <!-- QR Codes: Client, Subscription, JSON (if available) -->
                            <div class="mt-3">
                                @php
                                    $clientQr = $item->server_client?->qr_code; // data URI accessor
                                    // Build web URLs for stored QR images for sub/json if present
                                    $subQrPath = $item->server_client?->qr_code_sub;
                                    $jsonQrPath = $item->server_client?->qr_code_sub_json;
                                    $subQr = null;
                                    $jsonQr = null;
                                    if (is_string($subQrPath) && str_starts_with($subQrPath, 'data:image')) {
                                        $subQr = $subQrPath;
                                    } elseif (!empty($subQrPath)) {
                                        $norm = ltrim($subQrPath, '/');
                                        if (str_starts_with($norm, 'public/')) $norm = substr($norm, 7);
                                        $subQr = asset('storage/' . $norm);
                                    }
                                    if (is_string($jsonQrPath) && str_starts_with($jsonQrPath, 'data:image')) {
                                        $jsonQr = $jsonQrPath;
                                    } elseif (!empty($jsonQrPath)) {
                                        $norm2 = ltrim($jsonQrPath, '/');
                                        if (str_starts_with($norm2, 'public/')) $norm2 = substr($norm2, 7);
                                        $jsonQr = asset('storage/' . $norm2);
                                    }
                                @endphp
                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-center">
                                    @if($clientQr)
                                        <div class="bg-white rounded p-2 border">
                                            <img src="{{ $clientQr }}" alt="Client QR" class="mx-auto w-80 h-80 object-contain" />
                                            <p class="text-xs text-gray-600 mt-1">Client QR</p>
                                        </div>
                                    @endif
                                    @if($subQr)
                                        <div class="bg-white rounded p-2 border">
                                            <img src="{{ $subQr }}" alt="Subscription QR" class="mx-auto w-80 h-80 object-contain" />
                                            <p class="text-xs text-gray-600 mt-1">Subscription QR</p>
                                        </div>
                                    @endif
                                    @if($jsonQr)
                                        <div class="bg-white rounded p-2 border">
                                            <img src="{{ $jsonQr }}" alt="JSON QR" class="mx-auto w-80 h-80 object-contain" />
                                            <p class="text-xs text-gray-600 mt-1">JSON QR</p>
                                        </div>
                                    @endif
                                </div>
                                @if(!$clientQr && !$subQr && !$jsonQr)
                                    <p class="text-sm text-gray-500 text-center">QR codes unavailable</p>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    <!-- Customer's Purchased Configurations Section -->
    @if($customerOwnsPlan && !empty($customerConfigurations))
    <div class="bg-gradient-to-r from-green-600/20 to-emerald-600/20 backdrop-blur-md rounded-2xl p-6 border border-green-500/30 hover:border-green-400/50 transition-all duration-300 mb-6">
        <h3 class="text-xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-green-400 to-emerald-400 mb-4 flex items-center">
            <x-custom-icon name="check-circle" class="w-6 h-6 mr-3 text-green-400" />
            Your Active Configurations
        </h3>
        <div class="space-y-4">
            <div class="bg-green-900/20 border border-green-500/30 rounded-lg p-4">
                <p class="text-green-300 text-sm mb-4">
                    You've already purchased this plan! Here are your active proxy configurations:
                </p>

                @foreach($customerConfigurations as $index => $config)
                <div class="bg-white/5 rounded-lg p-4 mb-4 last:mb-0">
                    <div class="flex justify-between items-start mb-3">
                        <h4 class="text-white font-semibold">Configuration #{{ $index + 1 }}</h4>
                        <span class="text-xs text-green-400 bg-green-400/20 px-2 py-1 rounded-full">Active</span>
                    </div>

                    <!-- Configuration Link -->
                    @if(!empty($config['client_link']))
                    <div class="mb-3">
                        <label class="block text-gray-400 text-sm mb-2">Client Configuration:</label>
                        <div class="bg-gray-900/50 rounded-lg p-3 border border-gray-700">
                            <code class="text-green-300 text-xs break-all select-all">{{ $config['client_link'] }}</code>
                        </div>
                        <button class="mt-2 text-xs text-blue-400 hover:text-blue-300" 
                                onclick="navigator.clipboard.writeText('{{ $config['client_link'] }}')">
                            📋 Copy Link
                        </button>
                    </div>
                    @endif

                    <!-- Subscription Links -->
                    @if(!empty($config['subscription_link']))
                    <div class="mb-3">
                        <label class="block text-gray-400 text-sm mb-2">Subscription URL:</label>
                        <div class="bg-gray-900/50 rounded-lg p-3 border border-gray-700">
                            <code class="text-blue-300 text-xs break-all select-all">{{ $config['subscription_link'] }}</code>
                        </div>
                        <button class="mt-2 text-xs text-blue-400 hover:text-blue-300" 
                                onclick="navigator.clipboard.writeText('{{ $config['subscription_link'] }}')">
                            📋 Copy Subscription
                        </button>
                    </div>
                    @endif

                    <!-- QR Codes -->
                    @if(!empty($config['qr_codes']))
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @if(!empty($config['qr_codes']['client']))
                        <div class="text-center">
                            <label class="block text-gray-400 text-sm mb-2">Client QR Code:</label>
                            <div class="bg-white p-2 rounded-lg inline-block">
                                <img src="{{ $config['qr_codes']['client'] }}" 
                                        alt="Client QR Code" 
                                        class="w-32 h-32 mx-auto">
                            </div>
                        </div>
                        @endif
                        @if(!empty($config['qr_codes']['subscription']))
                        <div class="text-center">
                            <label class="block text-gray-400 text-sm mb-2">Subscription QR Code:</label>
                            <div class="bg-white p-2 rounded-lg inline-block">
                                <img src="{{ $config['qr_codes']['subscription'] }}" 
                                        alt="Subscription QR Code" 
                                        class="w-32 h-32 mx-auto">
                            </div>
                        </div>
                        @endif
                    </div>
                    @endif

                    <!-- Usage Information -->
                    @if(!empty($config['usage_info']))
                    <div class="mt-4 p-3 bg-blue-900/20 border border-blue-500/30 rounded-lg">
                        <h5 class="text-blue-300 font-medium mb-2">Usage Information:</h5>
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            @if(isset($config['usage_info']['traffic_used_mb']))
                            <div>
                                <span class="text-gray-400">Traffic Used:</span>
                                <span class="text-white">{{ number_format($config['usage_info']['traffic_used_mb']) }} MB</span>
                            </div>
                            @endif
                            @if(isset($config['usage_info']['traffic_limit_mb']))
                            <div>
                                <span class="text-gray-400">Traffic Limit:</span>
                                <span class="text-white">{{ number_format($config['usage_info']['traffic_limit_mb']) }} MB</span>
                            </div>
                            @endif
                            @if(isset($config['usage_info']['expires_at']) && $config['usage_info']['expires_at'])
                            <div>
                                <span class="text-gray-400">Expires:</span>
                                <span class="text-white">{{ \Carbon\Carbon::parse($config['usage_info']['expires_at'])->format('M j, Y') }}</span>
                            </div>
                            @endif
                            @if(isset($config['usage_info']['status']))
                            <div>
                                <span class="text-gray-400">Status:</span>
                                <span class="text-green-400 capitalize">{{ $config['usage_info']['status'] }}</span>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>
                @endforeach

                <!-- Quick Action Buttons -->
                <div class="flex flex-wrap gap-3 mt-6 pt-4 border-t border-green-500/20">
                    <a href="/customer/my-active-servers" 
                        class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-500 text-white rounded-lg transition-colors duration-200">
                        <x-custom-icon name="cog-6-tooth" class="w-4 h-4 mr-2" />
                        Manage Servers
                    </a>
                    <a href="/customer/orders" 
                        class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded-lg transition-colors duration-200">
                        <x-custom-icon name="document-text" class="w-4 h-4 mr-2" />
                        View Orders
                    </a>
                    <button onclick="window.print()" 
                            class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-500 text-white rounded-lg transition-colors duration-200">
                        <x-custom-icon name="printer" class="w-4 h-4 mr-2" />
                        Print Configs
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Order Timeline -->
    <div>
        <h4 class="text-lg font-medium text-white mb-4">Order Timeline</h4>
        <div class="flow-root">
            <ul class="-mb-8">
                <li>
                    <div class="relative pb-8">
                        <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200"></span>
                        <div class="relative flex space-x-3">
                            <div class="h-8 w-8 rounded-full bg-blue-500 flex items-center justify-center ring-8 ring-white">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                            </div>
                            <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                <div>
                                    <p class="text-sm text-gray-500">Order placed</p>
                                </div>
                                <div class="text-right text-sm whitespace-nowrap text-gray-500">
                                    {{ $order->created_at->format('M j, H:i') }}
                                </div>
                            </div>
                        </div>
                    </div>
                </li>

                @if($order->status !== 'pending')
                <li>
                    <div class="relative pb-8">
                        <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200"></span>
                        <div class="relative flex space-x-3">
                            <div class="h-8 w-8 rounded-full bg-yellow-500 flex items-center justify-center ring-8 ring-white">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                <div>
                                    <p class="text-sm text-gray-500">Payment processed</p>
                                </div>
                                <div class="text-right text-sm whitespace-nowrap text-gray-500">
                                    {{ $order->updated_at->format('M j, H:i') }}
                                </div>
                            </div>
                        </div>
                    </div>
                </li>
                @endif

                @if($order->status === 'completed')
                <li>
                    <div class="relative">
                        <div class="relative flex space-x-3">
                            <div class="h-8 w-8 rounded-full bg-green-500 flex items-center justify-center ring-8 ring-white">
                                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                            <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                <div>
                                    <p class="text-sm text-gray-500">Service activated</p>
                                </div>
                                <div class="text-right text-sm whitespace-nowrap text-gray-500">
                                    {{ $order->updated_at->format('M j, H:i') }}
                                </div>
                            </div>
                        </div>
                    </div>
                </li>
                @endif
            </ul>
        </div>
    </div>
</div>

<script>
function copyToClipboard(elementId) {
    const element = document.querySelector(`[data-config="${elementId}"]`);
    if (element) {
        navigator.clipboard.writeText(element.textContent);
        // Show success notification
        alert('Configuration copied to clipboard!');
    }
}

function showQRCode(itemId) {
    // This would show a modal with QR code
    alert('QR Code functionality would be implemented here');
}
</script>
