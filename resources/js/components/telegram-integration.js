// Telegram Bot Integration Components
import Alpine from 'alpinejs';

// Telegram API Service
class TelegramAPIService
{
    constructor ()
    {
        this.botToken = null;
        this.baseURL = 'https://api.telegram.org/bot';
        this.webhookURL = null;
    }

    setBotToken ( token )
    {
        this.botToken = token;
    }

    async makeRequest ( method, params = {} )
    {
        if ( !this.botToken )
        {
            throw new Error( 'Bot token not set' );
        }

        const url = `${ this.baseURL }${ this.botToken }/${ method }`;
        const response = await fetch( url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify( params )
        } );

        if ( !response.ok )
        {
            throw new Error( `Telegram API error: ${ response.statusText }` );
        }

        return await response.json();
    }

    async getBotInfo ()
    {
        return await this.makeRequest( 'getMe' );
    }

    async getWebhookInfo ()
    {
        return await this.makeRequest( 'getWebhookInfo' );
    }

    async setWebhook ( url, options = {} )
    {
        return await this.makeRequest( 'setWebhook', { url, ...options } );
    }

    async deleteWebhook ()
    {
        return await this.makeRequest( 'deleteWebhook' );
    }

    async sendMessage ( chatId, text, options = {} )
    {
        return await this.makeRequest( 'sendMessage', {
            chat_id: chatId,
            text,
            ...options
        } );
    }

    async getUpdates ( offset = 0, limit = 100 )
    {
        return await this.makeRequest( 'getUpdates', { offset, limit } );
    }

    async getChat ( chatId )
    {
        return await this.makeRequest( 'getChat', { chat_id: chatId } );
    }

    async getChatMembersCount ( chatId )
    {
        return await this.makeRequest( 'getChatMembersCount', { chat_id: chatId } );
    }
}

