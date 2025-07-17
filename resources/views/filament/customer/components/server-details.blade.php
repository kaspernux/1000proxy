<div class="space-y-6">
    {{-- Server Header --}}
    <div class="flex items-start justify-between">
        <div class="flex items-center space-x-4">
            <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-purple-600 rounded-lg flex items-center justify-center text-white text-2xl font-bold">
                {{ substr($server->name, 0, 2) }}
            </div>
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $server->name }}</h2>
                <p class="text-gray-600 dark:text-gray-400">{{ $server->description }}</p>
                <div class="flex items-center space-x-3 mt-2">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        {{ ucfirst($server->status) }}
                    </span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                        📍 {{ $server->location }}
                    </span>
                    @if($server->serverCategory)
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                        {{ $server->serverCategory->name }}
                    </span>
                    @endif
                </div>
            </div>
        </div>
        <div class="text-right">
            <div class="text-3xl font-bold text-gray-900 dark:text-white">${{ $server->price }}</div>
            <div class="text-gray-500 dark:text-gray-400">per month</div>
        </div>
    </div>

    {{-- Key Metrics --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-green-600">{{ $server->uptime ?? 99 }}%</div>
            <div class="text-sm text-gray-600 dark:text-gray-400">Uptime</div>
        </div>
        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-blue-600">{{ $server->activeClients()->count() ?? 0 }}</div>
            <div class="text-sm text-gray-600 dark:text-gray-400">Active Users</div>
        </div>
        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-purple-600">{{ $server->bandwidth ?? '1Gbps' }}</div>
            <div class="text-sm text-gray-600 dark:text-gray-400">Bandwidth</div>
        </div>
        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-orange-600">{{ $server->ping ?? '<50' }}ms</div>
            <div class="text-sm text-gray-600 dark:text-gray-400">Avg Ping</div>
        </div>
    </div>

    {{-- Server Features --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2"><x-custom-icon name="bolt" class="w-5 h-5" /> Features</h3>
            <ul class="space-y-2">
                <li class="flex items-center">
                    <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                    </svg>
                    <span class="text-gray-700 dark:text-gray-300">High-speed connection (1Gbps+)</span>
                </li>
                <li class="flex items-center">
                    <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                    </svg>
                    <span class="text-gray-700 dark:text-gray-300">Unlimited bandwidth</span>
                </li>
                <li class="flex items-center">
                    <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                    </svg>
                    <span class="text-gray-700 dark:text-gray-300">24/7 monitoring</span>
                </li>
                <li class="flex items-center">
                    <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                    </svg>
                    <span class="text-gray-700 dark:text-gray-300">DDoS protection</span>
                </li>
                <li class="flex items-center">
                    <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                    </svg>
                    <span class="text-gray-700 dark:text-gray-300">Zero logs policy</span>
                </li>
            </ul>
        </div>

        <div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2"><x-custom-icon name="cog-6-tooth" class="w-5 h-5" /> Protocols</h3>
            <div class="grid grid-cols-2 gap-2">
                <div class="bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200 px-3 py-2 rounded-lg text-center text-sm font-medium">
                    VLESS
                </div>
                <div class="bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 px-3 py-2 rounded-lg text-center text-sm font-medium">
                    VMess
                </div>
                <div class="bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-200 px-3 py-2 rounded-lg text-center text-sm font-medium">
                    Trojan
                </div>
                <div class="bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200 px-3 py-2 rounded-lg text-center text-sm font-medium">
                    Shadowsocks
                </div>
            </div>
        </div>
    </div>

    {{-- Performance Chart Placeholder --}}
    <div>
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2"><x-custom-icon name="chart-bar" class="w-5 h-5" /> Performance History</h3>
        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6 text-center">
            <div class="w-full h-32 bg-gradient-to-r from-blue-400 to-purple-500 rounded-lg flex items-center justify-center text-white">
                <div>
                    <div class="text-2xl mb-2">📈</div>
                    <div class="text-sm">Performance metrics coming soon</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Technical Specifications --}}
    <div>
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2"><x-custom-icon name="cog-6-tooth" class="w-5 h-5" /> Technical Specifications</h3>
        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
            <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <dt class="text-sm font-medium text-gray-600 dark:text-gray-400">Server Location</dt>
                    <dd class="text-sm text-gray-900 dark:text-white">{{ $server->location }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-600 dark:text-gray-400">IP Address</dt>
                    <dd class="text-sm text-gray-900 dark:text-white font-mono">{{ $server->ip ?? '***.***.***.**' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-600 dark:text-gray-400">Port Range</dt>
                    <dd class="text-sm text-gray-900 dark:text-white">{{ $server->port_range ?? '1000-9999' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-600 dark:text-gray-400">Encryption</dt>
                    <dd class="text-sm text-gray-900 dark:text-white">AES-256-GCM</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-600 dark:text-gray-400">Data Limit</dt>
                    <dd class="text-sm text-gray-900 dark:text-white">{{ $server->data_limit ? $server->data_limit . ' GB' : 'Unlimited' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-600 dark:text-gray-400">Concurrent Connections</dt>
                    <dd class="text-sm text-gray-900 dark:text-white">{{ $server->max_clients ?? 'Unlimited' }}</dd>
                </div>
            </dl>
        </div>
    </div>

    {{-- Customer Reviews Section --}}
    <div>
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2"><x-custom-icon name="star" class="w-5 h-5" /> Customer Reviews</h3>
        <div class="space-y-3">
            @forelse($server->reviews()->latest()->limit(3)->get() as $review)
            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center space-x-2">
                        <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white text-sm font-bold">
                            {{ substr($review->customer->name ?? 'A', 0, 1) }}
                        </div>
                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $review->customer->name ?? 'Anonymous' }}</span>
                    </div>
                    <div class="flex items-center">
                        @for($i = 1; $i <= 5; $i++)
                            <svg class="w-4 h-4 {{ $i <= ($review->rating ?? 5) ? 'text-yellow-400' : 'text-gray-300' }}" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                            </svg>
                        @endfor
                    </div>
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-400">{{ $review->comment ?? 'Great server performance and reliability!' }}</p>
                <div class="text-xs text-gray-500 dark:text-gray-500 mt-2">{{ $review->created_at->diffForHumans() ?? '2 days ago' }}</div>
            </div>
            @empty
            <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 text-center">
                <div class="text-gray-500 dark:text-gray-400">No reviews yet. Be the first to review this server!</div>
            </div>
            @endforelse
        </div>
    </div>

    {{-- Action Buttons --}}
    <div class="flex space-x-3 pt-4 border-t border-gray-200 dark:border-gray-600">
        <button class="flex-1 bg-blue-600 text-white py-3 px-4 rounded-lg hover:bg-blue-700 transition-colors font-medium">
            <x-custom-icon name="shopping-cart" class="w-4 h-4 mr-2" /> Purchase Server Access - ${{ $server->price }}/month
        </button>
        <button class="px-4 py-3 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
            <x-custom-icon name="heart" class="w-4 h-4 mr-2" /> Add to Favorites
        </button>
        <button class="px-4 py-3 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
            <x-custom-icon name="chart-bar" class="w-4 h-4 mr-2" /> Compare
        </button>
    </div>
</div>
