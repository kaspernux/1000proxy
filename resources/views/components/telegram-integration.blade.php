{{-- Telegram Bot Control Panel Component --}}
<div x-data="telegramBotControlPanel" x-init="init()" class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Telegram Bot Control Panel</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Manage your Telegram bot configuration and test functionality</p>
        </div>
        <div class="flex items-center space-x-3 mt-4 sm:mt-0">
            <div class="flex items-center">
                <span class="text-sm text-gray-600 dark:text-gray-400 mr-2">Status:</span>
                <span
                    :class="isConnected ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'"
                    class="px-2 py-1 text-xs font-medium rounded-full"
                    x-text="isConnected ? '🟢 Connected' : '🔴 Disconnected'"
                ></span>
            </div>
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

    {{-- Bot Configuration --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        {{-- Bot Token Configuration --}}
        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Bot Configuration</h3>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Bot Token</label>
                    <div class="flex space-x-2">
                        <input
                            x-model="botToken"
                            :disabled="isConnected"
                            type="password"
                            placeholder="Enter your Telegram bot token"
                            class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-600 dark:text-white text-sm disabled:bg-gray-100 dark:disabled:bg-gray-800"
                        >
                        <button
                            @click="isConnected ? disconnectBot() : connectBot()"
                            :disabled="isLoading || (!botToken && !isConnected)"
                            class="bg-blue-500 hover:bg-blue-600 disabled:bg-blue-300 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200"
                        >
                            <span x-show="!isLoading && !isConnected">🔗 Connect</span>
                            <span x-show="!isLoading && isConnected">🔌 Disconnect</span>
                            <span x-show="isLoading">⏳ Connecting...</span>
                        </button>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Get your bot token from @BotFather on Telegram</p>
                </div>

                {{-- Bot Info --}}
                <div x-show="botInfo" class="border-t border-gray-200 dark:border-gray-600 pt-4">
                    <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-2">Bot Information</h4>
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="text-gray-600 dark:text-gray-400">Username:</span>
                            <span class="font-medium text-gray-900 dark:text-white ml-1" x-text="botInfo?.username"></span>
                        </div>
                        <div>
                            <span class="text-gray-600 dark:text-gray-400">Name:</span>
                            <span class="font-medium text-gray-900 dark:text-white ml-1" x-text="botInfo?.first_name"></span>
                        </div>
                        <div>
                            <span class="text-gray-600 dark:text-gray-400">ID:</span>
                            <span class="font-mono text-gray-900 dark:text-white ml-1" x-text="botInfo?.id"></span>
                        </div>
                        <div>
                            <span class="text-gray-600 dark:text-gray-400">Can Join Groups:</span>
                            <span class="font-medium text-gray-900 dark:text-white ml-1" x-text="botInfo?.can_join_groups ? 'Yes' : 'No'"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Webhook Configuration --}}
        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Webhook Configuration</h3>

            <div class="space-y-4">
                <div x-show="webhookInfo">
                    <div class="text-sm space-y-2">
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">URL:</span>
                            <span class="font-mono text-gray-900 dark:text-white text-xs" x-text="webhookInfo?.url || 'Not set'"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Last Error:</span>
                            <span class="text-red-600 dark:text-red-400 text-xs" x-text="webhookInfo?.last_error_message || 'None'"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Pending Updates:</span>
                            <span class="font-medium text-gray-900 dark:text-white" x-text="webhookInfo?.pending_update_count || 0"></span>
                        </div>
                    </div>
                </div>

                <div class="flex space-x-2">
                    <button
                        @click="setWebhook()"
                        :disabled="!isConnected"
                        class="flex-1 bg-green-500 hover:bg-green-600 disabled:bg-green-300 text-white px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200"
                    >
                        🔗 Set Webhook
                    </button>
                    <button
                        @click="deleteWebhook()"
                        :disabled="!isConnected || !webhookInfo?.url"
                        class="flex-1 bg-red-500 hover:bg-red-600 disabled:bg-red-300 text-white px-3 py-2 rounded-md text-sm font-medium transition-colors duration-200"
                    >
                        🗑️ Delete Webhook
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Bot Commands Management --}}
    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 mb-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Bot Commands</h3>
            <button
                @click="setBotCommands()"
                :disabled="!isConnected"
                class="bg-blue-500 hover:bg-blue-600 disabled:bg-blue-300 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200"
            >
                🔄 Update Commands
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <template x-for="command in commands" :key="command.command">
                <div class="bg-white dark:bg-gray-600 p-3 rounded border border-gray-200 dark:border-gray-500">
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <h4 class="font-mono text-sm font-semibold text-blue-600 dark:text-blue-400" x-text="`/${command.command}`"></h4>
                            <p class="text-sm text-gray-600 dark:text-gray-300 mt-1" x-text="command.description"></p>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>

    {{-- Test Message --}}
    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 mb-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Send Test Message</h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Chat ID</label>
                    <input
                        x-model="testMessage.chatId"
                        type="text"
                        placeholder="Enter chat ID or @username"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-600 dark:text-white text-sm"
                    >
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Parse Mode</label>
                    <select
                        x-model="testMessage.parseMode"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-600 dark:text-white text-sm"
                    >
                        <option value="">None</option>
                        <option value="HTML">HTML</option>
                        <option value="Markdown">Markdown</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Message</label>
                <textarea
                    x-model="testMessage.text"
                    rows="4"
                    placeholder="Enter your test message here..."
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-600 dark:text-white text-sm resize-none"
                ></textarea>
            </div>
        </div>

        <div class="mt-4">
            <button
                @click="sendTestMessage()"
                :disabled="!isConnected || !testMessage.chatId || !testMessage.text"
                class="bg-blue-500 hover:bg-blue-600 disabled:bg-blue-300 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200"
            >
                📤 Send Test Message
            </button>
        </div>
    </div>

    {{-- Recent Activity --}}
    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Recent Activity</h3>

        <div class="space-y-3 max-h-60 overflow-y-auto">
            <template x-for="activity in recentActivity" :key="activity.id">
                <div class="flex items-start space-x-3 p-3 bg-white dark:bg-gray-600 rounded border border-gray-200 dark:border-gray-500">
                    <span class="text-lg" x-text="getActivityIcon(activity.type)"></span>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 dark:text-white" x-text="activity.message"></p>
                        <p class="text-xs text-gray-500 dark:text-gray-400" x-text="formatDate(activity.timestamp)"></p>
                    </div>
                    <span :class="getActivityColor(activity.type)" class="text-xs font-medium" x-text="activity.type.toUpperCase()"></span>
                </div>
            </template>

            <div x-show="recentActivity.length === 0" class="text-center py-6">
                <p class="text-sm text-gray-500 dark:text-gray-400">No recent activity</p>
            </div>
        </div>
    </div>
