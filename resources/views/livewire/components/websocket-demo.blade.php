{{-- WebSocket Integration Demo Component --}}
<div x-data="webSocketDemo" x-init="init()" class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">WebSocket Real-time Integration</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Live communication, notifications, and collaborative features</p>
        </div>
        <div class="flex items-center space-x-3 mt-4 sm:mt-0">
            <div class="flex items-center space-x-2">
                <div 
                    class="w-3 h-3 rounded-full"
                    :class="connectionStatus === 'connected' ? 'bg-green-500' : connectionStatus === 'connecting' ? 'bg-yellow-500' : 'bg-red-500'"
                ></div>
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300" x-text="connectionStatus"></span>
            </div>
            <button
                @click="toggleConnection()"
                class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200"
                x-text="connectionStatus === 'connected' ? '🔌 Disconnect' : '🔗 Connect'"
            ></button>
        </div>
    </div>

    {{-- Connection Statistics --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg p-4">
            <div class="flex items-center">
                <div class="p-2 bg-blue-500 rounded-lg">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Latency</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">
                        <span x-text="latency"></span>ms
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded-lg p-4">
            <div class="flex items-center">
                <div class="p-2 bg-green-500 rounded-lg">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Messages Sent</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white" x-text="messagesSent"></p>
                </div>
            </div>
        </div>

        <div class="bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-700 rounded-lg p-4">
            <div class="flex items-center">
                <div class="p-2 bg-purple-500 rounded-lg">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM4 19h1v-1a3 3 0 013-3h3a3 3 0 013 3v1h1m-9-10a3 3 0 110-6 3 3 0 010 6z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Active Users</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white" x-text="Object.keys(userPresence).length"></p>
                </div>
            </div>
        </div>

        <div class="bg-orange-50 dark:bg-orange-900/20 border border-orange-200 dark:border-orange-700 rounded-lg p-4">
            <div class="flex items-center">
                <div class="p-2 bg-orange-500 rounded-lg">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM4 19h1v-1a3 3 0 013-3h3a3 3 0 013 3v1h1m-9-10a3 3 0 110-6 3 3 0 010 6z"></path>
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Notifications</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white" x-text="notifications.length"></p>
                </div>
            </div>
        </div>
    </div>

    {{-- Real-time Features Tabs --}}
    <div class="mb-6">
        <div class="border-b border-gray-200 dark:border-gray-700">
            <nav class="-mb-px flex space-x-8">
                <button
                    @click="activeTab = 'notifications'"
                    class="py-2 px-1 border-b-2 font-medium text-sm transition-colors duration-200"
                    :class="activeTab === 'notifications' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'"
                >
                    🔔 Notifications
                </button>
                <button
                    @click="activeTab = 'chat'"
                    class="py-2 px-1 border-b-2 font-medium text-sm transition-colors duration-200"
                    :class="activeTab === 'chat' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'"
                >
                    💬 Live Chat
                </button>
                <button
                    @click="activeTab = 'servers'"
                    class="py-2 px-1 border-b-2 font-medium text-sm transition-colors duration-200"
                    :class="activeTab === 'servers' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'"
                >
                    🖥️ Server Status
                </button>
                <button
                    @click="activeTab = 'presence'"
                    class="py-2 px-1 border-b-2 font-medium text-sm transition-colors duration-200"
                    :class="activeTab === 'presence' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'"
                >
                    👥 User Presence
                </button>
            </nav>
        </div>
    </div>

    {{-- Notifications Tab --}}
    <div x-show="activeTab === 'notifications'" class="space-y-4">
        <div class="flex justify-between items-center">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Real-time Notifications</h3>
            <div class="flex space-x-2">
                <button
                    @click="sendTestNotification()"
                    class="bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded text-sm font-medium transition-colors duration-200"
                >
                    📢 Send Test
                </button>
                <button
                    @click="clearNotifications()"
                    class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-sm font-medium transition-colors duration-200"
                >
                    🗑️ Clear
                </button>
            </div>
        </div>
        
        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 max-h-96 overflow-y-auto">
            <template x-for="notification in notifications.slice(0, 10)" :key="notification.id">
                <div class="flex items-start space-x-3 p-3 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded mb-2 last:mb-0">
                    <div class="flex-shrink-0">
                        <div 
                            class="w-8 h-8 rounded-full flex items-center justify-center text-white text-sm"
                            :class="notification.type === 'success' ? 'bg-green-500' : notification.type === 'warning' ? 'bg-yellow-500' : notification.type === 'error' ? 'bg-red-500' : 'bg-blue-500'"
                        >
                            <span x-text="notification.type === 'success' ? '✓' : notification.type === 'warning' ? '⚠' : notification.type === 'error' ? '✗' : 'ℹ'"></span>
                        </div>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 dark:text-white" x-text="notification.title"></p>
                        <p class="text-sm text-gray-600 dark:text-gray-400" x-text="notification.message"></p>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-1" x-text="formatTime(notification.timestamp)"></p>
                    </div>
                </div>
            </template>
            <div x-show="notifications.length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400">
                No notifications yet. Send a test notification to see real-time updates!
            </div>
        </div>
    </div>

    {{-- Chat Tab --}}
    <div x-show="activeTab === 'chat'" class="space-y-4">
        <div class="flex justify-between items-center">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Live Chat Support</h3>
            <div class="flex items-center space-x-2">
                <span class="text-sm text-gray-500 dark:text-gray-400">Channel:</span>
                <select x-model="currentChannel" class="text-sm border border-gray-300 dark:border-gray-600 rounded px-2 py-1 dark:bg-gray-700 dark:text-white">
                    <option value="general">General</option>
                    <option value="support">Support</option>
                    <option value="billing">Billing</option>
                </select>
            </div>
        </div>
        
        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
            {{-- Chat Messages --}}
            <div class="h-64 overflow-y-auto mb-4 space-y-2">
                <template x-for="message in chatMessages.filter(m => m.channel === currentChannel)" :key="message.id">
                    <div class="flex items-start space-x-3">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white text-sm font-medium">
                                <span x-text="message.username ? message.username.charAt(0).toUpperCase() : 'U'"></span>
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center space-x-2">
                                <p class="text-sm font-medium text-gray-900 dark:text-white" x-text="message.username || 'Anonymous'"></p>
                                <p class="text-xs text-gray-500 dark:text-gray-400" x-text="formatTime(message.timestamp)"></p>
                            </div>
                            <p class="text-sm text-gray-700 dark:text-gray-300" x-text="message.message"></p>
                        </div>
                    </div>
                </template>
                <div x-show="chatMessages.filter(m => m.channel === currentChannel).length === 0" class="text-center py-8 text-gray-500 dark:text-gray-400">
                    No messages in this channel yet. Start the conversation!
                </div>
            </div>
            
            {{-- Chat Input --}}
            <div class="flex space-x-2">
                <input
                    x-model="newMessage"
                    @keydown.enter="sendChatMessage()"
                    type="text"
                    placeholder="Type your message..."
                    class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded text-sm dark:bg-gray-800 dark:text-white"
                >
                <button
                    @click="sendChatMessage()"
                    :disabled="!newMessage.trim()"
                    class="bg-blue-500 hover:bg-blue-600 disabled:bg-blue-300 text-white px-4 py-2 rounded text-sm font-medium transition-colors duration-200"
                >
                    Send
                </button>
            </div>
        </div>
    </div>

    {{-- Server Status Tab --}}
    <div x-show="activeTab === 'servers'" class="space-y-4">
        <div class="flex justify-between items-center">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Live Server Status</h3>
            <button
                @click="refreshServerStatus()"
                class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-sm font-medium transition-colors duration-200"
            >
                🔄 Refresh
            </button>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <template x-for="(server, serverId) in serverStatuses" :key="serverId">
                <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-lg p-4">
                    <div class="flex items-center justify-between mb-3">
                        <h4 class="text-md font-medium text-gray-900 dark:text-white" x-text="server.name || `Server ${serverId}`"></h4>
                        <div 
                            class="w-3 h-3 rounded-full"
                            :class="server.status === 'online' ? 'bg-green-500' : server.status === 'degraded' ? 'bg-yellow-500' : 'bg-red-500'"
                        ></div>
                    </div>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Status:</span>
                            <span class="font-medium" :class="server.status === 'online' ? 'text-green-600' : server.status === 'degraded' ? 'text-yellow-600' : 'text-red-600'" x-text="server.status"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Load:</span>
                            <span class="font-medium text-gray-900 dark:text-white" x-text="server.load || '0%'"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Connections:</span>
                            <span class="font-medium text-gray-900 dark:text-white" x-text="server.connections || 0"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600 dark:text-gray-400">Updated:</span>
                            <span class="font-medium text-gray-900 dark:text-white" x-text="formatTime(server.lastUpdate)"></span>
                        </div>
                    </div>
                </div>
            </template>
            
            {{-- Demo servers if none exist --}}
            <div x-show="Object.keys(serverStatuses).length === 0" class="col-span-full">
                <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                    <p>No server data available. Click refresh to load demo data.</p>
                </div>
            </div>
        </div>
    </div>

    {{-- User Presence Tab --}}
    <div x-show="activeTab === 'presence'" class="space-y-4">
        <div class="flex justify-between items-center">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">User Presence</h3>
            <div class="flex space-x-2">
                <select x-model="myStatus" @change="updateMyPresence()" class="text-sm border border-gray-300 dark:border-gray-600 rounded px-2 py-1 dark:bg-gray-700 dark:text-white">
                    <option value="online">🟢 Online</option>
                    <option value="away">🟡 Away</option>
                    <option value="busy">🔴 Busy</option>
                    <option value="invisible">⚫ Invisible</option>
                </select>
            </div>
        </div>
        
        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <template x-for="(presence, userId) in userPresence" :key="userId">
                    <div class="flex items-center space-x-3 p-3 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded">
                        <div class="flex-shrink-0">
                            <div class="relative">
                                <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center text-white font-medium">
                                    <span x-text="presence.username ? presence.username.charAt(0).toUpperCase() : 'U'"></span>
                                </div>
                                <div 
                                    class="absolute -bottom-1 -right-1 w-4 h-4 rounded-full border-2 border-white dark:border-gray-800"
                                    :class="presence.status === 'online' ? 'bg-green-500' : presence.status === 'away' ? 'bg-yellow-500' : presence.status === 'busy' ? 'bg-red-500' : 'bg-gray-500'"
                                ></div>
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-white" x-text="presence.username || `User ${userId}`"></p>
                            <p class="text-xs text-gray-500 dark:text-gray-400" x-text="presence.status"></p>
                            <p class="text-xs text-gray-400 dark:text-gray-500" x-text="'Last seen: ' + formatTime(presence.lastSeen)"></p>
                        </div>
                    </div>
                </template>
                
                <div x-show="Object.keys(userPresence).length === 0" class="col-span-full text-center py-8 text-gray-500 dark:text-gray-400">
                    No other users online at the moment.
                </div>
            </div>
        </div>
    </div>

    {{-- Connection Debug Info --}}
    <div class="mt-6 bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
        <details>
            <summary class="text-sm font-medium text-gray-700 dark:text-gray-300 cursor-pointer">Debug Information</summary>
            <div class="mt-2 text-xs text-gray-600 dark:text-gray-400 space-y-1">
                <div>Connection ID: <span class="font-mono" x-text="connectionId || 'Not connected'"></span></div>
                <div>Reconnect Attempts: <span x-text="reconnectAttempts"></span></div>
                <div>Queued Messages: <span x-text="queuedMessages"></span></div>
                <div>WebSocket URL: <span class="font-mono" x-text="wsUrl"></span></div>
                <div>Last Activity: <span x-text="formatTime(lastActivity)"></span></div>
            </div>
        </details>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('webSocketDemo', () => ({
        activeTab: 'notifications',
        connectionStatus: 'disconnected',
        latency: 0,
        messagesSent: 0,
        notifications: [],
        chatMessages: [],
        serverStatuses: {},
        userPresence: {},
        currentChannel: 'general',
        newMessage: '',
        myStatus: 'online',
        connectionId: null,
        reconnectAttempts: 0,
        queuedMessages: 0,
        wsUrl: '',
        lastActivity: Date.now(),

        init() {
            this.setupWebSocket();
            this.startDemoData();
        },

        setupWebSocket() {
            if (window.WebSocketManager) {
                // Setup event listeners
                window.WebSocketManager.on('connected', () => {
                    this.connectionStatus = 'connected';
                    this.updateStatus();
                });

                window.WebSocketManager.on('disconnected', () => {
                    this.connectionStatus = 'disconnected';
                    this.updateStatus();
                });

                window.WebSocketManager.on('latency_update', (data) => {
                    this.latency = data.latency;
                    this.lastActivity = Date.now();
                });

                window.WebSocketManager.on('notification', (data) => {
                    this.notifications.unshift({
                        id: Date.now(),
                        ...data,
                        timestamp: Date.now()
                    });
                    this.lastActivity = Date.now();
                });

                window.WebSocketManager.on('chat_message', (data) => {
                    this.chatMessages.push({
                        id: Date.now(),
                        ...data
                    });
                    this.lastActivity = Date.now();
                });

                window.WebSocketManager.on('server_status_update', (data) => {
                    this.serverStatuses[data.serverId] = {
                        ...data,
                        lastUpdate: Date.now()
                    };
                    this.lastActivity = Date.now();
                });

                window.WebSocketManager.on('user_presence_update', (data) => {
                    this.userPresence[data.userId] = {
                        ...data,
                        lastSeen: Date.now()
                    };
                    this.lastActivity = Date.now();
                });

                // Get initial status
                this.updateStatus();
                this.wsUrl = window.WebSocketManager.options.url;
            }
        },

        updateStatus() {
            if (window.WebSocketManager) {
                const status = window.WebSocketManager.getStatus();
                this.connectionId = status.connectionId;
                this.reconnectAttempts = status.reconnectAttempts;
                this.queuedMessages = status.queuedMessages;
                this.latency = status.latency;
            }
        },

        toggleConnection() {
            if (window.WebSocketManager) {
                if (this.connectionStatus === 'connected') {
                    window.WebSocketManager.disconnect();
                } else {
                    this.connectionStatus = 'connecting';
                    window.WebSocketManager.connect();
                }
            }
        },

        sendTestNotification() {
            if (window.WebSocketManager) {
                const types = ['info', 'success', 'warning', 'error'];
                const type = types[Math.floor(Math.random() * types.length)];
                const messages = {
                    info: 'This is an informational message',
                    success: 'Operation completed successfully!',
                    warning: 'This is a warning message',
                    error: 'An error has occurred'
                };

                window.WebSocketManager.sendNotification(
                    `Test ${type.charAt(0).toUpperCase() + type.slice(1)} Notification`,
                    messages[type],
                    { type }
                );
                this.messagesSent++;
            }
        },

        clearNotifications() {
            this.notifications = [];
        },

        sendChatMessage() {
            if (this.newMessage.trim() && window.WebSocketManager) {
                window.WebSocketManager.sendChatMessage(this.newMessage, this.currentChannel);
                
                // Add to local messages for demo
                this.chatMessages.push({
                    id: Date.now(),
                    message: this.newMessage,
                    channel: this.currentChannel,
                    username: 'You',
                    timestamp: Date.now()
                });
                
                this.newMessage = '';
                this.messagesSent++;
            }
        },

        refreshServerStatus() {
            if (window.WebSocketManager) {
                window.WebSocketManager.requestServerStatus();
                this.messagesSent++;
            }
            
            // Add demo data for immediate feedback
            this.addDemoServerData();
        },

        updateMyPresence() {
            if (window.WebSocketManager) {
                window.WebSocketManager.updateUserPresence(this.myStatus);
                this.messagesSent++;
            }
        },

        startDemoData() {
            // Add some demo notifications periodically
            setInterval(() => {
                if (this.connectionStatus === 'connected' && Math.random() > 0.7) {
                    this.addDemoNotification();
                }
            }, 10000);

            // Add demo presence data
            this.addDemoPresenceData();
        },

        addDemoNotification() {
            const types = ['info', 'success', 'warning'];
            const type = types[Math.floor(Math.random() * types.length)];
            const messages = {
                info: 'Server maintenance scheduled for tonight',
                success: 'New server connection established',
                warning: 'High traffic detected on Server #3'
            };

            this.notifications.unshift({
                id: Date.now(),
                type,
                title: `System ${type.charAt(0).toUpperCase() + type.slice(1)}`,
                message: messages[type],
                timestamp: Date.now()
            });
        },

        addDemoServerData() {
            const servers = ['server-1', 'server-2', 'server-3'];
            const statuses = ['online', 'online', 'degraded'];
            
            servers.forEach((serverId, index) => {
                this.serverStatuses[serverId] = {
                    serverId,
                    name: `Server ${index + 1}`,
                    status: statuses[index],
                    load: `${Math.floor(Math.random() * 100)}%`,
                    connections: Math.floor(Math.random() * 500),
                    lastUpdate: Date.now()
                };
            });
        },

        addDemoPresenceData() {
            const users = ['user-1', 'user-2', 'user-3'];
            const statuses = ['online', 'away', 'busy'];
            const usernames = ['Alice', 'Bob', 'Charlie'];
            
            users.forEach((userId, index) => {
                this.userPresence[userId] = {
                    userId,
                    username: usernames[index],
                    status: statuses[index],
                    lastSeen: Date.now() - Math.floor(Math.random() * 3600000)
                };
            });
        },

        formatTime(timestamp) {
            if (!timestamp) return 'Never';
            return new Date(timestamp).toLocaleTimeString();
        }
    }));
});
</script>