// Telegram Bot Control Panel Component
function telegramBotControlPanel ()
{
    return {
        // State
        botToken: '',
        botInfo: null,
        webhookInfo: null,
        isConnected: false,
        isLoading: false,
        error: null,

        // Test message data
        testMessage: {
            chatId: '',
            text: 'Hello from 1000Proxy Bot! 🤖',
            parseMode: 'HTML',
            replyMarkup: null
        },

        // Commands
        commands: [
            { command: 'start', description: 'Start the bot and link account' },
            { command: 'buy', description: 'Purchase a new proxy' },
            { command: 'myproxies', description: 'View your active proxies' },
            { command: 'balance', description: 'Check wallet balance' },
            { command: 'topup', description: 'Add funds to wallet' },
            { command: 'support', description: 'Contact support' },
            { command: 'config', description: 'Get proxy configuration' },
            { command: 'reset', description: 'Reset proxy settings' },
            { command: 'status', description: 'Check proxy status' },
            { command: 'help', description: 'Show all available commands' }
        ],

        // Recent activity
        recentActivity: [],

        // API service
        telegramAPI: new TelegramAPIService(),

        // Initialize
        async init ()
        {
            await this.loadBotSettings();
            if ( this.botToken )
            {
                await this.connectBot();
            }
            this.loadRecentActivity();
        },

        // Load bot settings from backend
        async loadBotSettings ()
        {
            try
            {
                const response = await fetch( '/telegram/webhook-info' );
                if ( response.ok )
                {
                    const data = await response.json();
                    // Keep webhook info if available
                    this.webhookInfo = data.data || data.webhook_info || null;
                }
            } catch ( error )
            {
                console.error( 'Failed to load bot settings:', error );
            }
        },

        // Connect to bot
        async connectBot ()
        {
            if ( !this.botToken )
            {
                this.error = 'Please enter a bot token';
                return;
            }

            this.isLoading = true;
            this.error = null;

            try
            {
                this.telegramAPI.setBotToken( this.botToken );

                // Test connection by getting bot info
                const botInfo = await this.telegramAPI.getBotInfo();
                if ( botInfo.ok )
                {
                    this.botInfo = botInfo.result;
                    this.isConnected = true;

                    // Get webhook info
                    const webhookInfo = await this.telegramAPI.getWebhookInfo();
                    this.webhookInfo = webhookInfo.result;

                    // Save token to backend
                    await this.saveBotToken();

                    this.addActivity( 'Bot connected successfully', 'success' );
                } else
                {
                    throw new Error( botInfo.description || 'Failed to connect to bot' );
                }
            } catch ( error )
            {
                this.error = error.message;
                this.isConnected = false;
                this.addActivity( `Connection failed: ${ error.message }`, 'error' );
            } finally
            {
                this.isLoading = false;
            }
        },

        // Disconnect bot
        async disconnectBot ()
        {
            this.isConnected = false;
            this.botInfo = null;
            this.webhookInfo = null;
            this.botToken = '';
            await this.saveBotToken();
            this.addActivity( 'Bot disconnected', 'info' );
        },

        // Save bot token to backend
        async saveBotToken ()
        {
            try
            {
                // No direct token storage endpoint; token must be managed in env/config on the server
                // This is a no-op to keep UI logic simple
                return true;
            } catch ( error )
            {
                console.error( 'Failed to save bot token:', error );
            }
        },

        // Set webhook
        async setWebhook ()
        {
            if ( !this.isConnected )
            {
                this.error = 'Bot not connected';
                return;
            }

            try
            {
                const webhookUrl = `${ window.location.origin }/telegram/webhook`;
                const setResp = await fetch( '/telegram/set-webhook', { method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector( 'meta[name="csrf-token"]' ).content } } );
                const setJson = setResp.ok ? await setResp.json() : { success: false };

                if ( setJson.success )
                {
                    // Refresh webhook info
                    const respInfo = await fetch( '/telegram/webhook-info' );
                    if ( respInfo.ok )
                    {
                        const infoJson = await respInfo.json();
                        this.webhookInfo = infoJson.data || infoJson;
                    }
                    this.addActivity( 'Webhook set successfully', 'success' );
                } else
                {
                    throw new Error( setJson.message || 'Failed to set webhook' );
                }
            } catch ( error )
            {
                this.error = error.message;
                this.addActivity( `Webhook setup failed: ${ error.message }`, 'error' );
            }
        },

        // Delete webhook
        async deleteWebhook ()
        {
            if ( !this.isConnected )
            {
                this.error = 'Bot not connected';
                return;
            }

            try
            {
                const delResp = await fetch( '/telegram/webhook', { method: 'DELETE', headers: { 'X-CSRF-TOKEN': document.querySelector( 'meta[name="csrf-token"]' ).content } } );
                const result = delResp.ok ? await delResp.json() : { success: false, message: 'Request failed' };

                if ( result.success )
                {
                    this.webhookInfo = { url: '', has_custom_certificate: false };
                    this.addActivity( 'Webhook deleted successfully', 'success' );
                } else
                {
                    throw new Error( result.message || 'Failed to delete webhook' );
                }
            } catch ( error )
            {
                this.error = error.message;
                this.addActivity( `Webhook deletion failed: ${ error.message }`, 'error' );
            }
        },

        // Send test message
        async sendTestMessage ()
        {
            if ( !this.isConnected )
            {
                this.error = 'Bot not connected';
                return;
            }

            if ( !this.testMessage.chatId )
            {
                this.error = 'Please enter a chat ID';
                return;
            }

            try
            {
                this.isLoading = true;
                const resp = await fetch( '/telegram/send-test-message', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector( 'meta[name="csrf-token"]' ).content
                    },
                    body: JSON.stringify( {
                        chat_id: this.testMessage.chatId,
                        message: this.testMessage.text
                    } )
                } );
                const data = await resp.json();

                if ( resp.ok && data.success )
                {
                    this.addActivity( `Test message sent to ${ this.testMessage.chatId }`, 'success' );
                } else
                {
                    throw new Error( data.message || 'Failed to send message' );
                }
            } catch ( error )
            {
                this.error = error.message;
                this.addActivity( `Message failed: ${ error.message }`, 'error' );
            } finally
            {
                this.isLoading = false;
            }
        },

        // Set bot commands
        async setBotCommands ()
        {
            if ( !this.isConnected )
            {
                this.error = 'Bot not connected';
                return;
            }

            try
            {
                const result = await this.telegramAPI.makeRequest( 'setMyCommands', {
                    commands: this.commands
                } );

                if ( result.ok )
                {
                    this.addActivity( 'Bot commands updated successfully', 'success' );
                } else
                {
                    throw new Error( result.description || 'Failed to set commands' );
                }
            } catch ( error )
            {
                this.error = error.message;
                this.addActivity( `Commands update failed: ${ error.message }`, 'error' );
            }
        },

        // Add activity
        addActivity ( message, type = 'info' )
        {
            this.recentActivity.unshift( {
                id: Date.now(),
                message,
                type,
                timestamp: new Date().toISOString()
            } );

            // Keep only last 10 activities
            if ( this.recentActivity.length > 10 )
            {
                this.recentActivity = this.recentActivity.slice( 0, 10 );
            }
        },

        // Load recent activity from backend
        async loadRecentActivity ()
        {
            try
            {
                const response = await fetch( '/telegram/bot-stats' );
                if ( response.ok )
                {
                    const json = await response.json();
                    const stats = json.data || {};
                    const ri = stats.recent_interactions || {};
                    this.recentActivity = [
                        { id: Date.now(), message: `Linked users: ${ stats.total_linked_users ?? 'N/A' }`, type: 'info', timestamp: new Date().toISOString() },
                        { id: Date.now() + 1, message: `24h: ${ ri.last_24h ?? 'N/A' }, 7d: ${ ri.last_7d ?? 'N/A' }`, type: 'info', timestamp: new Date().toISOString() }
                    ];
                }
            } catch ( error )
            {
                console.error( 'Failed to load recent activity:', error );
            }
        },

        // Get activity icon
        getActivityIcon ( type )
        {
            const icons = {
                success: '✅',
                error: '❌',
                warning: '⚠️',
                info: 'ℹ️'
            };
            return icons[ type ] || icons.info;
        },

        // Get activity color
        getActivityColor ( type )
        {
            const colors = {
                success: 'text-green-600 dark:text-green-400',
                error: 'text-red-600 dark:text-red-400',
                warning: 'text-yellow-600 dark:text-yellow-400',
                info: 'text-blue-600 dark:text-blue-400'
            };
            return colors[ type ] || colors.info;
        },

        // Format date
        formatDate ( dateString )
        {
            return new Date( dateString ).toLocaleString();
        }
    };
}