</div>

{{-- User Telegram Linking Component --}}
<div x-data="userTelegramLinking" x-init="init()" class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">User Telegram Linking</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Manage user account linking with Telegram</p>
        </div>
        <div class="flex items-center space-x-3 mt-4 sm:mt-0">
            <button
                @click="generateLinkingCode()"
                :disabled="isGenerating"
                class="bg-blue-500 hover:bg-blue-600 disabled:bg-blue-300 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200"
            >
                <span x-show="!isGenerating">🔗 Generate Link Code</span>
                <span x-show="isGenerating">⏳ Generating...</span>
            </button>
        </div>
    </div>

    {{-- Statistics --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg p-4">
            <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">Total Linked</h3>
            <p class="text-2xl font-bold text-blue-900 dark:text-blue-100" x-text="stats.totalLinked"></p>
        </div>
        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded-lg p-4">
            <h3 class="text-sm font-medium text-green-800 dark:text-green-200">Active Today</h3>
            <p class="text-2xl font-bold text-green-900 dark:text-green-100" x-text="stats.activeToday"></p>
        </div>
        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-lg p-4">
            <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">Pending Links</h3>
            <p class="text-2xl font-bold text-yellow-900 dark:text-yellow-100" x-text="stats.pendingLinks"></p>
        </div>
        <div class="bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-700 rounded-lg p-4">
            <h3 class="text-sm font-medium text-purple-800 dark:text-purple-200">Avg Response Time</h3>
            <p class="text-2xl font-bold text-purple-900 dark:text-purple-100" x-text="stats.averageResponseTime + 'ms'"></p>
        </div>
    </div>

    {{-- Linking Code & QR --}}
    <div x-show="linkingCode" class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6 mb-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Linking Code</h3>
                <div class="bg-white dark:bg-gray-600 p-4 rounded border border-gray-200 dark:border-gray-500">
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Share this code with users:</p>
                    <div class="flex items-center space-x-2">
                        <code class="bg-gray-100 dark:bg-gray-800 px-3 py-2 rounded font-mono text-lg font-bold text-blue-600 dark:text-blue-400 flex-1" x-text="linkingCode"></code>
                        <button
                            @click="navigator.clipboard.writeText(linkingCode)"
                            class="bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 px-3 py-2 rounded text-sm"
                        >
                            📋 Copy
                        </button>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">Users can send this code to your bot to link their accounts</p>
                </div>
            </div>

            <div x-show="qrCodeUrl">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">QR Code</h3>
                <div class="bg-white dark:bg-gray-600 p-4 rounded border border-gray-200 dark:border-gray-500 text-center">
                    <img :src="qrCodeUrl" alt="Linking QR Code" class="mx-auto max-w-full h-auto">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">Scan with Telegram to link account</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Search Users</label>
            <input
                x-model="searchQuery"
                type="text"
                placeholder="Search by name, username, or email..."
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white text-sm"
            >
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Status Filter</label>
            <select
                x-model="statusFilter"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white text-sm"
            >
                <option value="all">All Users</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
                <option value="verified">Verified</option>
                <option value="unverified">Unverified</option>
            </select>
        </div>
        <div class="flex items-end">
            <button
                @click="loadLinkedUsers()"
                class="w-full bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200"
            >
                🔄 Refresh
            </button>
        </div>
    </div>

    {{-- Linked Users Table --}}
    <div class="overflow-hidden shadow ring-1 ring-black ring-opacity-5 md:rounded-lg">
        <table class="min-w-full divide-y divide-gray-300 dark:divide-gray-600">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">User</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Telegram</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Linked Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                <template x-for="user in filteredUsers" :key="user.id">
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div>
                                    <div class="text-sm font-medium text-gray-900 dark:text-white" x-text="user.name || user.email"></div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400" x-text="user.email"></div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900 dark:text-white">
                                <div x-show="user.telegram_username" x-text="'@' + user.telegram_username"></div>
                                <div class="text-xs text-gray-500 dark:text-gray-400" x-text="user.telegram_first_name + (user.telegram_last_name ? ' ' + user.telegram_last_name : '')"></div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span
                                :class="getUserStatus(user) === 'Online' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' :
                                        getUserStatus(user).includes('ago') ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' :
                                        'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200'"
                                class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                x-text="getUserStatus(user)"
                            ></span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400" x-text="formatDate(user.telegram_linked_at)"></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                            <button
                                @click="sendTestMessage(user.telegram_chat_id)"
                                class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300"
                            >
                                📤 Test
                            </button>
                            <button
                                @click="unlinkUser(user.id)"
                                class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300"
                            >
                                🔗 Unlink
                            </button>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>

    {{-- Loading State --}}
    <div x-show="isLoading" class="text-center py-8">
        <div class="inline-flex items-center px-4 py-2 font-semibold leading-6 text-sm shadow rounded-md text-blue-500 bg-blue-100 dark:bg-blue-900 dark:text-blue-200 transition ease-in-out duration-150">
            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Loading linked users...
        </div>
    </div>

    {{-- Empty State --}}
    <div x-show="!isLoading && filteredUsers.length === 0" class="text-center py-12">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No linked users</h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">No users have linked their Telegram accounts yet.</p>
    </div>
</div>
