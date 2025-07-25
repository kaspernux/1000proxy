{{-- Telegram Notification Center Component --}}
<div x-data="telegramNotificationCenter" x-init="init()" class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Notification Center</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Send and manage Telegram notifications to your users</p>
        </div>
        <div class="flex items-center space-x-3 mt-4 sm:mt-0">
            <button
                @click="previewNotification()"
                :disabled="!newNotification.message"
                class="bg-blue-500 hover:bg-blue-600 disabled:bg-blue-300 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200"
            >
                👁️ Preview
            </button>
            <button
                @click="sendNotification()"
                :disabled="!newNotification.title || !newNotification.message"
                class="bg-green-500 hover:bg-green-600 disabled:bg-green-300 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200"
            >
                📤 Send Notification
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

    {{-- New Notification Form --}}
    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Create New Notification</h3>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Form Fields --}}
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Title</label>
                    <input
                        x-model="newNotification.title"
                        type="text"
                        placeholder="Notification title"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-600 dark:text-white text-sm"
                    >
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Recipients</label>
                    <select
                        x-model="newNotification.recipients"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-600 dark:text-white text-sm"
                    >
                        <option value="all">All Users</option>
                        <option value="active">Active Users Only</option>
                        <option value="inactive">Inactive Users</option>
                        <option value="verified">Verified Users</option>
                        <option value="premium">Premium Users</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Template</label>
                    <select
                        x-model="newNotification.template_id"
                        @change="loadTemplate(newNotification.template_id)"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-600 dark:text-white text-sm"
                    >
                        <option value="">No Template</option>
                        <template x-for="template in templates" :key="template.id">
                            <option :value="template.id" x-text="template.name"></option>
                        </template>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Priority</label>
                        <select
                            x-model="newNotification.priority"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-600 dark:text-white text-sm"
                        >
                            <option value="low">Low</option>
                            <option value="normal">Normal</option>
                            <option value="high">High</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Schedule</label>
                        <input
                            x-model="newNotification.schedule_at"
                            type="datetime-local"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-600 dark:text-white text-sm"
                        >
                    </div>
                </div>

                <div>
                    <label class="flex items-center">
                        <input
                            x-model="newNotification.include_unsubscribed"
                            type="checkbox"
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                        >
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Include unsubscribed users</span>
                    </label>
                </div>
            </div>

            {{-- Message Content --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Message</label>
                <textarea
                    x-model="newNotification.message"
                    rows="8"
                    placeholder="Enter your notification message here..."
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-600 dark:text-white text-sm resize-none"
                ></textarea>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                    Supports HTML formatting. Use variables: {name}, {email}, {balance}
                </p>
            </div>
        </div>

        <div class="flex justify-between items-center mt-6">
            <button
                @click="resetNotificationForm()"
                class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200"
            >
                🔄 Reset Form
            </button>
        </div>
    </div>

    {{-- Preview Modal --}}
    <div x-show="previewMode" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" x-cloak>
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white dark:bg-gray-800">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white text-center mb-4">Notification Preview</h3>

                <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg mb-4">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-gray-600 dark:text-gray-400">1000Proxy Bot</span>
                        <span class="text-xs text-gray-500 dark:text-gray-500">now</span>
                    </div>

                    <div x-show="previewData">
                        <h4 class="font-semibold text-gray-900 dark:text-white mb-2" x-text="previewData?.title"></h4>
                        <div class="text-sm text-gray-700 dark:text-gray-300" x-html="previewData?.formatted_message"></div>
                    </div>
                </div>

                <div class="flex justify-center space-x-3">
                    <button
                        @click="closePreview()"
                        class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200"
                    >
                        Close
                    </button>
                    <button
                        @click="closePreview(); sendNotification();"
                        class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200"
                    >
                        Send Now
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Status</label>
            <select
                x-model="statusFilter"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white text-sm"
            >
                <option value="all">All Statuses</option>
                <option value="sent">Sent</option>
                <option value="pending">Pending</option>
                <option value="failed">Failed</option>
                <option value="scheduled">Scheduled</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Priority</label>
            <select
                x-model="priorityFilter"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white text-sm"
            >
                <option value="all">All Priorities</option>
                <option value="high">High</option>
                <option value="normal">Normal</option>
                <option value="low">Low</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Date Range</label>
            <select
                x-model="dateRange"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-700 dark:text-white text-sm"
            >
                <option value="all">All Time</option>
                <option value="1d">Last 24 Hours</option>
                <option value="7d">Last 7 Days</option>
                <option value="30d">Last 30 Days</option>
            </select>
        </div>
        <div class="flex items-end">
            <button
                @click="loadNotifications()"
                class="w-full bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200"
            >
                🔄 Refresh
            </button>
        </div>
    </div>

    {{-- Notifications List --}}
    <div class="space-y-4">
        <template x-for="notification in filteredNotifications" :key="notification.id">
            <div class="bg-gray-50 dark:bg-gray-700 border border-gray-200 dark:border-gray-600 rounded-lg p-4">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center space-x-3 mb-2">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white" x-text="notification.title"></h3>
                            <span
                                :class="getStatusColor(notification.status)"
                                class="px-2 py-1 text-xs font-medium rounded-full"
                                x-text="notification.status.charAt(0).toUpperCase() + notification.status.slice(1)"
                            ></span>
                            <span
                                :class="getPriorityColor(notification.priority)"
                                class="text-xs font-medium"
                                x-text="notification.priority.toUpperCase()"
                            ></span>
                        </div>

                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-3 line-clamp-2" x-text="notification.message"></p>

                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                            <div>
                                <span class="text-gray-500 dark:text-gray-400">Recipients:</span>
                                <span class="font-medium text-gray-900 dark:text-white ml-1" x-text="notification.recipients"></span>
                            </div>
                            <div>
                                <span class="text-gray-500 dark:text-gray-400">Sent:</span>
                                <span class="font-medium text-gray-900 dark:text-white ml-1" x-text="notification.sent_count || 0"></span>
                            </div>
                            <div>
                                <span class="text-gray-500 dark:text-gray-400">Failed:</span>
                                <span class="font-medium text-gray-900 dark:text-white ml-1" x-text="notification.failed_count || 0"></span>
                            </div>
                            <div>
                                <span class="text-gray-500 dark:text-gray-400">Created:</span>
                                <span class="font-medium text-gray-900 dark:text-white ml-1" x-text="formatDate(notification.created_at)"></span>
                            </div>
                        </div>

                        <div x-show="notification.scheduled_at" class="mt-2 text-sm">
                            <span class="text-gray-500 dark:text-gray-400">Scheduled for:</span>
                            <span class="font-medium text-blue-600 dark:text-blue-400 ml-1" x-text="formatDate(notification.scheduled_at)"></span>
                        </div>

                        <div x-show="notification.error_message" class="mt-2 text-sm text-red-600 dark:text-red-400">
                            <span class="font-medium">Error:</span>
                            <span x-text="notification.error_message"></span>
                        </div>
                    </div>

                    <div class="flex flex-col space-y-2 ml-4">
                        <button
                            @click="alert('Notification details: ' + JSON.stringify(notification, null, 2))"
                            class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 text-sm font-medium"
                        >
                            👁️ View
                        </button>
                        <button
                            @click="deleteNotification(notification.id)"
                            :disabled="notification.status === 'pending'"
                            class="text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 disabled:text-gray-400 text-sm font-medium"
                        >
                            🗑️ Delete
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </div>

    {{-- Loading State --}}
    <div x-show="isLoading" class="text-center py-8">
        <div class="inline-flex items-center px-4 py-2 font-semibold leading-6 text-sm shadow rounded-md text-blue-500 bg-blue-100 dark:bg-blue-900 dark:text-blue-200 transition ease-in-out duration-150">
            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Loading notifications...
        </div>
    </div>

    {{-- Empty State --}}
    <div x-show="!isLoading && filteredNotifications.length === 0" class="text-center py-12">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-1l-4 4z"></path>
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No notifications</h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Create your first notification to get started.</p>
    </div>
</div>

{{-- Bot Command Builder Component --}}
<div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 mt-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Bot Command Builder</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Design custom commands and inline keyboards</p>
        </div>
    </div>

    {{-- Command Builder Interface --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Command Configuration --}}
        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Command Configuration</h3>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Command</label>
                    <input type="text" placeholder="/newcommand" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-600 dark:text-white text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Description</label>
                    <input type="text" placeholder="Command description" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-600 dark:text-white text-sm">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Response Message</label>
                    <textarea rows="4" placeholder="Enter the response message..." class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-600 dark:text-white text-sm resize-none"></textarea>
                </div>
            </div>
        </div>

        {{-- Inline Keyboard Designer --}}
        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Inline Keyboard Designer</h3>

            <div class="space-y-4">
                <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-4 min-h-32">
                    <p class="text-center text-gray-500 dark:text-gray-400 text-sm">Drop buttons here or click to add</p>
                </div>

                <div class="flex space-x-2">
                    <button class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-2 rounded text-sm font-medium">+ Add Button</button>
                    <button class="bg-green-500 hover:bg-green-600 text-white px-3 py-2 rounded text-sm font-medium">+ Add Row</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Preview & Actions --}}
    <div class="mt-6 flex justify-between items-center">
        <button class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-medium">👁️ Preview Command</button>
        <div class="space-x-2">
            <button class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md text-sm font-medium">💾 Save Draft</button>
            <button class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-md text-sm font-medium">🚀 Deploy Command</button>
        </div>
    </div>
</div>