// User Telegram Linking Component
function userTelegramLinking ()
{
    return {
        // State
        linkingCode: '',
        qrCodeUrl: '',
        isGenerating: false,
        linkedUsers: [],
        isLoading: false,
        error: null,

        // Filters
        searchQuery: '',
        statusFilter: 'all',

        // Statistics
        stats: {
            totalLinked: 0,
            activeToday: 0,
            pendingLinks: 0,
            averageResponseTime: 0
        },

        // Initialize
        async init ()
        {
            await this.loadLinkedUsers();
            await this.loadStats();
        },

        // Generate linking code and QR
        async generateLinkingCode ()
        {
            this.isGenerating = true;
            this.error = null;

            try
            {
                const response = await fetch( '/telegram/generate-link', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector( 'meta[name="csrf-token"]' ).content
                    }
                } );

                if ( response.ok )
                {
                    const data = await response.json();
                    this.linkingCode = data.code;
                    this.qrCodeUrl = data.qr_url;
                } else
                {
                    throw new Error( 'Failed to generate linking code' );
                }
            } catch ( error )
            {
                this.error = error.message;
            } finally
            {
                this.isGenerating = false;
            }
        },

        // Load linked users
        async loadLinkedUsers ()
        {
            this.isLoading = true;

            try
            {
                const response = await fetch( '/telegram/linked-users' );
                if ( response.ok )
                {
                    const data = await response.json();
                    this.linkedUsers = data.users || [];
                }
            } catch ( error )
            {
                this.error = 'Failed to load linked users';
            } finally
            {
                this.isLoading = false;
            }
        },

        // Load statistics
        async loadStats ()
        {
            try
            {
                const response = await fetch( '/telegram/stats' );
                if ( response.ok )
                {
                    const data = await response.json();
                    this.stats = { ...this.stats, ...data.stats };
                }
            } catch ( error )
            {
                console.error( 'Failed to load stats:', error );
            }
        },

        // Unlink user
        async unlinkUser ( userId )
        {
            if ( !confirm( 'Are you sure you want to unlink this user?' ) )
            {
                return;
            }

            try
            {
                const response = await fetch( `/telegram/unlink-user/${ userId }`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector( 'meta[name="csrf-token"]' ).content
                    }
                } );

                if ( response.ok )
                {
                    await this.loadLinkedUsers();
                    await this.loadStats();
                } else
                {
                    throw new Error( 'Failed to unlink user' );
                }
            } catch ( error )
            {
                this.error = error.message;
            }
        },

        // Send test message to user
        async sendTestMessage ( chatId )
        {
            try
            {
                const response = await fetch( '/telegram/send-notification', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector( 'meta[name="csrf-token"]' ).content
                    },
                    body: JSON.stringify( { chat_id: chatId } )
                } );

                if ( response.ok )
                {
                    alert( 'Test message sent successfully!' );
                } else
                {
                    throw new Error( 'Failed to send test message' );
                }
            } catch ( error )
            {
                this.error = error.message;
            }
        },

        // Get filtered users
        get filteredUsers ()
        {
            let filtered = this.linkedUsers;

            // Search filter
            if ( this.searchQuery )
            {
                const query = this.searchQuery.toLowerCase();
                filtered = filtered.filter( user =>
                    user.telegram_username?.toLowerCase().includes( query ) ||
                    user.first_name?.toLowerCase().includes( query ) ||
                    user.last_name?.toLowerCase().includes( query ) ||
                    user.email?.toLowerCase().includes( query )
                );
            }

            // Status filter
            if ( this.statusFilter !== 'all' )
            {
                filtered = filtered.filter( user =>
                {
                    switch ( this.statusFilter )
                    {
                        case 'active':
                            return user.last_activity && new Date( user.last_activity ) > new Date( Date.now() - 24 * 60 * 60 * 1000 );
                        case 'inactive':
                            return !user.last_activity || new Date( user.last_activity ) <= new Date( Date.now() - 24 * 60 * 60 * 1000 );
                        case 'verified':
                            return user.is_verified;
                        case 'unverified':
                            return !user.is_verified;
                        default:
                            return true;
                    }
                } );
            }

            return filtered;
        },

        // Get user status
        getUserStatus ( user )
        {
            if ( !user.last_activity ) return 'Never active';

            const lastActivity = new Date( user.last_activity );
            const now = new Date();
            const diffMs = now - lastActivity;
            const diffMins = Math.floor( diffMs / 60000 );

            if ( diffMins < 5 ) return 'Online';
            if ( diffMins < 60 ) return `${ diffMins }m ago`;
            if ( diffMins < 1440 ) return `${ Math.floor( diffMins / 60 ) }h ago`;

            return `${ Math.floor( diffMins / 1440 ) }d ago`;
        },

        // Get status color
        getStatusColor ( user )
        {
            if ( !user.last_activity ) return 'text-gray-500';

            const lastActivity = new Date( user.last_activity );
            const now = new Date();
            const diffMs = now - lastActivity;
            const diffMins = Math.floor( diffMs / 60000 );

            if ( diffMins < 5 ) return 'text-green-500';
            if ( diffMins < 60 ) return 'text-yellow-500';

            return 'text-red-500';
        },

        // Format date
        formatDate ( dateString )
        {
            if ( !dateString ) return 'Never';
            return new Date( dateString ).toLocaleDateString();
        }
    };
}

