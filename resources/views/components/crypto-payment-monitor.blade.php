{{-- Crypto Payment Monitor Component --}}
<div x-data="cryptoPaymentMonitor" x-init="init()" class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Cryptocurrency Monitor</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Real-time crypto payment tracking and rate monitoring</p>
        </div>
        <div class="flex items-center space-x-3 mt-4 sm:mt-0">
            <div class="flex items-center space-x-2">
                <div class="w-3 h-3 rounded-full" :class="websocketStatus === 'connected' ? 'bg-green-500' : 'bg-red-500'"></div>
                <span class="text-sm text-gray-600 dark:text-gray-400" x-text="websocketStatus === 'connected' ? 'Live Rates' : 'Disconnected'"></span>
            </div>
            <button
                @click="refreshRates()"
                class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200"
            >
                🔄 Refresh Rates
            </button>
        </div>
    </div>

    {{-- Quick Stats --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-gradient-to-r from-orange-500 to-orange-600 rounded-lg p-4 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-orange-100 text-sm">Bitcoin (BTC)</p>
                    <p class="text-xl font-bold" x-text="'$' + rates.BTC?.toLocaleString()"></p>
                </div>
                <div class="text-right">
                    <span class="text-xs" x-text="changes.BTC?.change || '0.00'"></span>
                    <div class="flex items-center justify-end mt-1">
                        <span :class="changes.BTC?.direction === 'up' ? 'text-green-200' : 'text-red-200'" class="text-xs">
                            <span x-show="changes.BTC?.direction === 'up'">↗</span>
                            <span x-show="changes.BTC?.direction === 'down'">↘</span>
                            <span x-text="changes.BTC?.percentage || '0.00'"></span>%
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-r from-gray-600 to-gray-700 rounded-lg p-4 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-100 text-sm">Ethereum (ETH)</p>
                    <p class="text-xl font-bold" x-text="'$' + rates.ETH?.toLocaleString()"></p>
                </div>
                <div class="text-right">
                    <span class="text-xs" x-text="changes.ETH?.change || '0.00'"></span>
                    <div class="flex items-center justify-end mt-1">
                        <span :class="changes.ETH?.direction === 'up' ? 'text-green-200' : 'text-red-200'" class="text-xs">
                            <span x-show="changes.ETH?.direction === 'up'">↗</span>
                            <span x-show="changes.ETH?.direction === 'down'">↘</span>
                            <span x-text="changes.ETH?.percentage || '0.00'"></span>%
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-r from-purple-500 to-purple-600 rounded-lg p-4 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-purple-100 text-sm">Pending Payments</p>
                    <p class="text-xl font-bold" x-text="pendingPayments.length"></p>
                </div>
                <div class="text-right">
                    <p class="text-xs text-purple-100">Total Value</p>
                    <p class="text-sm font-semibold" x-text="'$' + totalPendingValue.toLocaleString()"></p>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-lg p-4 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-green-100 text-sm">Confirmed Today</p>
                    <p class="text-xl font-bold" x-text="todayConfirmed"></p>
                </div>
                <div class="text-right">
                    <p class="text-xs text-green-100">Total Volume</p>
                    <p class="text-sm font-semibold" x-text="'$' + todayVolume.toLocaleString()"></p>
                </div>
            </div>
        </div>
    </div>

    {{-- Live Rate Chart --}}
    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Live Rate Chart (24h)</h3>
        <div class="h-64 flex items-center justify-center border border-gray-200 dark:border-gray-600 rounded-lg">
            <canvas id="cryptoRateChart" class="w-full h-full"></canvas>
        </div>
    </div>

    {{-- Pending Payments --}}
    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6 mb-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Pending Payments</h3>
            <div class="flex items-center space-x-2 mt-2 sm:mt-0">
                <select
                    x-model="currencyFilter"
                    @change="filterPendingPayments()"
                    class="px-3 py-1 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-600 dark:text-white text-sm"
                >
                    <option value="all">All Currencies</option>
                    <option value="BTC">Bitcoin</option>
                    <option value="ETH">Ethereum</option>
                    <option value="XMR">Monero</option>
                    <option value="LTC">Litecoin</option>
                </select>
                <button
                    @click="checkAllPayments()"
                    class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-sm font-medium transition-colors duration-200"
                >
                    🔍 Check All
                </button>
            </div>
        </div>

        <div class="space-y-3 max-h-96 overflow-y-auto">
            <template x-for="payment in filteredPendingPayments" :key="payment.id">
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg p-4">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center space-x-3 mb-2">
                                <span class="text-lg" x-text="getCurrencyIcon(payment.currency)"></span>
                                <div>
                                    <h4 class="text-sm font-semibold text-gray-900 dark:text-white" x-text="payment.currency + ' Payment'"></h4>
                                    <p class="text-xs text-gray-500 dark:text-gray-400" x-text="'Order #' + payment.order_id"></p>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400">Amount:</span>
                                    <p class="font-medium text-gray-900 dark:text-white" x-text="payment.crypto_amount + ' ' + payment.currency"></p>
                                </div>
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400">USD Value:</span>
                                    <p class="font-medium text-gray-900 dark:text-white" x-text="'$' + payment.usd_amount.toLocaleString()"></p>
                                </div>
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400">Confirmations:</span>
                                    <p class="font-medium" :class="payment.confirmations >= payment.required_confirmations ? 'text-green-600 dark:text-green-400' : 'text-yellow-600 dark:text-yellow-400'" x-text="payment.confirmations + '/' + payment.required_confirmations"></p>
                                </div>
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400">Time Left:</span>
                                    <p class="font-medium text-gray-900 dark:text-white" x-text="getTimeRemaining(payment.expires_at)"></p>
                                </div>
                            </div>

                            <div class="mt-3 p-2 bg-gray-50 dark:bg-gray-700 rounded text-xs font-mono break-all">
                                <span class="text-gray-500 dark:text-gray-400">Address: </span>
                                <span class="text-gray-900 dark:text-white" x-text="payment.address"></span>
                                <button @click="copyToClipboard(payment.address)" class="ml-2 text-blue-500 hover:text-blue-700">📋</button>
                            </div>

                            <div x-show="payment.tx_hash" class="mt-2 p-2 bg-gray-50 dark:bg-gray-700 rounded text-xs font-mono break-all">
                                <span class="text-gray-500 dark:text-gray-400">TX Hash: </span>
                                <span class="text-gray-900 dark:text-white" x-text="payment.tx_hash"></span>
                                <button @click="openBlockExplorer(payment.currency, payment.tx_hash)" class="ml-2 text-blue-500 hover:text-blue-700">🔗</button>
                            </div>
                        </div>

                        <div class="flex flex-col space-y-2 ml-4">
                            <button
                                @click="checkPaymentStatus(payment.id)"
                                class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 text-sm font-medium"
                            >
                                🔍 Check
                            </button>
                            <button
                                @click="generateQRCode(payment.address, payment.crypto_amount)"
                                class="text-green-600 dark:text-green-400 hover:text-green-800 dark:hover:text-green-300 text-sm font-medium"
                            >
                                📱 QR Code
                            </button>
                            <button
                                @click="resendNotification(payment.id)"
                                class="text-orange-600 dark:text-orange-400 hover:text-orange-800 dark:hover:text-orange-300 text-sm font-medium"
                            >
                                📧 Resend
                            </button>
                        </div>
                    </div>

                    {{-- Progress Bar --}}
                    <div class="mt-4">
                        <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mb-1">
                            <span>Confirmation Progress</span>
                            <span x-text="Math.min(100, Math.round((payment.confirmations / payment.required_confirmations) * 100)) + '%'"></span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2">
                            <div
                                class="h-2 rounded-full transition-all duration-300"
                                :class="payment.confirmations >= payment.required_confirmations ? 'bg-green-500' : 'bg-blue-500'"
                                :style="'width: ' + Math.min(100, Math.round((payment.confirmations / payment.required_confirmations) * 100)) + '%'"
                            ></div>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        {{-- Empty State --}}
        <div x-show="filteredPendingPayments.length === 0" class="text-center py-8">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No pending payments</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">All crypto payments have been processed.</p>
        </div>
    </div>

    {{-- QR Code Modal --}}
    <div x-show="showQRModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" x-cloak>
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
            <div class="mt-3 text-center">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Payment QR Code</h3>

                <div class="bg-white p-4 rounded-lg mb-4">
                    <div id="qrcode" class="flex justify-center"></div>
                </div>

                <div class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    <p>Scan with your crypto wallet to send payment</p>
                    <p class="font-mono text-xs mt-2 break-all" x-text="qrCodeData.address"></p>
                    <p class="font-semibold" x-text="qrCodeData.amount + ' ' + qrCodeData.currency"></p>
                </div>

                <div class="flex justify-center space-x-3">
                    <button
                        @click="closeQRModal()"
                        class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200"
                    >
                        Close
                    </button>
                    <button
                        @click="downloadQRCode()"
                        class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200"
                    >
                        Download
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- WebSocket Status Indicator --}}
    <div class="fixed bottom-4 right-4 z-40">
        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg p-3 shadow-lg">
            <div class="flex items-center space-x-2">
                <div class="w-2 h-2 rounded-full" :class="websocketStatus === 'connected' ? 'bg-green-500' : 'bg-red-500'"></div>
                <span class="text-xs text-gray-600 dark:text-gray-400" x-text="websocketStatus === 'connected' ? 'Live Updates Active' : 'Connection Lost'"></span>
            </div>
        </div>
    </div>

    {{-- Loading State --}}
    <div x-show="isLoading" class="text-center py-8">
        <div class="inline-flex items-center px-4 py-2 font-semibold leading-6 text-sm shadow rounded-md text-blue-500 bg-blue-100 dark:bg-blue-900 dark:text-blue-200 transition ease-in-out duration-150">
            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Loading crypto data...
        </div>
    </div>
</div>
