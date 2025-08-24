<?php

use Telegram\Bot\Commands\HelpCommand;

return [
    // Driver to use for Telegram integration: 'bot_api' (default) or 'tdlib'
    'driver' => env('TELEGRAM_DRIVER', 'bot_api'),

    // Optional custom base Bot API URL (e.g., self-hosted telegram-bot-api). Example: http://telegram-bot-api:8081
    'base_bot_url' => env('TELEGRAM_BASE_BOT_URL', null),

    // TDLib gateway settings (when driver = tdlib)
    'tdlib' => [
        // Base URL of the TDLib gateway service (we'll scaffold it under docker-compose)
        'gateway_url' => env('TDLIB_GATEWAY_URL', 'http://tdlib-gateway:8080'),
        // Optional shared secret for authenticating requests between app and gateway
        'api_key' => env('TDLIB_GATEWAY_KEY', env('APP_KEY')),
        // Default timeout for gateway calls (seconds)
        'timeout' => (int) env('TDLIB_GATEWAY_TIMEOUT', 10),
    ],
    /*
    |--------------------------------------------------------------------------
    | Your Telegram Bots
    |--------------------------------------------------------------------------
    | You may use multiple bots at once using the manager class. Each bot
    | that you own should be configured here.
    |
    | Here are each of the telegram bots config parameters.
    |
    | Supported Params:
    |
    | - name: The *personal* name you would like to refer to your bot as.
    |
    |       - token:    Your Telegram Bot's Access Token.
                        Refer for more details: https://core.telegram.org/bots#botfather
    |                   Example: (string) '123456:ABC-DEF1234ghIkl-zyx57W2v1u123ew11'.
    |
    |       - commands: (Optional) Commands to register for this bot,
    |                   Supported Values: "Command Group Name", "Shared Command Name", "Full Path to Class".
    |                   Default: Registers Global Commands.
    |                   Example: (array) [
    |                       'admin', // Command Group Name.
    |                       'status', // Shared Command Name.
    |                       Acme\Project\Commands\BotFather\HelloCommand::class,
    |                       Acme\Project\Commands\BotFather\ByeCommand::class,
    |             ]
    */
    'bots' => [
        'mybot' => [
            'token' => env('TELEGRAM_BOT_TOKEN', 'YOUR-BOT-TOKEN'),
            'certificate_path' => env('TELEGRAM_CERTIFICATE_PATH', 'YOUR-CERTIFICATE-PATH'),
            'webhook_url' => env('TELEGRAM_WEBHOOK_URL', 'YOUR-BOT-WEBHOOK-URL'),
            /*
             * @see https://core.telegram.org/bots/api#update
             */
            'allowed_updates' => null,
            'commands' => [
                // Acme\Project\Commands\MyTelegramBot\BotCommand::class
            ],
        ],

        //        'mySecondBot' => [
        //            'token' => '123456:abc',
        //        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Bot Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the bots you wish to use as
    | your default bot for regular use.
    |
    */
    'default' => 'mybot',

    /*
    |--------------------------------------------------------------------------
    | Asynchronous Requests [Optional]
    |--------------------------------------------------------------------------
    |
    | When set to True, All the requests would be made non-blocking (Async).
    |
    | Default: false
    | Possible Values: (Boolean) "true" OR "false"
    |
    */
    'async_requests' => env('TELEGRAM_ASYNC_REQUESTS', false),

    /*
    |--------------------------------------------------------------------------
    | HTTP Client Handler [Optional]
    |--------------------------------------------------------------------------
    |
    | If you'd like to use a custom HTTP Client Handler.
    | Should be an instance of \Telegram\Bot\HttpClients\HttpClientInterface
    |
    | Default: GuzzlePHP
    |
    */
    'http_client_handler' => null,

    /*
    |--------------------------------------------------------------------------
    | Base Bot Url [Optional]
    |--------------------------------------------------------------------------
    |
    | If you'd like to use a custom Base Bot Url.
    | Should be a local bot api endpoint or a proxy to the telegram api endpoint
    |
    | Default: https://api.telegram.org/bot
    |
    */
    'base_bot_url' => null,

    /*
    |--------------------------------------------------------------------------
    | Resolve Injected Dependencies in commands [Optional]
    |--------------------------------------------------------------------------
    |
    | Using Laravel's IoC container, we can easily type hint dependencies in
    | our command's constructor and have them automatically resolved for us.
    |
    | Default: true
    | Possible Values: (Boolean) "true" OR "false"
    |
    */
    'resolve_command_dependencies' => true,

    /*
    |--------------------------------------------------------------------------
    | Register Telegram Global Commands [Optional]
    |--------------------------------------------------------------------------
    |
    | If you'd like to use the SDK's built in command handler system,
    | You can register all the global commands here.
    |
    | Global commands will apply to all the bots in system and are always active.
    |
    | The command class should extend the \Telegram\Bot\Commands\Command class.
    |
    | Default: The SDK registers, a help command which when a user sends /help
    | will respond with a list of available commands and description.
    |
    */
    'commands' => [
        HelpCommand::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Command Groups [Optional]
    |--------------------------------------------------------------------------
    |
    | You can organize a set of commands into groups which can later,
    | be re-used across all your bots.
    |
    | You can create 4 types of groups:
    | 1. Group using full path to command classes.
    | 2. Group using shared commands: Provide the key name of the shared command
    | and the system will automatically resolve to the appropriate command.
    | 3. Group using other groups of commands: You can create a group which uses other
    | groups of commands to bundle them into one group.
    | 4. You can create a group with a combination of 1, 2 and 3 all together in one group.
    |
    | Examples shown below are by the group type for you to understand each of them.
    */
    'command_groups' => [
        /* // Group Type: 1
           'commmon' => [
                Acme\Project\Commands\TodoCommand::class,
                Acme\Project\Commands\TaskCommand::class,
           ],
        */

        /* // Group Type: 2
           'subscription' => [
                'start', // Shared Command Name.
                'stop', // Shared Command Name.
           ],
        */

        /* // Group Type: 3
            'auth' => [
                Acme\Project\Commands\LoginCommand::class,
                Acme\Project\Commands\SomeCommand::class,
            ],

            'stats' => [
                Acme\Project\Commands\UserStatsCommand::class,
                Acme\Project\Commands\SubscriberStatsCommand::class,
                Acme\Project\Commands\ReportsCommand::class,
            ],

            'admin' => [
                'auth', // Command Group Name.
                'stats' // Command Group Name.
            ],
        */

        /* // Group Type: 4
           'myBot' => [
                'admin', // Command Group Name.
                'subscription', // Command Group Name.
                'status', // Shared Command Name.
                'Acme\Project\Commands\BotCommand' // Full Path to Command Class.
           ],
        */
    ],

    /*
    |--------------------------------------------------------------------------
    | Shared Commands [Optional]
    |--------------------------------------------------------------------------
    |
    | Shared commands let you register commands that can be shared between,
    | one or more bots across the project.
    |
    | This will help you prevent from having to register same set of commands,
    | for each bot over and over again and make it easier to maintain them.
    |
    | Shared commands are not active by default, You need to use the key name to register them,
    | individually in a group of commands or in bot commands.
    | Think of this as a central storage, to register, reuse and maintain them across all bots.
    |
    */
    'shared_commands' => [
        // 'start' => Acme\Project\Commands\StartCommand::class,
        // 'stop' => Acme\Project\Commands\StopCommand::class,
        // 'status' => Acme\Project\Commands\StatusCommand::class,
    ],

    //
    // Locale behavior for bot interactions
    //
    'locale' => [
        // When true (default), always prefer Telegram device/app language for every update
        'prefer_device' => (bool) env('TELEGRAM_LOCALE_PREFER_DEVICE', true),
        // When true, a manually set override (/lang) will be honored over device language
        'honor_manual_override' => (bool) env('TELEGRAM_LOCALE_HONOR_MANUAL', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Chat Cleanup (Ephemeral UX)
    |--------------------------------------------------------------------------
    | When enabled, the bot deletes its previous message in a chat before
    | sending a new one to keep the conversation clean. Only bot messages
    | can be deleted in private chats. Users' messages are not deleted.
    */
    'cleanup' => [
        'ephemeral' => (bool) env('TELEGRAM_CLEANUP_EPHEMERAL', false),
        // Keep last N bot messages per chat (0 = delete last one each time)
        // For rate limit safety, we default to 0 (delete only the last one)
        'keep' => (int) env('TELEGRAM_CLEANUP_KEEP', 0),
        // Cache TTL for storing last message id(s) (seconds)
        'ttl' => (int) env('TELEGRAM_CLEANUP_TTL', 86400),
    // When using TDLib driver, optionally delete the entire chat history instead of individual bot messages
    'use_delete_chat_history' => (bool) env('TELEGRAM_CLEANUP_DELETE_CHAT_HISTORY', false),
    // TDLib deleteChatHistory options
    'remove_from_chat_list' => (bool) env('TELEGRAM_CLEANUP_REMOVE_FROM_LIST', false),
    'revoke' => (bool) env('TELEGRAM_CLEANUP_REVOKE', false),
    ],
];