// Telegram Notification Center Component
function telegramNotificationCenter ()
{
    return {
        // State
        notifications: [],
        templates: [],
        isLoading: false,
        error: null,

        // New notification
        newNotification: {
            title: '',
            message: '',
            recipients: 'all',
            template_id: '',
            schedule_at: '',
            priority: 'normal',
            include_unsubscribed: false
        },

        // Preview
        previewMode: false,
        previewData: null,

        // Filters
        statusFilter: 'all',
        priorityFilter: 'all',
        dateRange: '7d',

        // Initialize
        async init ()
        {
            await this.loadNotifications();
            await this.loadTemplates();
        },

        // Load notifications
        async loadNotifications ()
        {
            this.isLoading = true;

            try
            {
                const response = await fetch( '/telegram/notifications' );
                if ( response.ok )
                {
                    const data = await response.json();
                    this.notifications = data.notifications || [];
                }
            } catch ( error )
            {
                this.error = 'Failed to load notifications';
            } finally
            {
                this.isLoading = false;
            }
        },

        // Load templates
        async loadTemplates ()
        {
            try
            {
                const response = await fetch( '/telegram/templates' );
                if ( response.ok )
                {
                    const data = await response.json();
                    this.templates = data.templates || [];
                }
            } catch ( error )
            {
                console.error( 'Failed to load templates:', error );
            }
        },

        // Send notification
        async sendNotification ()
        {
            if ( !this.newNotification.title || !this.newNotification.message )
            {
                this.error = 'Title and message are required';
                return;
            }

            try
            {
                const response = await fetch( '/telegram/send-notification', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector( 'meta[name="csrf-token"]' ).content
                    },
                    body: JSON.stringify( this.newNotification )
                } );

                if ( response.ok )
                {
                    await this.loadNotifications();
                    this.resetNotificationForm();
                    alert( 'Notification sent successfully!' );
                } else
                {
                    throw new Error( 'Failed to send notification' );
                }
            } catch ( error )
            {
                this.error = error.message;
            }
        },

        // Preview notification
        async previewNotification ()
        {
            if ( !this.newNotification.message )
            {
                this.error = 'Message is required for preview';
                return;
            }

            try
            {
                const response = await fetch( '/telegram/preview-notification', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector( 'meta[name="csrf-token"]' ).content
                    },
                    body: JSON.stringify( this.newNotification )
                } );

                if ( response.ok )
                {
                    const data = await response.json();
                    this.previewData = data.preview;
                    this.previewMode = true;
                } else
                {
                    throw new Error( 'Failed to generate preview' );
                }
            } catch ( error )
            {
                this.error = error.message;
            }
        },

        // Close preview
        closePreview ()
        {
            this.previewMode = false;
            this.previewData = null;
        },

        // Reset form
        resetNotificationForm ()
        {
            this.newNotification = {
                title: '',
                message: '',
                recipients: 'all',
                template_id: '',
                schedule_at: '',
                priority: 'normal',
                include_unsubscribed: false
            };
        },

        // Load template
        loadTemplate ( templateId )
        {
            const template = this.templates.find( t => t.id == templateId );
            if ( template )
            {
                this.newNotification.title = template.title;
                this.newNotification.message = template.content;
            }
        },

        // Delete notification
        async deleteNotification ( notificationId )
        {
            if ( !confirm( 'Are you sure you want to delete this notification?' ) )
            {
                return;
            }

            try
            {
                const response = await fetch( `/telegram/notifications/${ notificationId }`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector( 'meta[name="csrf-token"]' ).content
                    }
                } );

                if ( response.ok )
                {
                    await this.loadNotifications();
                } else
                {
                    throw new Error( 'Failed to delete notification' );
                }
            } catch ( error )
            {
                this.error = error.message;
            }
        },

        // Get filtered notifications
        get filteredNotifications ()
        {
            let filtered = this.notifications;

            // Status filter
            if ( this.statusFilter !== 'all' )
            {
                filtered = filtered.filter( notification => notification.status === this.statusFilter );
            }

            // Priority filter
            if ( this.priorityFilter !== 'all' )
            {
                filtered = filtered.filter( notification => notification.priority === this.priorityFilter );
            }

            // Date range filter
            if ( this.dateRange !== 'all' )
            {
                const days = parseInt( this.dateRange.replace( 'd', '' ) );
                const cutoff = new Date( Date.now() - days * 24 * 60 * 60 * 1000 );
                filtered = filtered.filter( notification => new Date( notification.created_at ) >= cutoff );
            }

            return filtered.sort( ( a, b ) => new Date( b.created_at ) - new Date( a.created_at ) );
        },

        // Get status color
        getStatusColor ( status )
        {
            const colors = {
                sent: 'text-green-600 bg-green-100 dark:bg-green-900 dark:text-green-200',
                pending: 'text-yellow-600 bg-yellow-100 dark:bg-yellow-900 dark:text-yellow-200',
                failed: 'text-red-600 bg-red-100 dark:bg-red-900 dark:text-red-200',
                scheduled: 'text-blue-600 bg-blue-100 dark:bg-blue-900 dark:text-blue-200'
            };
            return colors[ status ] || 'text-gray-600 bg-gray-100';
        },

        // Get priority color
        getPriorityColor ( priority )
        {
            const colors = {
                high: 'text-red-600',
                normal: 'text-blue-600',
                low: 'text-gray-600'
            };
            return colors[ priority ] || colors.normal;
        },

        // Format date
        formatDate ( dateString )
        {
            return new Date( dateString ).toLocaleString();
        }
    };
}

// Export to window for Alpine registration
window.telegramBotControlPanel = telegramBotControlPanel;
window.userTelegramLinking = userTelegramLinking;
window.telegramNotificationCenter = telegramNotificationCenter;
