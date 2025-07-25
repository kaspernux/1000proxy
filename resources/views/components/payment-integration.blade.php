{{-- Payment Gateway Integration Component --}}
<div x-data="multiPaymentProcessor" x-init="init()" class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Payment Gateway Management</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Manage multiple payment gateways and monitor transactions</p>
        </div>
        <div class="flex items-center space-x-3 mt-4 sm:mt-0">
            <div class="flex items-center space-x-2">
                <div class="w-3 h-3 rounded-full" :class="connectionStatus === 'connected' ? 'bg-green-500' : 'bg-red-500'"></div>
                <span class="text-sm text-gray-600 dark:text-gray-400" x-text="connectionStatus === 'connected' ? 'Live Updates' : 'Disconnected'"></span>
            </div>
            <button
                @click="refreshGateways()"
                class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200"
            >
                🔄 Refresh
            </button>
        </div>
    </div>

    {{-- Error Alert --}}
    <div x-show="error" class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded-lg p-4 mb-6">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-red-800 dark:text-red-200">Error</h3>
                <p class="text-sm text-red-700 dark:text-red-300 mt-1" x-text="error"></p>
                <button @click="error = null" class="text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-200 text-sm font-medium mt-2">Dismiss</button>
            </div>
        </div>
    </div>

    {{-- Success Alert --}}
    <div x-show="success" class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded-lg p-4 mb-6">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-green-800 dark:text-green-200">Success</h3>
                <p class="text-sm text-green-700 dark:text-green-300 mt-1" x-text="success"></p>
                <button @click="success = null" class="text-green-600 dark:text-green-400 hover:text-green-800 dark:hover:text-green-200 text-sm font-medium mt-2">Dismiss</button>
            </div>
        </div>
    </div>

    {{-- Gateway Overview Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg p-4 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-100 text-sm">Active Gateways</p>
                    <p class="text-2xl font-bold" x-text="stats.activeGateways"></p>
                </div>
                <div class="p-3 bg-blue-400 bg-opacity-30 rounded-full">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-lg p-4 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-green-100 text-sm">Success Rate</p>
                    <p class="text-2xl font-bold" x-text="stats.successRate + '%'"></p>
                </div>
                <div class="p-3 bg-green-400 bg-opacity-30 rounded-full">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-r from-yellow-500 to-yellow-600 rounded-lg p-4 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-yellow-100 text-sm">Pending Payments</p>
                    <p class="text-2xl font-bold" x-text="stats.pendingPayments"></p>
                </div>
                <div class="p-3 bg-yellow-400 bg-opacity-30 rounded-full">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-r from-purple-500 to-purple-600 rounded-lg p-4 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-purple-100 text-sm">Today's Volume</p>
                    <p class="text-2xl font-bold" x-text="'$' + stats.todayVolume.toLocaleString()"></p>
                </div>
                <div class="p-3 bg-purple-400 bg-opacity-30 rounded-full">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    {{-- Gateway Status List --}}
    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Gateway Status</h3>

        <div class="space-y-4">
            <template x-for="gateway in gateways" :key="gateway.id">
                <div class="flex items-center justify-between p-4 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg">
                    <div class="flex items-center space-x-4">
                        <div class="flex-shrink-0">
                            <div class="w-10 h-10 rounded-lg flex items-center justify-center" :class="gateway.status === 'active' ? 'bg-green-100 dark:bg-green-900' : 'bg-red-100 dark:bg-red-900'">
                                <span class="text-lg" x-text="gateway.icon"></span>
                            </div>
                        </div>
                        <div>
                            <h4 class="text-sm font-medium text-gray-900 dark:text-white" x-text="gateway.name"></h4>
                            <p class="text-sm text-gray-500 dark:text-gray-400" x-text="gateway.provider"></p>
                        </div>
                    </div>

                    <div class="flex items-center space-x-4">
                        <div class="text-right">
                            <p class="text-sm font-medium text-gray-900 dark:text-white" x-text="gateway.successRate + '% success'"></p>
                            <p class="text-sm text-gray-500 dark:text-gray-400" x-text="gateway.lastTransaction"></p>
                        </div>

                        <div class="flex items-center space-x-2">
                            <span
                                :class="gateway.status === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'"
                                class="px-2 py-1 text-xs font-medium rounded-full"
                                x-text="gateway.status.charAt(0).toUpperCase() + gateway.status.slice(1)"
                            ></span>

                            <button
                                @click="toggleGatewayStatus(gateway.id)"
                                :class="gateway.status === 'active' ? 'bg-red-500 hover:bg-red-600' : 'bg-green-500 hover:bg-green-600'"
                                class="text-white px-3 py-1 rounded text-xs font-medium transition-colors duration-200"
                                x-text="gateway.status === 'active' ? 'Disable' : 'Enable'"
                            ></button>

                            <button
                                @click="testGateway(gateway.id)"
                                class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-xs font-medium transition-colors duration-200"
                            >
                                Test
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- Add New Gateway --}}
    <div x-show="showAddGateway" class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Add New Gateway</h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Gateway Type</label>
                <select
                    x-model="newGateway.type"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-600 dark:text-white text-sm"
                >
                    <option value="">Select Gateway Type</option>
                    <option value="stripe">Stripe</option>
                    <option value="paypal">PayPal</option>
                    <option value="coinpayments">CoinPayments</option>
                    <option value="nowpayments">NOWPayments</option>
                    <option value="blockchain">Blockchain.info</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Gateway Name</label>
                <input
                    x-model="newGateway.name"
                    type="text"
                    placeholder="Enter gateway name"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-600 dark:text-white text-sm"
                >
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">API Key</label>
                <input
                    x-model="newGateway.apiKey"
                    type="password"
                    placeholder="Enter API key"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-600 dark:text-white text-sm"
                >
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Secret Key</label>
                <input
                    x-model="newGateway.secretKey"
                    type="password"
                    placeholder="Enter secret key"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-600 dark:text-white text-sm"
                >
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Webhook URL</label>
                <input
                    x-model="newGateway.webhookUrl"
                    type="url"
                    placeholder="https://yoursite.com/webhook"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-600 dark:text-white text-sm"
                >
            </div>
        </div>

        <div class="flex justify-between items-center mt-6">
            <button
                @click="showAddGateway = false; resetGatewayForm();"
                class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200"
            >
                Cancel
            </button>
            <button
                @click="addGateway()"
                :disabled="!newGateway.type || !newGateway.name"
                class="bg-green-500 hover:bg-green-600 disabled:bg-green-300 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200"
            >
                Add Gateway
            </button>
        </div>
    </div>

    {{-- Actions --}}
    <div class="flex justify-between items-center">
        <button
            @click="showAddGateway = !showAddGateway"
            class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200"
        >
            ➕ Add Gateway
        </button>

        <div class="flex space-x-2">
            <button
                @click="exportGatewayConfig()"
                class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200"
            >
                📥 Export Config
            </button>
            <button
                @click="testAllGateways()"
                class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200"
            >
                🧪 Test All
            </button>
        </div>
    </div>

    {{-- Loading State --}}
    <div x-show="isLoading" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-xl">
            <div class="flex items-center space-x-3">
                <svg class="animate-spin h-5 w-5 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-gray-900 dark:text-white font-medium">Processing...</span>
            </div>
        </div>
    </div>
</div>
