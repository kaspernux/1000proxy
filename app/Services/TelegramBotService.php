<?php

namespace App\Services;


use App\Models\User;
use App\Models\Customer;
use App\Models\ServerPlan;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\PaymentMethod;
use App\Models\ServerClient;
use App\Models\Server;
use App\Jobs\ProcessXuiOrder;
use Illuminate\Support\Facades\Log;
use Telegram\Bot\Api;
use Telegram\Bot\Objects\Update;
use Telegram\Bot\Objects\Message;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Telegram\Bot\Keyboard\Keyboard;
use Illuminate\Support\Facades\Lang;
use App\Services\TemplateRenderer;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Storage;
use App\Services\LocaleService;
use App\Services\Telegram\TdlibGatewayClient;

class TelegramBotService
{
    protected $telegram;
    protected bool $enhanced = true;
    protected TemplateRenderer $templates;
    protected ?TdlibGatewayClient $tdlib = null;

    public function __construct()
    {
        // Choose driver between Bot API and TDLib gateway
        $driver = config('telegram.driver', 'bot_api');
        if ($driver === 'tdlib') {
            $this->tdlib = app(TdlibGatewayClient::class);
        }
        $this->telegram = new Api(config('services.telegram.bot_token'));
        // If a custom base Bot API URL is configured, use it (e.g., self-hosted telegram-bot-api backed by TDLib)
        $base = config('telegram.base_bot_url') ?? config('telegram.base_bot_api_url') ?? null;
        if ($base && method_exists($this->telegram, 'setBaseBotUrl')) {
            $this->telegram->setBaseBotUrl(rtrim($base, '/') . '/bot');
        }
        $this->enhanced = (bool) (config('services.telegram.enhanced') ?? env('TELEGRAM_BOT_ENHANCED', true));
        $this->templates = app(TemplateRenderer::class);
    }

    /**
     * Map a base locale to common regional/variant aliases to improve Telegram language_code matching.
     * Example: fr => ['fr','fr-FR','fr_FR']
     */
    protected function languageAliases(string $base): array
    {
        $base = strtolower(str_replace('_', '-', $base));
        $map = [
            'en' => ['en', 'en-US', 'en_GB', 'en-GB', 'en_US'],
            'fr' => ['fr', 'fr-FR', 'fr_FR', 'fr-CA', 'fr_CA'],
            'de' => ['de', 'de-DE', 'de_DE'],
            'es' => ['es', 'es-ES', 'es_ES', 'es-MX', 'es_MX'],
            'pt' => ['pt', 'pt-PT', 'pt_PT', 'pt-BR', 'pt_BR'],
            'zh' => ['zh', 'zh-CN', 'zh_CN', 'zh-TW', 'zh_TW', 'zh-Hans', 'zh-Hant'],
            'ar' => ['ar', 'ar-SA', 'ar_SA'],
            'hi' => ['hi', 'hi-IN', 'hi_IN'],
            'it' => ['it', 'it-IT', 'it_IT'],
            'ja' => ['ja', 'ja-JP', 'ja_JP'],
            'ko' => ['ko', 'ko-KR', 'ko_KR'],
            'tr' => ['tr', 'tr-TR', 'tr_TR'],
            'fa' => ['fa', 'fa-IR', 'fa_IR', 'fa-AF', 'fa_AF'],
            'id' => ['id', 'id-ID', 'id_ID'],
            'vi' => ['vi', 'vi-VN', 'vi_VN'],
            'pl' => ['pl', 'pl-PL', 'pl_PL'],
            'nl' => ['nl', 'nl-NL', 'nl_NL', 'nl-BE', 'nl_BE'],
            'sv' => ['sv', 'sv-SE', 'sv_SE'],
            'th' => ['th', 'th-TH', 'th_TH'],
            'ur' => ['ur', 'ur-PK', 'ur_PK'],
            'ru' => ['ru', 'ru-RU', 'ru_RU'],
        ];
        return $map[$base] ?? [$base];
    }

    /**
     * Toggle enhanced behavior (no-op public setter for controller feature-flag)
     */
    public function setEnhanced(bool $enhanced): self
    {
        $this->enhanced = $enhanced;
        return $this;
    }

    /**
     * Get the current list of commands from Telegram (default scope, English).
     * Returns array of [command, description] or empty array.
     */
    public function getCurrentCommands(): array
    {
        try {
            $resp = $this->botApi('getMyCommands');
            if (isset($resp['ok']) && $resp['ok'] && isset($resp['result']) && is_array($resp['result'])) {
                return array_map(function ($cmd) {
                    return [
                        'command' => $cmd['command'] ?? '',
                        'description' => $cmd['description'] ?? '',
                    ];
                }, $resp['result']);
            }
        } catch (\Throwable $e) {
            // Log but do not throw
            \Log::warning('Failed to fetch Telegram commands', ['error' => $e->getMessage()]);
        }
        return [];
    }

    /**
     * Set bot commands. If $commands is null, uses repository defaults and localizes per supported locales.
     * Accepts array of [ ['command' => 'start', 'description' => '...'], ... ]
     */
    public function setCommands(?array $commands = null): bool
    {
        try {
            $supported = (array) (config('locales.supported') ?? ['en']);

            // Build default commands list if not provided
            if ($commands === null) {
                $commands = $this->buildDefaultCommands('en');
            }

            // First set a default (no language_code) list using English as base
            $this->botApi('setMyCommands', [
                'commands' => json_encode($commands),
                // default scope: bot_command_scope_default
            ]);

            // Then set per-language localized variants
            foreach ($supported as $locale) {
                $locale = (string) $locale;
                if ($locale === 'en') {
                    continue; // already set as default
                }
                $locCommands = $this->buildDefaultCommands($locale);
                foreach ($this->languageAliases($locale) as $code) {
                    $this->botApi('setMyCommands', [
                        'commands' => json_encode($locCommands),
                        'language_code' => $code,
                    ]);
                }
            }

            \Log::info('Telegram bot commands updated');
            return true;
        } catch (\Throwable $e) {
            \Log::error('Failed to set Telegram commands', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Default commands for the bot, localized via translations with safe fallbacks.
     * Returns [['command' => string, 'description' => string], ...]
     */
    protected function buildDefaultCommands(string $locale = 'en'): array
    {
        $t = function (string $key, string $fallback): string {
            try {
                $v = (string) \Lang::get($key);
                if ($v !== '' && $v !== $key) return $v;
                // Fallback to legacy commands group if cmd.* is missing
                if (str_starts_with($key, 'telegram.cmd.')) {
                    $alt = str_replace('telegram.cmd.', 'telegram.commands.', $key);
                    $v2 = (string) \Lang::get($alt);
                    if ($v2 !== '' && $v2 !== $alt) return $v2;
                }
            } catch (\Throwable $e) {}
            return $fallback;
        };

        // Temporarily switch locale for lookup
        $orig = app()->getLocale();
        try { app()->setLocale($locale); } catch (\Throwable $e) {}

        $items = [
            ['command' => 'start',     'description' => $t('telegram.cmd.start', 'Start or welcome message')],
            ['command' => 'menu',      'description' => $t('telegram.cmd.menu', 'Open main menu')],
            ['command' => 'link',      'description' => $t('telegram.cmd.link', 'Link your Telegram to your account')],
            ['command' => 'plans',     'description' => $t('telegram.cmd.plans', 'Browse plans')],
            ['command' => 'myproxies', 'description' => $t('telegram.cmd.myproxies', 'My proxies and configs')],
            ['command' => 'orders',    'description' => $t('telegram.cmd.orders', 'Order history')],
            ['command' => 'balance',   'description' => $t('telegram.cmd.balance', 'Wallet & balance')],
            ['command' => 'topup',     'description' => $t('telegram.cmd.topup', 'Add funds to wallet')],
            ['command' => 'support',   'description' => $t('telegram.cmd.support', 'Get help & support')],
            ['command' => 'profile',   'description' => $t('telegram.cmd.profile', 'Profile settings')],
            ['command' => 'help',      'description' => $t('telegram.cmd.help', 'How to use the bot')],
        ];

        // Restore locale
        try { app()->setLocale($orig); } catch (\Throwable $e) {}
        return $items;
    }



    /**
     * Set bot branding (name, short description, and description)
     */
    public function setBranding(?string $name = null, ?string $shortDescription = null, ?string $description = null): bool
    {
        try {
            if ($name) {
                $this->botApi('setMyName', ['name' => $name]);
            }
            if ($shortDescription) {
                $this->botApi('setMyShortDescription', ['short_description' => $shortDescription]);
            }
            if ($description) {
                $this->botApi('setMyDescription', ['description' => $description]);
            }
            Log::info('Telegram bot branding updated');
            return true;
        } catch (\Throwable $e) {
            Log::error('Failed to set Telegram branding', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Set localized bot branding (name, short description, description) for multiple locales.
     * Uses translation keys telegram.bot.name|short|description per locale with English fallback.
     */
    public function setBrandingLocalized(): bool
    {
        try {
            // Use locales from config to ensure all supported languages are set
            $supported = (array) (config('locales.supported') ?? ['en']);
            $locales = [];
            foreach ($supported as $lc) {
                $lc = (string) $lc;
                $locales[$lc] = $this->languageAliases($lc);
            }

            // Helper to safely fetch by locale with English fallback
            $get = function(string $key, string $locale, string $fallback = ''): string {
                try {
                    $val = (string) \Lang::get($key, [], $locale);
                    if ($val !== '' && $val !== $key) {
                        return $val;
                    }
                } catch (\Throwable $e) {}
                return $fallback;
            };

            // Helper with retry/backoff for 429s
            $call = function (string $endpoint, array $params, int $attempts = 3) {
                $delay = 1; // seconds
                for ($i = 0; $i < $attempts; $i++) {
                    try {
                        $res = $this->botApi($endpoint, $params);
                        // small pacing between calls
                        usleep(300_000); // 0.3s
                        return $res;
                    } catch (\Illuminate\Http\Client\RequestException $e) {
                        $resp = $e->response;
                        $json = $resp ? $resp->json() : null;
                        $retry = $json['parameters']['retry_after'] ?? null;
                        if ($resp && $resp->status() === 429) {
                            $sleep = is_numeric($retry) ? (int)$retry : $delay;
                            sleep($sleep);
                            $delay = min($delay * 2, 8);
                            continue;
                        }
                        // Non-429, do not retry further
                        throw $e;
                    } catch (\Throwable $e) {
                        // Unknown error; small delay then retry
                        usleep(500_000);
                        if ($i === $attempts - 1) throw $e;
                    }
                }
                return ['ok' => false];
            };

            // Default (no language_code) from English
            $defaultName = $get('telegram.bot.name', 'en', '');
            $defaultShort = $get('telegram.bot.short', 'en', '');
            $defaultDesc = $get('telegram.bot.description', 'en', '');
            if ($defaultName) { $call('setMyName', ['name' => $defaultName]); }
            if ($defaultShort) { $call('setMyShortDescription', ['short_description' => $defaultShort]); }
            if ($defaultDesc) { $call('setMyDescription', ['description' => $defaultDesc]); }

            // Per-language variants. Keep the bot name constant across locales for brand consistency.
            foreach ($locales as $locale => $codes) {
                $name = $defaultName; // do not localize name; brand remains identical
                $short = $get('telegram.bot.short', $locale, $defaultShort);
                $desc = $get('telegram.bot.description', $locale, $defaultDesc);
                foreach ($codes as $code) {
                    if ($name) { $call('setMyName', ['name' => $name, 'language_code' => $code]); }
                    if ($short) { $call('setMyShortDescription', ['short_description' => $short, 'language_code' => $code]); }
                    if ($desc) { $call('setMyDescription', ['description' => $desc, 'language_code' => $code]); }
                }
            }

            \Log::info('Telegram localized branding set');
            return true;
        } catch (\Throwable $e) {
            \Log::error('Failed to set localized Telegram branding', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Set default branding (no language_code) using values from a given locale (default en).
     * Useful for quick, short updates to avoid HTTP timeouts.
     */
    public function setDefaultBrandingFromLocale(string $sourceLocale = 'en', array $only = ['name','short','description']): bool
    {
        try {
            $get = function(string $key, string $locale): string {
                try {
                    $val = (string) \Lang::get($key, [], $locale);
                    if ($val !== '' && $val !== $key) return $val;
                } catch (\Throwable $e) {}
                return '';
            };
            $name = $get('telegram.bot.name', $sourceLocale);
            $short = $get('telegram.bot.short', $sourceLocale);
            $desc = $get('telegram.bot.description', $sourceLocale);

            if (in_array('name', $only, true) && $name) {
                $this->botApi('setMyName', ['name' => $name]);
            }
            if (in_array('short', $only, true) && $short) {
                $this->botApi('setMyShortDescription', ['short_description' => $short]);
            }
            if (in_array('description', $only, true) && $desc) {
                $this->botApi('setMyDescription', ['description' => $desc]);
            }
            return true;
        } catch (\Throwable $e) {
            \Log::error('Failed to set default branding', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Set branding for a single locale (language_code).
     * By default, keeps the brand name the same as the default locale unless overridden.
     */
    public function setBrandingForLocale(string $locale, ?string $name = null, ?string $short = null, ?string $description = null, array $only = ['name','short','description']): bool
    {
        try {
            // Resolve values with fallbacks
            $get = function(string $key, ?string $explicit, string $locale, string $fallbackLocale = 'en'): ?string {
                if ($explicit !== null) return $explicit;
                try {
                    $val = (string) \Lang::get($key, [], $locale);
                    if ($val !== '' && $val !== $key) return $val;
                } catch (\Throwable $e) {}
                try {
                    $val2 = (string) \Lang::get($key, [], $fallbackLocale);
                    if ($val2 !== '' && $val2 !== $key) return $val2;
                } catch (\Throwable $e) {}
                return null;
            };

            // Keep the brand name constant if not explicitly provided
            $resolvedName = $get('telegram.bot.name', $name, 'en', 'en');
            $resolvedShort = $get('telegram.bot.short', $short, $locale, 'en');
            $resolvedDesc = $get('telegram.bot.description', $description, $locale, 'en');

            foreach ($this->languageAliases($locale) as $code) {
                $langParam = ['language_code' => $code];
                if (in_array('name', $only, true) && $resolvedName) {
                    $this->botApi('setMyName', ['name' => $resolvedName] + $langParam);
                }
                if (in_array('short', $only, true) && $resolvedShort) {
                    $this->botApi('setMyShortDescription', ['short_description' => $resolvedShort] + $langParam);
                }
                if (in_array('description', $only, true) && $resolvedDesc) {
                    $this->botApi('setMyDescription', ['description' => $resolvedDesc] + $langParam);
                }
            }
            return true;
        } catch (\Throwable $e) {
            \Log::error('Failed to set branding for locale', ['locale' => $locale, 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Set commands only for a single locale (language_code). If locale is 'en' or null, sets default scope.
     */
    public function setCommandsForLocale(?string $locale = null): bool
    {
        try {
            if ($locale === null || $locale === 'en') {
                $commands = $this->buildDefaultCommands('en');
                $this->botApi('setMyCommands', [ 'commands' => json_encode($commands) ]);
            } else {
                $commands = $this->buildDefaultCommands($locale);
                foreach ($this->languageAliases($locale) as $code) {
                    $this->botApi('setMyCommands', [
                        'commands' => json_encode($commands),
                        'language_code' => $code,
                    ]);
                }
            }
            return true;
        } catch (\Throwable $e) {
            \Log::error('Failed to set commands for locale', ['locale' => $locale, 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Configure chat menu button
     * type: 'commands' or 'web_app' (with text + url)
     */
    public function setMenuButton(string $type = 'commands', ?string $text = null, ?string $url = null): bool
    {
        try {
            $menu = ['type' => 'commands'];
            if ($type === 'web_app' && $text && $url) {
                $menu = [
                    'type' => 'web_app',
                    'text' => $text,
                    'web_app' => ['url' => $url]
                ];
            }
            $this->botApi('setChatMenuButton', ['menu_button' => $menu]);
            Log::info('Telegram menu button updated', ['type' => $type]);
            return true;
        } catch (\Throwable $e) {
            Log::error('Failed to set Telegram menu button', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Low-level helper to call Telegram Bot API endpoints not exposed by the SDK.
     * Returns decoded response array; throws on API error.
     */
    protected function botApi(string $endpoint, array $params = []): array
    {
        $token = (string) config('services.telegram.bot_token');
        if ($token === '') {
            throw new \RuntimeException('Missing TELEGRAM_BOT_TOKEN');
        }
    // When TDLib gateway is enabled, forward to gateway raw method
        if ($this->tdlib) {
            return $this->tdlib->rawApi($endpoint, $params);
        }
    $base = rtrim((string) (config('telegram.base_bot_url') ?? 'https://api.telegram.org'), '/');
    // The SDK expects base ends with /bot elsewhere; for direct calls we build fully
    $url = rtrim($base . '/bot' . $token . '/' . ltrim($endpoint, '/'), '/');
        $resp = Http::asForm()->post($url, $params);
        if (!$resp->ok()) {
            $resp->throw();
        }
        $data = $resp->json();
        if (!is_array($data)) {
            throw new \RuntimeException('Invalid Telegram response');
        }
        if (isset($data['ok']) && $data['ok'] !== true) {
            throw new \RuntimeException('Telegram API error: ' . ($data['description'] ?? 'unknown'));
        }
        return $data;
    }

    /**
     * Cache key for storing recent bot message IDs per chat.
     */
    protected function cacheKeyRecentMsgs(int $chatId): string
    {
        return 'tg:last_bot_msgs:' . $chatId;
    }

    /**
     * Remember a sent bot message id for a chat, capped by config cleanup.keep.
     */
    protected function rememberBotMessageId(int $chatId, int $messageId): void
    {
        try {
            $key = $this->cacheKeyRecentMsgs($chatId);
            $ttl = (int) (config('telegram.cleanup.ttl') ?? 86400);
            $keep = max(0, (int) (config('telegram.cleanup.keep') ?? 0));
            $existing = (array) (cache()->get($key) ?? []);
            array_unshift($existing, $messageId);
            if ($keep >= 0) {
                $existing = array_slice($existing, 0, max(1, $keep));
            }
            cache()->put($key, $existing, $ttl);
        } catch (\Throwable $e) {
            // ignore
        }
    }

    /**
     * Delete previous bot message(s) in chat based on config cleanup.keep.
     */
    protected function cleanupPreviousBotMessages(int $chatId): void
    {
        try {
            if (!config('telegram.cleanup.ephemeral')) {
                return;
            }
            // If TDLib is enabled and configured, prefer deleting entire chat history for a fully clean chat
            if ($this->tdlib && (bool) (config('telegram.cleanup.use_delete_chat_history') ?? false)) {
                try {
                    $this->tdlib->rawApi('deleteChatHistory', [
                        'chat_id' => $chatId,
                        'remove_from_chat_list' => (bool) (config('telegram.cleanup.remove_from_chat_list') ?? false),
                        'revoke' => (bool) (config('telegram.cleanup.revoke') ?? false),
                    ]);
                    // After deleting history, clear any remembered ids and return
                    cache()->forget($this->cacheKeyRecentMsgs($chatId));
                    return;
                } catch (\Throwable $e) {
                    // Fall back to per-message deletion
                }
            }
            $key = $this->cacheKeyRecentMsgs($chatId);
            $keep = max(0, (int) (config('telegram.cleanup.keep') ?? 0));
            $ids = (array) (cache()->get($key) ?? []);
            if (empty($ids)) return;

            // If keep = 0, delete the most recent one; if keep = 1, keep last, delete older, etc.
            // We will delete all currently stored ids; new message will be remembered afterward
            foreach ($ids as $mid) {
                try {
                    if ($this->tdlib) {
                        $this->tdlib->rawApi('deleteMessage', ['chat_id' => $chatId, 'message_id' => $mid]);
                    } else {
                        $this->botApi('deleteMessage', ['chat_id' => $chatId, 'message_id' => $mid]);
                    }
                    // small pacing to avoid flood
                    usleep(200_000);
                } catch (\Throwable $e) {
                    // ignore delete errors (message already deleted, insufficient rights, timeout, etc.)
                }
            }
            cache()->forget($key);
        } catch (\Throwable $e) {
            // ignore cleanup failures
        }
    }

    /**
     * Set webhook for Telegram bot
     */
    public function setWebhook(): bool
    {
        try {
            $baseUrl = rtrim((string) config('services.telegram.webhook_url'), '/');
            $secret = (string) (config('services.telegram.secret_token') ?? '');

            // If a secret is configured, support path-based secret for compatibility
            $url = $baseUrl;
            if (!empty($secret)) {
                $url .= '/' . $secret;
            }

            $params = [
                'url' => $url,
                'allowed_updates' => ['message', 'callback_query']
            ];
            if (!empty($secret)) {
                // Also set Telegram's header-based secret for stronger verification
                $params['secret_token'] = $secret;
            }
            if ($this->tdlib) {
                $response = $this->tdlib->setWebhook($params);
            } else {
                $response = $this->telegram->setWebhook($params);
            }

            Log::info('Telegram webhook set successfully', ['response' => $response]);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to set Telegram webhook', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Get webhook info
     */
    public function getWebhookInfo(): array
    {
        try {
            if ($this->tdlib) {
                return $this->tdlib->getWebhookInfo();
            }
            $response = $this->telegram->getWebhookInfo();
            return $response->toArray();
        } catch (\Exception $e) {
            Log::error('Failed to get webhook info', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Remove webhook
     */
    public function removeWebhook(): bool
    {
        try {
            if ($this->tdlib) {
                $response = $this->tdlib->removeWebhook();
            } else {
                $response = $this->telegram->removeWebhook();
            }
            Log::info('Telegram webhook removed successfully', ['response' => $response]);
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to remove Telegram webhook', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Test bot functionality
     */
    public function testBot(): array
    {
        try {
            $me = $this->telegram->getMe();
            return [
                'bot_info' => $me->toArray(),
                'webhook_info' => $this->getWebhookInfo()
            ];
        } catch (\Exception $e) {
            Log::error('Failed to test bot', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Process incoming webhook update
     */
    public function processUpdate(array $update): void
    {
        try {
            $update = new Update($update);
            $this->applyLocale($update);

            if ($update->getMessage()) {
                $message = $update->getMessage();
                if ($message instanceof Message) {
                    $this->handleMessage($message);
                }
            }

            if ($update->getCallbackQuery()) {
                $this->handleCallbackQuery($update->getCallbackQuery());
            }
        } catch (\Exception $e) {
            Log::error('Error processing Telegram update', [
                'error' => $e->getMessage(),
                'update' => $update
            ]);
        }
    }

    /**
     * Resolve and apply locale for this update.
     */
    protected function applyLocale(Update $update): void
    {
        $chatId = null;
        $telegramLang = null;
        try {
            if ($msg = $update->getMessage()) {
                $chatId = $msg->getChat()->getId();
                $telegramLang = $msg->getFrom()->getLanguageCode();
            } elseif ($cb = $update->getCallbackQuery()) {
                $chatId = $cb->getMessage()->getChat()->getId();
                $telegramLang = $cb->getFrom()->getLanguageCode();
            }

            $entity = null;
            if ($chatId) {
                $entity = Customer::where('telegram_chat_id', $chatId)->first();
                if (!$entity) {
                    $entity = User::where('telegram_chat_id', $chatId)->first();
                }
            }

            // Resolve locale with precedence (configurable):
            // Default behavior: Always use Telegram device/app language first on every update.
            // Optional: honor a manual override if explicitly enabled in config.
            $preferDevice = (bool) (config('telegram.locale.prefer_device') ?? true);
            $honorManual = (bool) (config('telegram.locale.honor_manual_override') ?? false);

            $override = $chatId ? cache()->get("tg_locale_{$chatId}") : null;
            $override = is_string($override) ? LocaleService::normalize($override) : null;

            // Device language as reported by Telegram client
            $deviceLocale = $telegramLang ? LocaleService::normalize(strtolower($telegramLang)) : null;

            if ($preferDevice && $deviceLocale && LocaleService::isSupported($deviceLocale)) {
                $locale = $deviceLocale;
            } elseif ($honorManual && $override && LocaleService::isSupported($override)) {
                $locale = $override;
            } elseif (!$preferDevice && $override && LocaleService::isSupported($override)) {
                // Backward compatibility if device preference is disabled
                $locale = $override;
            } elseif ($entity && $entity->locale) {
                $locale = LocaleService::normalize($entity->locale);
            } else {
                $locale = config('locales.default', 'en');
            }

            // Persist the chosen locale on the entity if different/empty
            if ($entity && $entity->locale !== $locale) {
                try {
                    $entity->locale = $locale;
                    $entity->saveQuietly();
                } catch (\Throwable $e) {
                    Log::warning('Failed persisting detected locale', ['error' => $e->getMessage()]);
                }
            }

            if (!LocaleService::isSupported($locale)) {
                $locale = config('locales.default', 'en');
            }
            app()->setLocale($locale);
        } catch (\Throwable $e) {
            Log::warning('Locale resolution failure, falling back to default', ['error' => $e->getMessage()]);
            app()->setLocale(config('locales.default', 'en'));
        }
    }

    /**
     * Handle incoming messages
     */
    protected function handleMessage(Message $message): void
    {
        $chatId = $message->getChat()->getId();
        $text = $message->getText();
        $userId = $message->getFrom()->getId();

        // Inbound anti-spam: limit messages per chat (e.g., 30/min)
        if (class_exists(\Illuminate\Support\Facades\RateLimiter::class)) {
            $keyIn = 'tg_in:' . $chatId;
            if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($keyIn, 30)) {
                // After cooldown window, send a friendly notice once
                $coolKey = $keyIn . ':cool_notice';
                $last = cache()->get($coolKey);
                if (!$last || now()->diffInSeconds($last) > 60) {
                    cache()->put($coolKey, now(), 120);
                    $this->sendMessage($chatId, __('telegram.messages.rate_limited') ?: '⏳ You are sending messages too fast. Please slow down.');
                }
                return;
            }
            \Illuminate\Support\Facades\RateLimiter::hit($keyIn, 60);
        }

        // Map common labels/emojis to commands (even without reply keyboards)
        if ($text && !str_starts_with($text, '/')) {
            $normalized = trim(preg_replace('/\s+/', ' ', $text));
            // 1) Emoji-first mapping (language-agnostic)
            $emojiMap = [
                '✨' => '/menu',
                '🛒' => '/plans',
                '📦' => '/orders',
                '🧰' => '/myproxies',
                '💳' => '/balance',
                '🆘' => '/support',
                '👤' => '/profile',
                '🆕' => '/signup',
            ];
            foreach ($emojiMap as $emoji => $cmd) {
                if (str_starts_with($normalized, $emoji)) {
                    $text = $cmd;
                    break;
                }
            }
            // 2) Localized label mapping (current locale)
            if ($text && !str_starts_with($text, '/')) {
                $currentLocaleMap = [
                    '✨ ' . $this->trans('telegram.buttons.menu') => '/menu',
                    '🛒 ' . $this->trans('telegram.buttons.plans') => '/plans',
                    '📦 ' . $this->trans('telegram.buttons.orders') => '/orders',
                    '🧰 ' . $this->trans('telegram.buttons.my_services') => '/myproxies',
                    '💳 ' . $this->trans('telegram.buttons.wallet') => '/balance',
                    '🆘 ' . $this->trans('telegram.buttons.support') => '/support',
                    '👤 ' . $this->trans('telegram.buttons.profile') => '/profile',
                    '🆕 ' . $this->trans('telegram.buttons.sign_up') => '/signup',
                ];
                if (isset($currentLocaleMap[$normalized])) {
                    $text = $currentLocaleMap[$normalized];
                }
            }
            // 3) Hardcoded English labels as last resort
            if ($text && !str_starts_with($text, '/')) {
                $englishMap = [
                    '✨ Menu' => '/menu',
                    '🛒 Plans' => '/plans',
                    '📦 Orders' => '/orders',
                    '🧰 My Proxies' => '/myproxies',
                    '💳 Wallet' => '/balance',
                    '🆘 Support' => '/support',
                    '👤 Profile' => '/profile',
                    '🆕 Sign Up' => '/signup',
                ];
                if (isset($englishMap[$normalized])) {
                    $text = $englishMap[$normalized];
                }
            }
        }

        // Extract command and parameters
        $command = strtok($text, ' ');
        $params = trim(substr($text, strlen($command)));

        // Support compact commands like /config_123 or /reset_abc-uuid
    if (preg_match('/^\/(config|reset|status|buy|qrcode)_(.+)$/', $text, $m)) {
            $compactCmd = '/' . $m[1];
            $compactParam = $m[2];
            $command = $compactCmd;
            $params = $compactParam;
        }

        switch ($command) {
            case '/start':
                // Allow "/start xx" to set a language override quickly
                $p = trim($params);
                if ($p !== '' && preg_match('/^[A-Za-z]{2}([_-][A-Za-z]{2})?$/', $p)) {
                    $norm = LocaleService::normalize($p);
                    if (LocaleService::isSupported($norm)) {
                        cache()->put('tg_locale_' . $chatId, $norm, now()->addDays(7));
                        app()->setLocale($norm);
                    }
                }
                $this->handleStart($chatId, $userId, $params, $message);
                break;

            case '/menu':
                $this->handleMenu($chatId, $userId);
                break;

            case '/balance':
                $this->handleBalance($chatId, $userId);
                break;

            case '/myproxies':
                $this->handleMyProxies($chatId, $userId);
                break;

            case '/servers':
                // Backward compatible alias for plans listing (paginated)
                $this->handleServersPage($chatId, $userId, 1);
                break;

            case '/plans':
                $this->handleServersPage($chatId, $userId, 1);
                break;

            case '/orders':
                $this->handleOrders($chatId, $userId);
                break;

            case '/buy':
                $this->handleBuy($chatId, $userId, $params);
                break;

            case '/topup':
                $url = rtrim(config('app.url'), '/') . '/wallet/usd/top-up';
                $this->sendMessage($chatId, '🔗 ' . $url);
                $this->handleTopup($chatId, $userId);
                break;

            case '/config':
                $this->handleConfig($chatId, $userId, $params);
                break;

            case '/login':
                $this->handleLogin($chatId, $userId, $message);
                break;

            case '/qrcode':
                $this->handleQrCode($chatId, $userId, $params);
                break;

            case '/reset':
                $this->handleReset($chatId, $userId, $params);
                break;

            case '/status':
                $this->handleStatus($chatId, $userId, $params);
                break;

            case '/support':
                $this->handleSupport($chatId, $userId, $params);
                break;

            case '/help':
                $this->handleHelp($chatId);
                break;

            case '/lang':
            case '/language':
                $this->handleLanguage($chatId, $userId, $params);
                break;

            case '/link':
                $this->handleLink($chatId, $userId);
                break;

            case '/signup':
                $this->handleSignup($chatId, $userId);
                break;

            case '/profile':
                $this->handleProfile($chatId, $userId);
                break;

            // case '/more': deprecated

            // Admin Commands
            case '/admin':
                $this->handleAdminPanel($chatId, $userId);
                break;

            case '/users':
                $this->handleAdminUsers($chatId, $userId, $params);
                break;

            case '/serverhealth':
                $this->handleServerHealth($chatId, $userId);
                break;

            case '/stats':
                $this->handleSystemStats($chatId, $userId);
                break;

            case '/broadcast':
                $this->handleBroadcast($chatId, $userId, $params);
                break;

        default:
                // Check if the text is a linking code
                if (preg_match('/^[A-Za-z0-9]{8}$/', $text)) {
            $this->handleLinkingCode($chatId, $userId, $text, $message);
                } else {
                    // Check ongoing flow state (e.g., signup/profile updates)
                    if ($this->handleOngoingFlow($chatId, $userId, $text)) {
                        // handled
                    } else {
                        $this->sendMessage($chatId, $this->trans('telegram.messages.unknown', [], 'Unknown command. Type /help'));
                    }
                }
        }
    }

    /**
     * Show language selector and apply selection. Also supports direct param like "/lang fr".
     */
    protected function handleLanguage(int $chatId, int $userId, string $params): void
    {
        $param = trim($params);
        if ($param !== '') {
            $norm = LocaleService::normalize($param);
            if (LocaleService::isSupported($norm)) {
                cache()->put("tg_locale_{$chatId}", $norm, now()->addDays(30));
                app()->setLocale($norm);
                $this->sendMessage($chatId, '✅ ' . $this->trans('telegram.messages.language_set', ['lang' => strtoupper($norm)], 'Language set to: :lang'));
                $this->handleMenu($chatId, $userId);
                return;
            }
        }
        // Build inline keyboard with common languages
        $kb = Keyboard::make()->inline();
        $row = [];
        foreach (config('locales.supported', ['en']) as $lc) {
            $label = strtoupper($lc);
            $row[] = Keyboard::inlineButton(['text' => $label, 'callback_data' => 'set_lang_' . $lc]);
            if (count($row) >= 4) { $kb->row($row); $row = []; }
        }
        if (!empty($row)) { $kb->row($row); }
        $kb = $this->appendBackToMenu($kb, $chatId, $userId);
        $this->sendMessageWithKeyboard($chatId, '🌐 ' . $this->trans('telegram.messages.pick_language', [], 'Pick your language:'), $kb);
    }

    /**
     * Show extended More submenu
     */
    protected function handleMore(int $chatId, int $userId): void
    {
        $kb = Keyboard::make()->inline();
        $kb->row([
            Keyboard::inlineButton(['text' => '🛍 ' . $this->trans('telegram.buttons.plans', [], 'Plans'), 'callback_data' => 'open_plans']),
            Keyboard::inlineButton(['text' => '🧾 ' . $this->trans('telegram.buttons.orders', [], 'Orders'), 'callback_data' => 'open_orders'])
        ]);
        $kb->row([
            Keyboard::inlineButton(['text' => '🧪 ' . $this->trans('telegram.buttons.status', [], 'Status'), 'callback_data' => 'open_status']),
            Keyboard::inlineButton(['text' => '🛠 ' . $this->trans('telegram.buttons.filters', [], 'Filters'), 'callback_data' => 'server_filters'])
        ]);
        $kb = $this->appendBackToMenu($kb, $chatId, $userId);
        $this->sendMessageWithKeyboard($chatId, $this->trans('telegram.messages.more_menu', [], 'More options:'), $kb);
    }

    /**
     * Handle /start command
     */
    protected function handleStart(int $chatId, int $userId, string $params, ?Message $message = null): void
    {
    // Remove any old reply keyboards; we use inline keyboards only now
    $this->hideReplyKeyboard($chatId);

    // Resolve current identities
    $customer = Customer::where('telegram_chat_id', $chatId)->first();
    $staff = $this->getStaffUser($chatId, $userId);

        // If not linked, and we can identify a previously linked account by username, offer magic login
        if (!$customer && $message) {
            try {
                $username = $message->getFrom()->getUsername();
                if ($username) {
                    $maybe = Customer::where('telegram_username', $username)->first();
                    if ($maybe && !$maybe->telegram_chat_id) {
                        $url = \App\Http\Controllers\MagicLoginController::generateFor($maybe, 60 * 24);
                        $text = $this->templates->render('magic_login', 'telegram', [
                            'url' => $url,
                            'name' => $maybe->name ?? 'there',
                        ]);
                        if ($text === 'magic_login') {
                            $text = '🔑 Welcome back, :name!\n\nTap to sign in: :url';
                            $text = str_replace([':name', ':url'], [$maybe->name ?? 'there', $url], $text);
                        }
                        $kb = Keyboard::make()->inline()->row([
                            Keyboard::inlineButton(['text' => '🔑 ' . __('telegram.common.open'), 'url' => $url])
                        ]);
                        $this->sendMessageWithKeyboard($chatId, $text, $kb);
                    }
                }
            } catch (\Throwable $e) {
                // ignore
            }
        }

        // Tailored welcome + centralized menu
        if ($customer) {
            $text = $this->templates->render('welcome_customer', 'telegram', [
                'name' => $customer->name ?? 'there',
                'url' => config('app.url')
            ]);
            if ($text === 'welcome_customer') {
                $text = '👋 ' . $this->trans('telegram.menu.title', [], 'Welcome back!') . "\n\n" . $this->trans('telegram.admin.open_dashboard', ['url' => config('app.url') . '/account'], 'Dashboard: :url');
            }
            // Build main inline menu and attach it to the welcome visual/message directly
            $keyboard = $this->buildMainInlineMenu(true, (bool)$this->getStaffUser($chatId, $userId));
            $this->sendWelcomeVisualWithMenu($chatId, $text, $keyboard);
        } elseif ($staff) {
            $text = $this->templates->render('welcome_staff', 'telegram', [
                'name' => $staff->name ?? 'there',
                'url' => config('app.url') . '/admin'
            ]);
            if ($text === 'welcome_staff') {
                $text = '🛠 ' . $this->trans('telegram.admin.panel_title', [], 'Admin Panel') . "\n\n" . $this->trans('telegram.admin.open_dashboard', ['url' => config('app.url') . '/admin'], 'Dashboard: :url');
            }
            // Attach main inline menu directly
            $keyboard = $this->buildMainInlineMenu(false, true);
            $this->sendWelcomeVisualWithMenu($chatId, $text, $keyboard);
        } else {
            $text = $this->templates->render('welcome_guest', 'telegram', [
                'url' => config('app.url')
            ]);
            if ($text === 'welcome_guest') {
                $text = $this->trans('telegram.messages.start_welcome', ['url' => config('app.url')], 'Welcome to 1000proxy! Open: :url');
            }
            // Attach minimal guest menu directly
            $keyboard = $this->buildGuestInlineMenu();
            $this->sendWelcomeVisualWithMenu($chatId, $text, $keyboard);
        }
    }

    /**
     * Prefer sending a styled welcome image with caption if present.
     */
    protected function sendWelcomeVisual(int $chatId, string $caption, Keyboard $kb): void
    {
        // Try common paths under public/
        $candidates = [
            public_path('shield-proxy.png'),
        ];
        foreach ($candidates as $path) {
            if (is_readable($path)) {
                $this->sendPhoto($chatId, $path, $caption);
                // Send the keyboard after the photo as a separate message for better UX
                $this->sendMessageWithKeyboard($chatId, __('telegram.menu.title'), $kb);
                return;
            }
        }
        // Fallback to plain message with keyboard
        $this->sendMessageWithKeyboard($chatId, $caption, $kb);
    }

    /**
     * Variant of welcome visual that does NOT send any keyboard. Used by /start.
     */
    protected function sendWelcomeVisualNoMenu(int $chatId, string $caption): void
    {
        $candidates = [
            public_path('shield-proxy.png'),
        ];
        foreach ($candidates as $path) {
            if (is_readable($path)) {
                $this->sendPhoto($chatId, $path, $caption);
                return;
            }
        }
        $this->sendMessage($chatId, $caption);
    }

    /**
     * Send welcome visual with the main menu attached as an inline keyboard in the same message when possible.
     */
    protected function sendWelcomeVisualWithMenu(int $chatId, string $caption, $keyboard): void
    {
        $candidates = [
            public_path('shield-proxy.png'),
        ];
        foreach ($candidates as $path) {
            if (is_readable($path)) {
                $this->sendPhotoWithKeyboard($chatId, $path, $caption, $keyboard);
                return;
            }
        }
        // Fallback to text + keyboard if no image is available
        $this->sendMessageWithKeyboard($chatId, $caption, $keyboard);
    }

    /**
     * Handle /myproxies command
     */
    protected function handleMyProxies(int $chatId, int $userId, int $page = 1): void
    {
        $customer = $this->getAuthenticatedCustomer($chatId, $userId);
        if (!$customer) return;

        $perPage = 5;
        $offset = max(0, ($page - 1) * $perPage);
        $total = $customer->clients()->count();
        $totalPages = max(1, (int) ceil($total / $perPage));
        $clients = $customer->clients()->latest()->skip($offset)->take($perPage)->get();

        if ($clients->isEmpty()) {
            $this->sendMessageWithKeyboard($chatId, __('telegram.messages.no_services'), $this->backKeyboard());
            return;
        }
        // Precompute used
        $usedMap = [];
        foreach ($clients as $client) {
            $usedMap[$client->id] = $this->formatTraffic(($client->remote_up ?? 0) + ($client->remote_down ?? 0));
        }
        $text = $this->renderView('telegram.services', [
            'clients' => $clients,
            'usedMap' => $usedMap,
        ]);
        // Simple inline actions per context + pagination + back
        $kb = Keyboard::make()->inline();
        foreach ($clients as $client) {
            $cfgText = '🔗 ' . $this->trans('telegram.buttons.config', [], 'Config') . ' #' . substr((string)$client->id, 0, 6);
            $rstText = '🔄 ' . $this->trans('telegram.buttons.reset', [], 'Reset');
            $kb->row([
                Keyboard::inlineButton(['text' => $cfgText, 'callback_data' => 'config_open_' . $client->id])
            ]);
            $kb->row([
                Keyboard::inlineButton(['text' => $rstText, 'callback_data' => 'reset_open_' . $client->id])
            ]);
        }
        // Pagination rows (full-width)
        if ($page > 1) {
            $kb->row([Keyboard::inlineButton(['text' => '◀️ ' . __('telegram.common.prev'), 'callback_data' => 'myproxies_page_' . ($page - 1)])]);
        }
        $kb->row([Keyboard::inlineButton(['text' => '📄 ' . $page . '/' . $totalPages, 'callback_data' => 'noop'])]);
        if ($page < $totalPages) {
            $kb->row([Keyboard::inlineButton(['text' => __('telegram.common.next') . ' ▶️', 'callback_data' => 'myproxies_page_' . ($page + 1)])]);
        }
    $kb = $this->appendBackToMenu($kb, $chatId, $userId);
        $hint = $this->trans('telegram.admin.use_config_hint', [], 'Tap a proxy to open its configuration.');
    $dash = $this->trans('telegram.admin.open_dashboard', ['url' => config('app.url') . '/account'], 'Dashboard: :url');
    $this->sendMessageWithKeyboard($chatId, $text . "\n" . $hint . "\n" . $dash, $kb);
    }

    /**
     * Handle /topup command
     */
    protected function handleTopup(int $chatId, int $userId): void
    {
        $customer = $this->getAuthenticatedCustomer($chatId, $userId);
        if (!$customer) return;

        $wallet = $customer->wallet;
        $currentBalance = $wallet ? $wallet->balance : 0;
        $message = $this->renderView('telegram.topup', [
            'currentBalance' => $currentBalance,
        ]);

        // Create inline keyboard for quick top-up amounts via builder
            $kb = Keyboard::make()->inline()
            ->row([
                Keyboard::inlineButton(['text' => '$15', 'url' => config('app.url') . '/wallet/usd/top-up?amount=15']),
                Keyboard::inlineButton(['text' => '$30', 'url' => config('app.url') . '/wallet/usd/top-up?amount=30']),
                Keyboard::inlineButton(['text' => '$50', 'url' => config('app.url') . '/wallet/usd/top-up?amount=50'])
            ])
            ->row([
                Keyboard::inlineButton(['text' => '$100', 'url' => config('app.url') . '/wallet/usd/top-up?amount=100']),
                Keyboard::inlineButton(['text' => __('telegram.buttons.custom_amount'), 'url' => config('app.url') . '/wallet/usd/top-up'])
            ]);

    $kb = $this->appendBackToMenu($kb, $chatId, $userId);
        $this->sendMessageWithKeyboard($chatId, $message, $kb);
    }

    /**
     * Handle /config command
     */
    protected function handleConfig(int $chatId, int $userId, string $params): void
    {
        $customer = $this->getAuthenticatedCustomer($chatId, $userId);
        if (!$customer) return;

        if (empty($params)) {
            $this->sendMessageWithKeyboard($chatId, __('telegram.messages.config_need_id'), $this->backKeyboard());
            return;
        }

        $textId = trim($params);

        // Try as ServerClient UUID first
        $client = ServerClient::where('customer_id', $customer->id)->find($textId);
        $orderItem = null;

        if (!$client && ctype_digit($textId)) {
            // Fallback as OrderItem ID
            $orderItem = OrderItem::whereHas('order', fn($q) => $q->where('customer_id', $customer->id))
                ->where('id', (int) $textId)
                ->first();
            $client = $orderItem?->serverClients()->first();
        }

        if (!$client) {
            $this->sendMessageWithKeyboard($chatId, __('telegram.messages.config_not_found'), $this->backKeyboard());
            return;
        }

        $config = $client->getDownloadableConfig();
        $clientLink = $config['client_link'] ?? $client->client_link ?? null;
        $subscriptionLink = $config['subscription_link'] ?? $client->remote_sub_link ?? null;
        $jsonLink = $config['json_link'] ?? $client->remote_json_link ?? null;

        // Build caption with richer details
        $server = $client->inbound->server ?? null;
        $location = $server?->country ?? $server?->ip ?? '—';
        $protocol = strtolower($client->serverInbound?->protocol ?? $client->inbound?->protocol ?? 'vless');
        $status = $client->status ?? ($client->enable ? 'active' : 'inactive');
        $usedBytes = (int)($client->remote_up ?? 0) + (int)($client->remote_down ?? 0);
        $limitBytes = (int)($client->total_gb_bytes ?? 0);
        $usedText = $this->formatTraffic($usedBytes);
        $limitText = $limitBytes > 0 ? $this->formatTraffic($limitBytes) : '—';
    $expMs = (int) ($client->expiry_time ?? 0);
    $expires = $expMs > 0 ? \Carbon\Carbon::createFromTimestampMs($expMs)->format('M j, Y') : '—';
        $caption = "🔐 " . __('telegram.config.title') . "\n";
        $caption .= "📦 " . __('telegram.config.plan') . ": <b>" . ($client->plan->name ?? '—') . "</b>\n";
        $caption .= "🌐 " . __('telegram.config.server') . ": <b>{$location}</b>\n";
        $caption .= "🛠 Protocol: <b>" . strtoupper($protocol) . "</b>\n";
        $caption .= "📈 " . __('telegram.services.traffic') . ": <b>{$usedText}</b> / <b>{$limitText}</b>\n";
        $caption .= "📅 " . __('telegram.services.expires') . ": <b>{$expires}</b>\n";
        $caption .= "🆔 <code>" . $client->id . "</code>";

        // Prepare QR image (subscription preferred; fallback to client link)
        $qrRel = $client->qr_code_sub ?: null;
        $qrPath = null;
        if ($qrRel && Storage::disk('public')->exists($qrRel)) {
            $qrPath = Storage::disk('public')->path($qrRel);
        } else {
            $qrTarget = $subscriptionLink ?: $clientLink;
            if ($qrTarget) {
                try {
                    $png = QrCode::format('png')->size(512)->margin(1)->generate($qrTarget);
                    $hash = substr(md5($qrTarget), 0, 8);
                    $prefix = $subscriptionLink ? 'sub' : 'client';
                    $filename = "qrcodes/{$prefix}_" . $customer->id . '_' . $client->id . '_' . $hash . '.png';
                    Storage::disk('public')->put($filename, $png);
                    $qrPath = Storage::disk('public')->path($filename);
                    $qrRel = $filename;
                } catch (\Throwable $e) {
                    // fallback to text mode below
                }
            }
        }

        // Build inline buttons
        $kb = Keyboard::make()->inline();
        if (!empty($clientLink)) {
            $kb->row([Keyboard::inlineButton(['text' => '📱 ' . __('telegram.common.open'), 'url' => $clientLink])]);
        }
        if (!empty($subscriptionLink)) {
            $kb->row([Keyboard::inlineButton(['text' => '🔗 ' . __('telegram.config.subscription'), 'url' => $subscriptionLink])]);
        }
        if (!empty($jsonLink)) {
            $kb->row([Keyboard::inlineButton(['text' => '🧾 JSON', 'url' => $jsonLink])]);
        }
        if ($qrRel) {
            $kb->row([Keyboard::inlineButton(['text' => '⬇️ ' . __('telegram.buttons.download_qr'), 'url' => Storage::disk('public')->url($qrRel)])]);
        } else {
            // Offer on-demand QR via callback
            $kb->row([Keyboard::inlineButton(['text' => '🧿 QR Code', 'callback_data' => 'qrcode_' . $client->id])]);
        }
        $kb = $this->appendBackToMenu($kb, $chatId, $userId);

        if ($qrPath && is_readable($qrPath)) {
            $this->sendPhoto($chatId, $qrPath, $caption);
            // Send buttons under a short label
            $this->sendMessageWithKeyboard($chatId, __('telegram.common.actions'), $kb);
        } else {
            // Fallback to text view
            $text = $this->renderView('telegram.config', [
                'planName' => $client->plan->name ?? '—',
                'server' => $location,
                'clientLink' => $clientLink,
                'subscriptionLink' => $subscriptionLink,
                'jsonLink' => $jsonLink,
            ]);
            $this->sendMessageWithKeyboard($chatId, $text, $kb);
        }
    }

    /**
     * Handle /qrcode command or callback to show a QR code for the subscription link when available.
     */
    protected function handleQrCode(int $chatId, int $userId, string $params): void
    {
        $customer = $this->getAuthenticatedCustomer($chatId, $userId);
        if (!$customer) return;

        if (empty($params)) {
            $this->sendMessageWithKeyboard($chatId, __('telegram.messages.config_need_id'), $this->backKeyboard());
            return;
        }
        $client = ServerClient::where('customer_id', $customer->id)->find(trim($params));
        if (!$client) {
            $this->sendMessageWithKeyboard($chatId, __('telegram.messages.service_not_found'), $this->backKeyboard());
            return;
        }
        $config = $client->getDownloadableConfig();
        $sub = $config['subscription_link'] ?? $config['client_link'] ?? null;
        if (!$sub) {
            $this->sendMessageWithKeyboard($chatId, __('telegram.messages.config_not_found'), $this->backKeyboard());
            return;
        }
        // If QrCode is available, generate an image and send it inline; else link to dashboard QR
        try {
            if (class_exists(\SimpleSoftwareIO\QrCode\Facades\QrCode::class)) {
                $png = QrCode::format('png')->size(512)->margin(1)->generate($sub);
                $filename = 'qrcodes/sub_' . $customer->id . '_' . $client->id . '_' . substr(md5($sub), 0, 8) . '.png';
                Storage::disk('public')->put($filename, $png);
                $path = Storage::disk('public')->path($filename);
                $this->sendPhoto($chatId, $path, '🔗 ' . __('telegram.config.subscription'));
                // Follow-up with a back button
                $this->sendMessageWithKeyboard($chatId, __('telegram.common.actions'), $this->backKeyboard());
                return;
            }
        } catch (\Throwable $e) {
            \Log::warning('QR generation failed', ['error' => $e->getMessage()]);
        }
    $qrUrl = config('app.url') . '/account/my-active-servers';
    $this->sendMessageWithKeyboard($chatId, __('telegram.config.dashboard') . ' ' . $qrUrl, $this->backKeyboard());
    }

    /**
     * Handle /reset command
     */
    protected function handleReset(int $chatId, int $userId, string $params): void
    {
        $customer = $this->getAuthenticatedCustomer($chatId, $userId);
        if (!$customer) return;

        if (empty($params)) {
            $this->sendMessageWithKeyboard($chatId, __('telegram.messages.reset_need_id'), $this->backKeyboard());
            return;
        }

        $client = ServerClient::where('customer_id', $customer->id)->find(trim($params));
        if (!$client) {
            $this->sendMessageWithKeyboard($chatId, __('telegram.messages.client_not_found'), $this->backKeyboard());
            return;
        }

        // Send confirmation keyboard (builder)
        $keyboard = Keyboard::make()->inline()->row([
            Keyboard::inlineButton(['text' => '✅ ' . __('telegram.buttons.yes_reset'), 'callback_data' => "reset_confirm_{$client->id}"]),
            Keyboard::inlineButton(['text' => '❌ ' . __('telegram.buttons.cancel'), 'callback_data' => "reset_cancel_{$client->id}"])
        ]);

        $text = $this->renderView('telegram.reset_confirm', [
            'planName' => $client->plan->name ?? '—',
            'server' => $client->inbound->server->ip ?? '—',
        ]);

        $keyboard = $this->appendBackToMenu($keyboard, $chatId, $userId);
        $this->sendMessageWithKeyboard($chatId, $text, $keyboard);
    }

    /**
     * Handle /status command
     */
    protected function handleStatus(int $chatId, int $userId, string $params): void
    {
        $customer = $this->getAuthenticatedCustomer($chatId, $userId);
        if (!$customer) return;

        if (empty($params)) {
            $activeServices = $customer->clients()->count();
            $wallet = $customer->wallet;
            $balance = $wallet ? $wallet->balance : 0;
            $text = $this->renderView('telegram.status_account', [
                'name' => $customer->name,
                'balance' => $balance,
                'activeServices' => $activeServices,
                'memberSince' => $customer->created_at->format('M j, Y'),
            ]);

            $this->sendMessageWithKeyboard($chatId, $text, $this->backKeyboard());
            return;
        }

        // Show specific client status
        $client = ServerClient::where('customer_id', $customer->id)->find(trim($params));
        if (!$client) {
            $this->sendMessageWithKeyboard($chatId, __('telegram.messages.service_not_found'), $this->backKeyboard());
            return;
        }

        $text = $this->renderView('telegram.status_client', [
            'planName' => $client->plan->name ?? '—',
            'server' => $client->inbound->server->ip ?? '—',
            'connection' => (bool)($client->enable ?? false),
            'upload' => $this->formatTraffic((int)($client->remote_up ?? 0)),
            'download' => $this->formatTraffic((int)($client->remote_down ?? 0)),
            'total' => $this->formatTraffic((int)(($client->remote_up ?? 0) + ($client->remote_down ?? 0))),
            'resets' => (int)($client->reset ?? 0),
            'created' => ($client->created_at?->format('M j, Y H:i') ?? '—'),
            'clientId' => $client->id,
        ]);

    $this->sendMessageWithKeyboard($chatId, $text, $this->backKeyboard());
    }

    /**
     * Show the main inline menu with quick actions
     */
    protected function handleMenu(int $chatId, int $userId): void
    {
        // Ensure legacy reply keyboards are removed
        $this->hideReplyKeyboard($chatId);
        $customer = Customer::where('telegram_chat_id', $chatId)->first();
        $staff = $this->getStaffUser($chatId, $userId);

        if (!$customer && !$staff) {
            // Minimal guest menu
            $keyboard = $this->buildGuestInlineMenu();
            $text = '✨ ' . $this->trans('telegram.menu.title', [], 'Main Menu');
            $this->sendMessageWithKeyboard($chatId, $text, $keyboard);
            return;
        }

        // Unified main menu for linked users and/or staff
        $keyboard = $this->buildMainInlineMenu((bool)$customer, (bool)$staff);
        $prefix = $staff && !$customer ? '🛠 ' : '🔥 ';
        $titleKey = $staff && !$customer ? 'telegram.admin.panel_title' : 'telegram.menu.title';
        $text = $prefix . $this->trans($titleKey, [], $staff && !$customer ? 'Admin Panel' : 'Main Menu');
        $this->sendMessageWithKeyboard($chatId, $text, $keyboard);
    }

    protected function buildCustomerInlineMenu(): Keyboard
    {
        return $this->buildMainInlineMenu(true, false);
    }

    protected function buildStaffInlineMenu(): Keyboard
    {
        $kb = Keyboard::make()->inline();
        // One button per row (full-width)
        $kb->row([
            Keyboard::inlineButton(['text' => '👥 ' . __('telegram.buttons.users'), 'callback_data' => 'user_stats'])
        ]);
        $kb->row([
            Keyboard::inlineButton(['text' => '🌐 ' . __('telegram.buttons.servers'), 'callback_data' => 'server_health'])
        ]);
        $kb->row([
            Keyboard::inlineButton(['text' => '📊 ' . __('telegram.buttons.statistics'), 'callback_data' => 'system_stats'])
        ]);
        $kb->row([
            Keyboard::inlineButton(['text' => '📢 ' . __('telegram.buttons.broadcast'), 'callback_data' => 'admin_broadcast'])
        ]);
        $kb->row([
            Keyboard::inlineButton(['text' => '🔗 ' . __('telegram.common.open_dashboard'), 'url' => config('app.url') . '/admin'])
        ]);
        return $kb;
    }

    protected function buildGuestInlineMenu(): Keyboard
    {
        $kb = Keyboard::make()->inline();
        $kb->row([
            Keyboard::inlineButton(['text' => '🌐 ' . $this->trans('telegram.buttons.visit_website', [], 'Visit website'), 'url' => rtrim(config('app.url'), '/')])
        ]);
        // Quick language switcher
        $kb->row([
            Keyboard::inlineButton(['text' => '🌍 ' . $this->trans('telegram.buttons.language', [], 'Language'), 'callback_data' => 'open_language'])
        ]);
        $kb->row([
            Keyboard::inlineButton(['text' => '🛒 ' . __('telegram.common.browse_plans'), 'callback_data' => 'view_servers'])
        ]);
        $kb->row([
            Keyboard::inlineButton(['text' => '📚 ' . $this->trans('telegram.common.docs', [], 'Docs'), 'url' => rtrim(config('app.url'), '/') . '/docs'])
        ]);
        $kb->row([
            Keyboard::inlineButton(['text' => '🆕 ' . __('telegram.common.create_account'), 'callback_data' => 'signup_start'])
        ]);
        $kb->row([
            Keyboard::inlineButton(['text' => '🔗 ' . $this->trans('telegram.buttons.link_account', [], 'Link Account'), 'callback_data' => 'open_link'])
        ]);
        return $kb;
    }

    /**
     * Unified 4-option main inline menu: Buy, My Proxies, Support, Promotions (+ Admin for staff)
     */
    protected function buildMainInlineMenu(bool $isCustomer, bool $isStaff): Keyboard
    {
        $kb = Keyboard::make()->inline();

        // Determine safe, localized labels with fallbacks
        $buyText = '🛒 ' . $this->trans('telegram.buttons.plans');
        $myText = '🧰 ' . $this->trans('telegram.buttons.my_services');
        $supportText = '🆘 ' . $this->trans('telegram.buttons.support');
        $promoText = '🎁 ' . $this->trans('telegram.buttons.promotions');

        // If not a customer (guest), show a very small set via guest builder
        if (!$isCustomer && !$isStaff) {
            return $this->buildGuestInlineMenu();
        }

        // Full-width rows: one button per row
        $kb->row([
            Keyboard::inlineButton(['text' => $buyText, 'callback_data' => 'view_servers'])
        ]);
        $kb->row([
            Keyboard::inlineButton(['text' => $myText, 'callback_data' => 'view_myproxies'])
        ]);
        $kb->row([
            Keyboard::inlineButton(['text' => $supportText, 'callback_data' => 'open_support'])
        ]);
        $kb->row([
            Keyboard::inlineButton(['text' => $promoText, 'callback_data' => 'view_promotions'])
        ]);
        $kb->row([
            Keyboard::inlineButton(['text' => '💳 ' . $this->trans('telegram.buttons.wallet'), 'callback_data' => 'open_balance'])
        ]);
        $kb->row([
            Keyboard::inlineButton(['text' => '📦 ' . $this->trans('telegram.buttons.orders'), 'callback_data' => 'view_orders'])
        ]);

        // Row 4: Profile
        $kb->row([
            Keyboard::inlineButton(['text' => '👤 ' . $this->trans('telegram.buttons.profile'), 'callback_data' => 'open_profile'])
        ]);

        // Staff-only quick access to Admin Panel
        if ($isStaff) {
            $kb->row([
                Keyboard::inlineButton(['text' => '🛠 ' . $this->trans('telegram.admin.panel_title', [], 'Admin Panel'), 'callback_data' => 'admin_panel'])
            ]);
        }

        // Optional: dashboard link for everyone
        $kb->row([
            Keyboard::inlineButton(['text' => '🔗 ' . $this->trans('telegram.common.open_dashboard'), 'url' => config('app.url') . '/account'])
        ]);

        return $kb;
    }

    /**
     * Handle /link command: show clear, localized linking instructions.
     */
    protected function handleLink(int $chatId, int $userId): void
    {
    // If already linked (either Customer or User), nudge to /menu
    if (Customer::where('telegram_chat_id', $chatId)->exists() || User::where('telegram_chat_id', $chatId)->exists()) {
            $this->sendMessage($chatId, __('telegram.messages.already_linked'));
            return;
        }

        $dashboardUrl = rtrim(config('app.url'), '/') . '/account';
        $steps = $this->trans('telegram.messages.link_steps', ['url' => $dashboardUrl],
            "To link your account:\n\n1) Open: :url\n2) Sign in or create an account\n3) Go to Account Settings → Link Telegram\n4) Copy the 8‑character code and send it here\n\nPaste the code here anytime.");
        $intro = $this->trans('telegram.messages.link_intro', [], 'Link your Telegram to manage your account:');
        $help = $this->trans('telegram.messages.link_help', [], 'Need help? Type /help');
        $text = '🔗 ' . $intro . "\n\n" . $steps . "\n\n" . $help;

        $kb = Keyboard::make()->inline()->row([
            Keyboard::inlineButton(['text' => '🔗 ' . $this->trans('telegram.common.open_dashboard', [], 'Open Dashboard'), 'url' => $dashboardUrl])
        ]);
        $kb = $this->appendBackToMenu($kb, $chatId, $userId);
        $this->sendMessageWithKeyboard($chatId, $text, $kb);
    }

    /**
     * Handle /balance command
     */
    protected function handleBalance(int $chatId, int $userId): void
    {
        $customer = $this->getAuthenticatedCustomer($chatId, $userId);
        if (!$customer) return;

        $wallet = $customer->wallet;
        $balance = $wallet ? $wallet->balance : 0;
        $text = $this->templates->render('balance_summary', 'telegram', [
            'amount' => '$' . number_format((float)$balance, 2)
        ]);
        if ($text === 'balance_summary') {
            $text = $this->renderView('telegram.balance', [ 'balance' => $balance ]);
        }
        // Build inline keyboard: Top Up + Back
        $kb = Keyboard::make()->inline();
        $kb->row([
            Keyboard::inlineButton(['text' => '💳 ' . __('telegram.buttons.topup_wallet'), 'url' => config('app.url') . '/wallet/usd/top-up'])
        ]);
        // Append standard back keyboard row (if backKeyboard returns array structure, adapt accordingly)
        try {
            $backKb = $this->backKeyboard();
            // If backKeyboard returns Keyboard instance, merge rows
            if ($backKb instanceof Keyboard) {
                // Extract rows from backKb by reflection to avoid SDK private props (fallback: add a Back button)
                $kb->row([
                    Keyboard::inlineButton(['text' => '⬅️ ' . __('telegram.common.back'), 'callback_data' => 'back_menu'])
                ]);
            } elseif (is_array($backKb) && isset($backKb['inline_keyboard'])) {
                foreach ($backKb['inline_keyboard'] as $row) {
                    // Ensure row is an array of buttons
                    if (is_array($row)) {
                        $kb->row($row);
                    }
                }
            } else {
                $kb->row([
                    Keyboard::inlineButton(['text' => '⬅️ ' . __('telegram.common.back'), 'callback_data' => 'back_menu'])
                ]);
            }
        } catch (\Throwable $e) {
            // Fallback back button
            $kb->row([
                Keyboard::inlineButton(['text' => '⬅️ ' . __('telegram.common.back'), 'callback_data' => 'back_menu'])
            ]);
        }
        $this->sendMessageWithKeyboard($chatId, $text, $kb);
    }


    /**
     * Handle /orders command
     */
    protected function handleOrders(int $chatId, int $userId, int $page = 1): void
    {
        $customer = $this->getAuthenticatedCustomer($chatId, $userId);
        if (!$customer) return;

        $perPage = 5;
        $offset = max(0, ($page - 1) * $perPage);
        $total = $customer->orders()->count();
        $totalPages = max(1, (int) ceil($total / $perPage));
        $orders = $customer->orders()->latest()->skip($offset)->take($perPage)->get();

        if ($orders->isEmpty()) {
            $this->sendMessage($chatId, __('telegram.messages.no_orders'));
            return;
        }
        $text = $this->renderView('telegram.orders', [
            'orders' => $orders,
        ]);
    $kb = Keyboard::make()->inline();
        foreach ($orders as $order) {
            $icon = $this->getOrderStatusIcon($order->order_status ?? '');
            $amount = (float) ($order->grand_amount ?? $order->total_amount ?? 0);
            $kb->row([Keyboard::inlineButton([
                'text' => $icon . ' #' . $order->id . ' • $' . number_format($amount, 2),
                'url' => config('app.url') . '/account/order-management?order=' . $order->id,
            ])]);
        }
        // Pagination row
        if ($page > 1) {
            $kb->row([Keyboard::inlineButton(['text' => '◀️ ' . __('telegram.common.prev'), 'callback_data' => 'orders_page_' . ($page - 1)])]);
        }
        $kb->row([Keyboard::inlineButton(['text' => '📄 ' . $page . '/' . $totalPages, 'callback_data' => 'noop'])]);
        if ($page < $totalPages) {
            $kb->row([Keyboard::inlineButton(['text' => __('telegram.common.next') . ' ▶️', 'callback_data' => 'orders_page_' . ($page + 1)])]);
        }
        $kb->row([Keyboard::inlineButton([
            'text' => '🧾 ' . __('telegram.buttons.open_orders'),
            'url' => config('app.url') . '/account/order-management'
        ])]);
    $kb = $this->appendBackToMenu($kb, $chatId, $userId);
        $this->sendMessageWithKeyboard($chatId, $text, $kb);
    }

    /**
     * Handle /buy command
     */
    protected function handleBuy(int $chatId, int $userId, string $params): void
    {
        $customer = $this->getAuthenticatedCustomer($chatId, $userId);
        if (!$customer) return;

        // Extract plan ID from params (e.g., "buy_1" or "1")
        $planId = null;
        if (str_starts_with($params, 'buy_')) {
            $planId = (int) substr($params, 4);
        } elseif (ctype_digit($params)) {
            $planId = (int) $params;
        }

        if (!$planId) {
            $msg = $this->templates->render('buy_need_plan', 'telegram');
            if ($msg === 'buy_need_plan') { $msg = __('telegram.messages.buy_need_plan'); }
            $this->sendMessage($chatId, $msg);
            return;
        }

        $plan = ServerPlan::with('server')->find($planId);
        if (!$plan || !$plan->isAvailable()) {
            $msg = $this->templates->render('plan_unavailable', 'telegram');
            if ($msg === 'plan_unavailable') { $msg = __('telegram.messages.plan_unavailable'); }
            $this->sendMessage($chatId, $msg);
            return;
        }

        // Check wallet balance
        $wallet = $customer->wallet;
        $price = (float) $plan->getTotalPrice();
        if (!$wallet || $wallet->balance < $price) {
            $msg = $this->templates->render('insufficient_balance', 'telegram', ['url' => config('app.url') . '/wallet/usd/top-up']);
            if ($msg === 'insufficient_balance') { $msg = __('telegram.messages.insufficient_balance', ['url' => config('app.url') . '/wallet/usd/top-up']); }
            $this->sendMessage($chatId, $msg);
            return;
        }

        // Create order + item and mark as paid to trigger provisioning
        try {
            $paymentMethod = PaymentMethod::where('slug', 'wallet')->first();

            $order = Order::create([
                'customer_id' => $customer->id,
                'grand_amount' => $price,
                'currency' => 'usd',
                'payment_method' => $paymentMethod->id ?? null,
                'order_status' => 'new',
                'payment_status' => 'paid',
                'notes' => 'Telegram bot purchase',
            ]);

            $orderItem = OrderItem::create([
                'order_id' => $order->id,
                'server_plan_id' => $plan->id,
                'quantity' => 1,
                'unit_amount' => $plan->price,
                'total_amount' => $plan->price,
            ]);

            // Debit wallet with transaction
            $customer->payFromWallet($price, 'Telegram Order #' . $order->id);

            // Trigger provisioning via existing pipeline (OrderPaid event already fires on paid)
            // Optional: ensure provisioning kicks if listener expects dispatchWithDependencies
            try {
                ProcessXuiOrder::dispatch($order);
            } catch (\Throwable $t) {
                // Listener may handle it; ignore here
            }

            $message = "✅ Order Created Successfully!\n\n";
            $message .= "📋 Order ID: #{$order->id}\n";
            $message .= "📦 Plan: {$plan->name}\n";
            $message .= "🌐 Server: " . ($plan->server->country ?? $plan->server->ip ?? '—') . "\n";
            $message .= "💰 Amount: $" . number_format($price, 2) . "\n";
            $message .= "📊 Status: Processing\n\n";
            $message .= "⏳ Your configuration will be ready shortly. We'll notify you here when it's complete.";

            $this->sendMessage($chatId, $message);

        } catch (\Exception $e) {
            Log::error('Telegram bot order creation failed', [
                'customer_id' => $customer->id,
                'plan_id' => $plan->id,
                'error' => $e->getMessage()
            ]);

            $this->sendMessage($chatId, __('telegram.messages.order_failed'));
        }
    }

    /**
     * Handle /support command
     */
    protected function handleSupport(int $chatId, int $userId, string $params): void
    {
        $customer = $this->getAuthenticatedCustomer($chatId, $userId);
        if (!$customer) return;

        if (empty($params)) {
            $text = $this->renderView('telegram.support_options');
            $this->sendMessageWithKeyboard($chatId, $text, $this->backKeyboard());
            return;
        }

        // Create support ticket
        $ticket = [
            'customer_id' => $customer->id,
            'subject' => 'Telegram Support Request',
            'message' => $params,
            'source' => 'telegram',
            'telegram_chat_id' => $chatId
        ];

        // Here you would typically save to a support tickets table
        Log::info('Telegram support ticket created', $ticket);

        $text = $this->renderView('telegram.support_sent', [
            'customerName' => $customer->name,
            'messageText' => $params,
        ]);

    $this->sendMessageWithKeyboard($chatId, $text, $this->backKeyboard());
    }

    /**
     * Handle /help command
     */
    protected function handleHelp(int $chatId): void
    {
        // Prefer DB template for help content; fallback to our view
        $help = '';
        try {
            $help = $this->templates->render('help', 'telegram');
        } catch (\Throwable $e) {
            \Log::warning('Help template render failed, falling back to view', ['error' => $e->getMessage()]);
        }
        if ($help === 'help' || trim($help) === '') {
            $help = $this->renderView('telegram.help');
            if ($help === 'telegram.help' || trim($help) === '') {
                // Final hard fallback
                $help = '<b>/help</b> — Help unavailable right now. Try /menu.';
                \Log::error('Help fallback view was empty; sending minimal fallback');
            }
        }

        // Telegram max message length is 4096 chars (conservative cap)
        if (strlen($help) > 3900) {
            $help = substr($help, 0, 3900) . "\n…";
        }

        $this->sendMessageWithKeyboard($chatId, $help, $this->backKeyboard());
    }

    /**
     * Handle reset confirmation
     */
    protected function handleResetConfirm(int $chatId, int $userId, string $clientId): void
    {
        $customer = $this->getAuthenticatedCustomer($chatId, $userId);
        if (!$customer) return;

        $client = ServerClient::where('customer_id', $customer->id)->find($clientId);
        if (!$client) {
            $this->sendMessage($chatId, __('telegram.messages.client_not_found_or_reset'));
            return;
        }

        try {
            // Prefer local reset with sync; fallback to service
            $client->resetTraffic();
            $text = $this->renderView('telegram.reset_success', [
                'planName' => $client->plan->name ?? '—',
                'server' => $client->inbound->server->ip ?? '—',
                'clientId' => $client->id,
            ]);

            $this->sendMessage($chatId, $text);

        } catch (\Exception $e) {
            Log::error('Telegram bot client reset failed', [
                'client_id' => $clientId,
                'error' => $e->getMessage()
            ]);

            $this->sendMessage($chatId, "❌ Reset failed. Please try again or contact support.");
        }
    }

    /**
     * Handle servers pagination
     */
    protected function handleServersPage(int $chatId, int $userId, int $page): void
    {
        // Allow guests to browse; purchase will prompt signup if not linked
        $isLinked = Customer::where('telegram_chat_id', $chatId)->exists();

        $perPage = 5;
        $offset = ($page - 1) * $perPage;

        // Read current filters from cache for this chat
        $filters = $this->getPlanFilters($chatId);

        $baseQuery = ServerPlan::where('is_active', true)
            ->where('in_stock', true)
            ->where('on_sale', true);

        // Apply filters (country, category, days) if present
        if (!empty($filters['country'])) {
            $baseQuery->where('country_code', $filters['country']);
        }
        if (!empty($filters['category'])) {
            $baseQuery->where('server_category_id', (int) $filters['category']);
        }
        if (!empty($filters['days'])) {
            $baseQuery->where('days', (int) $filters['days']);
        }

        $plans = (clone $baseQuery)
            ->with('server')
            ->orderBy('popularity_score', 'desc')
            ->skip($offset)
            ->take($perPage)
            ->get();

    $totalPlans = (clone $baseQuery)->count();
        $totalPages = max(1, (int) ceil($totalPlans / $perPage));

        if ($plans->isEmpty()) {
            $kb = Keyboard::make()->inline();
            // Filters and clear (localized)
            $filterBtn = Keyboard::inlineButton(['text' => '🔎 ' . $this->trans('telegram.buttons.filters', [], 'Filters'), 'callback_data' => 'server_filters']);
            $clearBtn = Keyboard::inlineButton(['text' => '🧹 ' . $this->trans('telegram.buttons.clear_filters', [], 'Clear Filters'), 'callback_data' => 'server_filter_clear']);
            $kb->row([$filterBtn, $clearBtn]);
            $kb = $this->appendBackToMenu($kb, $chatId, $userId);
            $this->sendMessageWithKeyboard($chatId, $this->trans('telegram.messages.no_plans_page', ['page' => $page], 'No plans found on page :page.'), $kb);
            return;
        }

        // Render plans page
        $text = $this->renderView('telegram.plans', [
            'plans' => $plans,
            'page' => $page,
            'totalPages' => $totalPages,
        ]);

        // Append a compact filters summary when any filter is active
        $active = [];
        if (!empty($filters['country'])) { $active[] = '🌍 ' . strtoupper($filters['country']); }
        if (!empty($filters['category'])) { $active[] = '🗂 #' . (int)$filters['category']; }
        if (!empty($filters['days'])) { $active[] = '📅 ' . (int)$filters['days'] . 'd'; }
        if (!empty($active)) {
            $text .= "\n\n" . '🔎 ' . $this->trans('telegram.messages.active_filters', [], 'Active filters') . ': ' . implode(' • ', $active);
        }

        // Build keyboard (pagination + buy buttons)
        $kb = Keyboard::make()->inline();
        // Filters row first
        $filterBtn = Keyboard::inlineButton(['text' => '🔎 ' . $this->trans('telegram.buttons.filters', [], 'Filters'), 'callback_data' => 'server_filters']);
        $kb->row([$filterBtn]);
        if (!empty($active)) {
            $kb->row([Keyboard::inlineButton(['text' => '🧹 ' . $this->trans('telegram.buttons.clear_filters', [], 'Clear'), 'callback_data' => 'server_filter_clear'])]);
        }
        // Pagination as separate rows for full-width
        if ($page > 1) {
            $kb->row([Keyboard::inlineButton(['text' => '◀️ ' . __('telegram.common.prev'), 'callback_data' => 'server_page_' . ($page - 1)])]);
        }
        $kb->row([Keyboard::inlineButton(['text' => '📄 ' . $page . '/' . $totalPages, 'callback_data' => 'noop'])]);
        if ($page < $totalPages) {
            $kb->row([Keyboard::inlineButton(['text' => __('telegram.common.next') . ' ▶️', 'callback_data' => 'server_page_' . ($page + 1)])]);
        }
        foreach ($plans as $plan) {
            $kb->row([Keyboard::inlineButton([
                'text' => '🛒 ' . __('telegram.buttons.buy_plan', ['name' => $plan->name, 'price' => '$' . number_format((float)$plan->price, 2)]),
                'callback_data' => 'buy_plan_' . $plan->id,
            ])]);
        }
        if (!$isLinked) {
            $kb->row([Keyboard::inlineButton(['text' => '🆕 ' . __('telegram.common.create_account'), 'callback_data' => 'signup_start'])]);
        }

    $kb = $this->appendBackToMenu($kb, $chatId, $userId);
    $this->sendMessageWithKeyboard($chatId, $text, $kb);
    }

    /**
     * Handle buy confirmation
     */
    protected function handleBuyConfirm(int $chatId, int $userId, int $planId): void
    {
        $customer = $this->getAuthenticatedCustomer($chatId, $userId);
        if (!$customer) return;

        $plan = ServerPlan::with('server')->find($planId);
        if (!$plan || !$plan->isAvailable()) {
            $this->sendMessage($chatId, __('telegram.messages.plan_not_available'));
            return;
        }

        $wallet = $customer->wallet;
        if (!$wallet || $wallet->balance < $plan->price) {
            $message = __('telegram.messages.buy_insufficient') . "\n\n";
            $message .= "💰 " . __('telegram.messages.required_amount', ['amount' => '$' . number_format((float)$plan->price, 2)]) . "\n";
            $message .= "💳 " . __('telegram.messages.current_balance', ['amount' => '$' . number_format($wallet ? $wallet->balance : 0, 2)]) . "\n\n";
            $message .= __('telegram.messages.buy_topup_hint');

            $keyboard = [
                'inline_keyboard' => [
                    [ ['text' => '💳 ' . __('telegram.buttons.topup_wallet'), 'url' => config('app.url') . '/wallet/usd/top-up'] ],
                    [ ['text' => '🔄 ' . __('telegram.buttons.topped_up_refresh'), 'callback_data' => 'refresh_balance'] ],
                ]
            ];

            $this->sendMessageWithKeyboard($chatId, $message, $keyboard);
            return;
        }

        // Create confirmation keyboard (full-width buttons)
        $keyboard = Keyboard::make()->inline()
            ->row([
                Keyboard::inlineButton(['text' => '✅ ' . __('telegram.buttons.confirm_purchase'), 'callback_data' => "confirm_buy_{$planId}"])
            ])
            ->row([
                Keyboard::inlineButton(['text' => '❌ ' . __('telegram.buttons.cancel'), 'callback_data' => 'cancel_buy'])
            ]);

        $message = '🛒 ' . __('telegram.messages.buy_confirm_title') . "\n\n";
        $message .= '📦 ' . __('telegram.messages.buy_confirm_plan', ['plan' => $plan->name]) . "\n";
        $message .= '🌐 ' . __('telegram.messages.buy_confirm_server', ['server' => ($plan->server->country ?? $plan->server->ip ?? '—')]) . "\n";
        $message .= '💵 ' . __('telegram.messages.buy_confirm_price', ['price' => '$' . number_format((float)$plan->price, 2)]) . "\n";
        $message .= '💰 ' . __('telegram.messages.buy_confirm_balance', ['balance' => '$' . number_format($wallet->balance, 2)]) . "\n";
        $message .= '💳 ' . __('telegram.messages.buy_confirm_after', ['after' => '$' . number_format($wallet->balance - (float)$plan->price, 2)]) . "\n\n";
        $message .= __('telegram.messages.buy_confirm_proceed');

        $this->sendMessageWithKeyboard($chatId, $message, $keyboard);
    }

    /**
     * Handle /login command: send a magic login link to previously linked users.
     */
    protected function handleLogin(int $chatId, int $userId, ?Message $message = null): void
    {
    // Ensure legacy reply keyboards are removed
    $this->hideReplyKeyboard($chatId);
        // If already linked, generate a signin link anyway for convenience
        $customer = Customer::where('telegram_chat_id', $chatId)->first();
        if (!$customer && $message) {
            $username = $message->getFrom()->getUsername();
            if ($username) {
                $customer = Customer::where('telegram_username', $username)->first();
            }
        }

        if (!$customer) {
            $this->sendMessage($chatId, __('telegram.messages.user_not_found_simple'));
            return;
        }

        try {
            $url = \App\Http\Controllers\MagicLoginController::generateFor($customer, 60 * 24);
            $text = $this->templates->render('magic_login', 'telegram', [
                'url' => $url,
                'name' => $customer->name ?? 'there',
            ]);
            if ($text === 'magic_login') {
                $text = '🔑 Welcome back, :name!\n\nTap to sign in: :url';
                $text = str_replace([':name', ':url'], [$customer->name ?? 'there', $url], $text);
            }
            $kb = Keyboard::make()->inline()->row([
                Keyboard::inlineButton(['text' => '🔑 ' . __('telegram.common.open'), 'url' => $url])
            ]);
            $kb = $this->appendBackToMenu($kb, $chatId, $userId);
            $this->sendMessageWithKeyboard($chatId, $text, $kb);
        } catch (\Throwable $e) {
            $this->sendMessage($chatId, __('telegram.messages.order_failed'));
        }
    }

    /**
     * Handle admin panel
     */
    protected function handleAdminPanel(int $chatId, int $userId): void
    {
    $user = $this->getAuthenticatedUser($chatId, $userId);
    if (!$user || !$this->isAdmin($user)) {
        $this->sendMessage($chatId, __('telegram.messages.access_denied'));
            return;
        }

    $keyboard = Keyboard::make()->inline()
            ->row([
                Keyboard::inlineButton(['text' => '👥 ' . __('telegram.buttons.users'), 'callback_data' => 'user_stats'])
            ])
            ->row([
                Keyboard::inlineButton(['text' => '🌐 ' . __('telegram.buttons.servers'), 'callback_data' => 'server_health'])
            ])
            ->row([
                Keyboard::inlineButton(['text' => '📊 ' . __('telegram.buttons.statistics'), 'callback_data' => 'system_stats'])
            ])
            ->row([
                Keyboard::inlineButton(['text' => '📢 ' . __('telegram.buttons.broadcast'), 'callback_data' => 'admin_broadcast'])
            ])
            ->row([
                Keyboard::inlineButton(['text' => '🔄 ' . __('telegram.buttons.refresh'), 'callback_data' => 'admin_panel'])
            ]);

    $text = $this->renderView('telegram.admin.panel');

    $this->sendMessageWithKeyboard($chatId, $text, $keyboard);
    }

    /**
     * Handle admin users command
     */
    protected function handleAdminUsers(int $chatId, int $userId, string $params): void
    {
        $staff = $this->getStaffUser($chatId, $userId);
        if (!$staff || !$this->isAdmin($staff)) {
            $this->sendMessage($chatId, __('telegram.messages.access_denied'));
            return;
        }

        if (empty($params)) {
            // Show user statistics
            $totalUsers = Customer::count();
            $activeUsers = Customer::whereHas('clients')->count();
            $telegramUsers = Customer::whereNotNull('telegram_chat_id')->count();
            $recentUsers = Customer::where('created_at', '>=', now()->subDays(7))->count();

            $text = $this->renderView('telegram.admin.user_stats', [
                'totalUsers' => $totalUsers,
                'activeUsers' => $activeUsers,
                'telegramUsers' => $telegramUsers,
                'recentUsers' => $recentUsers,
            ]);

            $this->sendMessage($chatId, $text);
            return;
        }

        // Search for specific customer
        $searchUser = Customer::where('email', 'like', "%{$params}%")
            ->orWhere('name', 'like', "%{$params}%")
            ->first();

        if (!$searchUser) {
            $this->sendMessage($chatId, __('telegram.messages.user_not_found', ['q' => $params]));
            return;
        }
        $orders = $searchUser->orders()->count();
        $activeOrders = $searchUser->clients()->count();
        $wallet = $searchUser->wallet;
        $balance = $wallet ? $wallet->balance : 0;

        $text = $this->renderView('telegram.admin.user_details', [
            'email' => $searchUser->email,
            'name' => $searchUser->name,
            'balance' => $balance,
            'orders' => $orders,
            'activeOrders' => $activeOrders,
            'telegramLinked' => (bool)$searchUser->telegram_chat_id,
            'joined' => $searchUser->created_at->format('M j, Y'),
            'lastLogin' => ($searchUser->last_login_at ? $searchUser->last_login_at->format('M j, Y H:i') : 'Never'),
        ]);

        $this->sendMessage($chatId, $text);
    }

    /**
     * Handle server health
     */
    protected function handleServerHealth(int $chatId, int $userId, int $page = 1): void
    {
    $staff = $this->getStaffUser($chatId, $userId);
    if (!$staff || !$this->isAdmin($staff)) {
            $this->sendMessage($chatId, __('telegram.messages.access_denied'));
            return;
        }

        $perPage = 10;
        $offset = ($page - 1) * $perPage;
        $query = Server::with(['brand', 'category']);
        $totalServers = (clone $query)->count();
    $activeServers = (clone $query)->where('status', 'up')->count();
        $inactiveServers = (clone $query)->where('status', 'inactive')->count();
        $totalPages = max(1, (int) ceil($totalServers / $perPage));

        $servers = Server::with(['brand', 'category'])
            ->orderBy('location')
            ->skip($offset)
            ->take($perPage)
            ->get();

        $serversList = [];
        foreach ($servers as $server) {
            $serversList[] = [
                'statusIcon' => $server->status === 'active' ? '✅' : '❌',
                'location' => $server->location,
                'loadIcon' => ($server->load > 80 ? '🔴' : ($server->load > 60 ? '🟡' : '🟢')),
                'load' => (int)($server->load ?? 0),
                'price' => (float)($server->price ?? 0),
            ];
        }

        $text = $this->renderView('telegram.admin.server_health', [
            'totalServers' => $totalServers,
            'activeServers' => $activeServers,
            'inactiveServers' => $inactiveServers,
            'servers' => $serversList,
            'remaining' => max(0, $totalServers - ($page * $perPage)),
        ]);

        // Pagination keyboard
        $kb = Keyboard::make()->inline();
        if ($page > 1) {
            $kb->row([Keyboard::inlineButton(['text' => '◀️ ' . __('telegram.common.prev'), 'callback_data' => 'server_health_page_' . ($page - 1)])]);
        }
        $kb->row([Keyboard::inlineButton(['text' => '📄 ' . $page . '/' . $totalPages, 'callback_data' => 'noop'])]);
        if ($page < $totalPages) {
            $kb->row([Keyboard::inlineButton(['text' => __('telegram.common.next') . ' ▶️', 'callback_data' => 'server_health_page_' . ($page + 1)])]);
        }

        $this->sendMessageWithKeyboard($chatId, $text, $kb);
    }

    /**
     * Handle user stats (for callback)
     */
    protected function handleUserStats(int $chatId, int $userId): void
    {
        $this->handleAdminUsers($chatId, $userId, '');
    }

    /**
     * Handle system stats
     */
    protected function handleSystemStats(int $chatId, int $userId): void
    {
        $user = $this->getAuthenticatedUser($chatId, $userId);
        if (!$user || !$this->isAdmin($user)) {
            $this->sendMessage($chatId, __('telegram.messages.access_denied'));
            return;
        }

        // System statistics
        $totalUsers = Customer::count();
        $totalOrders = Order::count();
        $completedOrders = Order::where('order_status', 'completed')->count();
        $pendingOrders = Order::where('order_status', 'processing')->count();
        $totalRevenue = (float) (Order::where('payment_status', 'paid')->sum('grand_amount'));
        $todayRevenue = (float) (Order::where('payment_status', 'paid')
            ->whereDate('created_at', today())
            ->sum('grand_amount'));
        $todayOrders = Order::whereDate('created_at', today())->count();

        // Server statistics
        $totalServers = \App\Models\Server::count();
        $activeServers = \App\Models\Server::where('status', 'up')->count();
        $avgLoad = \App\Models\Server::where('status', 'up')->avg('load') ?? 0;

        $text = $this->renderView('telegram.admin.system_stats', [
            'totalUsers' => $totalUsers,
            'totalOrders' => $totalOrders,
            'completedOrders' => $completedOrders,
            'pendingOrders' => $pendingOrders,
            'totalRevenue' => $totalRevenue,
            'todayRevenue' => $todayRevenue,
            'todayOrders' => $todayOrders,
            'totalServers' => $totalServers,
            'activeServers' => $activeServers,
            'avgLoad' => $avgLoad,
            'updatedAt' => now()->format('H:i:s'),
        ]);

        $this->sendMessage($chatId, $text);
    }

    /**
     * Handle broadcast message
     */
    protected function handleBroadcast(int $chatId, int $userId, string $params): void
    {
        $staff = $this->getStaffUser($chatId, $userId);
        if (!$staff || !$this->isAdmin($staff)) {
            $this->sendMessage($chatId, __('telegram.messages.access_denied'));
            return;
        }

        if (empty($params)) {
            $text = $this->renderView('telegram.admin.broadcast_help');
            $this->sendMessage($chatId, $text);
            return;
        }

        // Get all users with Telegram linked, optional segmentation and opt-out
        $query = Customer::whereNotNull('telegram_chat_id');
        // Segment: active customers only if requested (prefix 'active: ')
        $onlyActive = false;
        if (str_starts_with($params, 'active:')) {
            $onlyActive = true;
            $params = trim(substr($params, strlen('active:')));
        }
        if ($onlyActive) {
            $query->whereHas('clients');
        }
        // Opt-out flag (expects boolean column telegram_opt_out)
        if (\Schema::hasColumn('customers', 'telegram_opt_out')) {
            $query->where(function($q){ $q->whereNull('telegram_opt_out')->orWhere('telegram_opt_out', false); });
        }
        $telegramUsers = $query->pluck('telegram_chat_id')->all();

        // Prefer DB template; fallback to legacy text
        $broadcastMessage = $this->templates->render('broadcast_generic', 'telegram', [
            'message' => $params,
        ]);
        if ($broadcastMessage === 'broadcast_generic') {
            $broadcastMessage = __('telegram.messages.broadcast_title') . "\n\n{$params}\n\n" . __('telegram.messages.broadcast_footer');
        }

    // Chunk dispatch to a queue for scalability
    $sentCount = 0;
    $failedCount = 0;
        $chunks = array_chunk($telegramUsers, 500);
        foreach ($chunks as $idx => $chunk) {
            try {
                \App\Jobs\TelegramBroadcastChunk::dispatch($chunk, $broadcastMessage)->onQueue('telegram');
                $sentCount += count($chunk); // optimistic counting; worker reports failures
            } catch (\Throwable $e) {
                $failedCount += count($chunk);
                Log::warning('Failed to dispatch broadcast chunk', [ 'error' => $e->getMessage(), 'chunk' => $idx ]);
            }
        }

        $text = $this->renderView('telegram.admin.broadcast_result', [
            'sent' => $sentCount,
            'failed' => $failedCount,
            'total' => count($telegramUsers),
        ]);

        $this->sendMessage($chatId, $text);
        // Also send a lightweight summary message back to admin via queue (does not wait for completion accuracy)
        try {
            \App\Jobs\TelegramBroadcastSummary::dispatch($chatId, count($telegramUsers), $sentCount, $failedCount)->onQueue('telegram');
        } catch (\Throwable $e) { /* ignore */ }

        Log::info('Admin broadcast sent', [
            'admin_user_id' => $staff->id,
            'message' => $params,
            'sent_count' => $sentCount,
            'failed_count' => $failedCount
        ]);
    }

    /**
     * Check if user is admin
     */
    protected function isAdmin(User $user): bool
    {
        return $user->is_admin || $user->hasRole('admin') || in_array($user->email, config('app.admin_emails', []));
    }

    /**
     * Get authenticated customer (end-user)
     */
    protected function getAuthenticatedCustomer(int $chatId, int $userId): ?Customer
    {
        $customer = Customer::where('telegram_chat_id', $chatId)->first();

        if (!$customer) {
            $this->sendMessage($chatId, __('telegram.admin.link_account_first', ['url' => config('app.url')]));
            return null;
        }

        return $customer;
    }

    /**
     * Get authenticated staff user (for admin commands)
     */
    protected function getStaffUser(int $chatId, int $userId): ?User
    {
        return User::where('telegram_chat_id', $chatId)->first();
    }

    /**
     * Handle linking code
     */
    protected function handleLinkingCode(int $chatId, int $userId, string $code, Message $message): void
    {
        // Check if the code exists in cache
        $cacheKey = "telegram_linking_{$code}";
        $payload = cache()->get($cacheKey);

        if (!$payload) {
            $this->sendMessage($chatId, __('telegram.admin.code_invalid'));
            return;
        }
        $telegramUser = $message->getFrom();

        // Support both payload shapes: scalar customer ID, or ['type' => 'user'|'customer', 'id' => N]
        $type = 'customer';
        $targetId = null;
        if (is_array($payload)) {
            $type = ($payload['type'] ?? 'customer') === 'user' ? 'user' : 'customer';
            $targetId = (int) ($payload['id'] ?? 0);
        } else {
            $targetId = (int) $payload;
        }

        if ($type === 'user') {
            // Link staff User account
            $user = User::find($targetId);
            if (!$user) {
                $this->sendMessage($chatId, __('telegram.admin.user_not_found_simple'));
                return;
            }
            // Ensure Telegram chat not linked to another User
            $existingUser = User::where('telegram_chat_id', $chatId)->first();
            if ($existingUser && $existingUser->id !== $user->id) {
                $this->sendMessage($chatId, __('telegram.admin.telegram_already_linked'));
                return;
            }
            $user->telegram_chat_id = $chatId;
            // Best effort store username if attribute exists
            try { $user->telegram_username = $telegramUser->getUsername(); } catch (\Throwable $e) {}
            $user->save();

            cache()->forget($cacheKey);
            $this->sendMessage($chatId, __('telegram.admin.linking_success', ['name' => $user->name ?? '']))
            ;
            $this->handleMenu($chatId, $userId);
            Log::info('Telegram account linked (user)', [
                'user_id' => $user->id,
                'telegram_chat_id' => $chatId,
                'telegram_username' => $telegramUser->getUsername()
            ]);
            return;
        }

        // Default: link Customer account
        $customer = Customer::find($targetId);
        if (!$customer) {
            $this->sendMessage($chatId, __('telegram.admin.user_not_found_simple'));
            return;
        }
        // Check if this Telegram account is already linked to another customer
        $existingCustomer = Customer::where('telegram_chat_id', $chatId)->first();
        if ($existingCustomer && $existingCustomer->id !== $customer->id) {
            $this->sendMessage($chatId, __('telegram.admin.telegram_already_linked'));
            return;
        }
        $customer->linkTelegram(
            $chatId,
            $telegramUser->getUsername(),
            $telegramUser->getFirstName(),
            $telegramUser->getLastName()
        );

        cache()->forget($cacheKey);
        $this->sendMessage($chatId, __('telegram.admin.linking_success', ['name' => $customer->name]));
        $this->handleMenu($chatId, $userId);
        Log::info('Telegram account linked (customer)', [
            'customer_id' => $customer->id,
            'telegram_chat_id' => $chatId,
            'telegram_username' => $telegramUser->getUsername()
        ]);
    }

    /**
     * Send message to Telegram chat
     */
    protected function sendMessage(int $chatId, string $text): void
    {
        try {
            // Delete previous bot messages if ephemeral cleanup is enabled
            $this->cleanupPreviousBotMessages($chatId);
            // Outgoing rate limit per chat: 20 msgs/min
            if (class_exists(\Illuminate\Support\Facades\RateLimiter::class)) {
                $key = 'tg_out:' . $chatId;
                if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($key, 20)) {
                    // Skip sending to avoid hitting Telegram limits
                    Log::warning('Telegram outgoing rate limit hit', ['chat_id' => $chatId]);
                    return;
                }
                \Illuminate\Support\Facades\RateLimiter::hit($key, 60);
            }
            if ($this->tdlib) {
                $resp = $this->tdlib->sendMessage([
                    'chat_id' => $chatId,
                    'text' => $text,
                    'parse_mode' => 'HTML'
                ]);
                $mid = is_array($resp) ? ($resp['result']['message_id'] ?? null) : null;
                if ($mid) { $this->rememberBotMessageId($chatId, (int) $mid); }
            } else {
                $resp = $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => $text,
                    'parse_mode' => 'HTML'
                ]);
                $mid = method_exists($resp, 'getMessageId') ? $resp->getMessageId() : ($resp['result']['message_id'] ?? null);
                if ($mid) { $this->rememberBotMessageId($chatId, (int) $mid); }
            }
        } catch (\Exception $e) {
            Log::error('Failed to send Telegram message', [
                'chat_id' => $chatId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Public method to send direct message (for controller access)
     */
    public function sendDirectMessage(int $chatId, string $text): void
    {
        $this->sendMessage($chatId, $text);
    }

    /**
     * Send message with inline keyboard
     */
    protected function sendMessageWithKeyboard(int $chatId, string $text, $keyboard): void
    {
        try {
            // Delete previous bot messages if ephemeral cleanup is enabled
            $this->cleanupPreviousBotMessages($chatId);
            // Allow Keyboard builder instance or plain array
            $replyMarkup = $keyboard;
            if (is_array($keyboard)) {
                $replyMarkup = json_encode($keyboard);
            }
            if ($this->tdlib) {
                $reply = $replyMarkup instanceof \Telegram\Bot\Keyboard\Keyboard ? $replyMarkup->toArray() : $replyMarkup;
                $resp = $this->tdlib->sendMessage([
                    'chat_id' => $chatId,
                    'text' => $text,
                    'parse_mode' => 'HTML',
                    'reply_markup' => $reply,
                ]);
                $mid = is_array($resp) ? ($resp['result']['message_id'] ?? null) : null;
                if ($mid) { $this->rememberBotMessageId($chatId, (int) $mid); }
            } else {
                $resp = $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => $text,
                    'parse_mode' => 'HTML',
                    'reply_markup' => $replyMarkup
                ]);
                $mid = method_exists($resp, 'getMessageId') ? $resp->getMessageId() : ($resp['result']['message_id'] ?? null);
                if ($mid) { $this->rememberBotMessageId($chatId, (int) $mid); }
            }
        } catch (\Exception $e) {
            Log::error('Failed to send Telegram message with keyboard', [
                'chat_id' => $chatId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Show a quick loading preview by editing in-place if possible.
     */
    protected function previewLoading(int $chatId, int $messageId): void
    {
        try {
            $this->telegram->editMessageText([
                'chat_id' => $chatId,
                'message_id' => $messageId,
                'text' => '⏳ ' . $this->trans('telegram.common.loading', [], 'Loading...'),
                'parse_mode' => 'HTML'
            ]);
        } catch (\Throwable $e) {
            // Fallback: send a separate loading message (Ignored rate limit concerns for brevity)
            $this->sendMessage($chatId, '⏳ ' . $this->trans('telegram.common.loading', [], 'Loading...'));
        }
    }

    /**
     * Attempt to edit existing message (navigation) else send new.
     */
    protected function editOrSend(int $chatId, int $messageId, string $text, $keyboard): void
    {
        try {
            $replyMarkup = $keyboard;
            if ($keyboard instanceof Keyboard) {
                $replyMarkup = $keyboard;
            } elseif (is_array($keyboard)) {
                $replyMarkup = json_encode($keyboard);
            }
            if ($this->tdlib) {
                $reply = $replyMarkup instanceof \Telegram\Bot\Keyboard\Keyboard ? $replyMarkup->toArray() : $replyMarkup;
                $this->tdlib->editMessageText([
                    'chat_id' => $chatId,
                    'message_id' => $messageId,
                    'text' => $text,
                    'parse_mode' => 'HTML',
                    'reply_markup' => $reply,
                ]);
            } else {
                $this->telegram->editMessageText([
                    'chat_id' => $chatId,
                    'message_id' => $messageId,
                    'text' => $text,
                    'parse_mode' => 'HTML',
                    'reply_markup' => $replyMarkup,
                ]);
            }
        } catch (\Throwable $e) {
            $this->sendMessageWithKeyboard($chatId, $text, $keyboard);
        }
    }

    /**
     * Send a local photo file to a Telegram chat with optional caption.
     */
    protected function sendPhoto(int $chatId, string $filePath, ?string $caption = null): void
    {
        try {
            // Delete previous bot messages if ephemeral cleanup is enabled
            $this->cleanupPreviousBotMessages($chatId);
            if (!is_readable($filePath)) {
                throw new \RuntimeException('Photo not readable: ' . $filePath);
            }
            if ($this->tdlib) {
                $resp = $this->tdlib->sendPhoto([
                    'chat_id' => $chatId,
                    'photo' => $filePath,
                    'caption' => $caption,
                    'parse_mode' => 'HTML',
                ]);
            } else {
                $resp = $this->telegram->sendPhoto([
                    'chat_id' => $chatId,
                    'photo' => fopen($filePath, 'rb'),
                    'caption' => $caption,
                    'parse_mode' => 'HTML',
                ]);
            }
            $mid = is_array($resp) ? ($resp['result']['message_id'] ?? null) : (method_exists($resp ?? null, 'getMessageId') ? $resp->getMessageId() : null);
            if ($mid) { $this->rememberBotMessageId($chatId, (int) $mid); }
        } catch (\Throwable $e) {
            Log::error('Failed to send photo', ['chat_id' => $chatId, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Send a local photo with an inline keyboard attached (for welcome + menu in one message).
     */
    protected function sendPhotoWithKeyboard(int $chatId, string $filePath, ?string $caption, $keyboard): void
    {
        try {
            // Delete previous bot messages if ephemeral cleanup is enabled
            $this->cleanupPreviousBotMessages($chatId);
            if (!is_readable($filePath)) {
                throw new \RuntimeException('Photo not readable: ' . $filePath);
            }
            $replyMarkup = $keyboard;
            if (is_array($keyboard)) {
                $replyMarkup = json_encode($keyboard);
            }
            if ($this->tdlib) {
                $reply = $replyMarkup instanceof \Telegram\Bot\Keyboard\Keyboard ? $replyMarkup->toArray() : $replyMarkup;
                $resp = $this->tdlib->sendPhoto([
                    'chat_id' => $chatId,
                    'photo' => $filePath,
                    'caption' => $caption,
                    'parse_mode' => 'HTML',
                    'reply_markup' => $reply,
                ]);
            } else {
                $resp = $this->telegram->sendPhoto([
                    'chat_id' => $chatId,
                    'photo' => fopen($filePath, 'rb'),
                    'caption' => $caption,
                    'parse_mode' => 'HTML',
                    'reply_markup' => $replyMarkup,
                ]);
            }
            $mid = is_array($resp) ? ($resp['result']['message_id'] ?? null) : (method_exists($resp ?? null, 'getMessageId') ? $resp->getMessageId() : null);
            if ($mid) { $this->rememberBotMessageId($chatId, (int) $mid); }
        } catch (\Throwable $e) {
            Log::error('Failed to send photo with keyboard', ['chat_id' => $chatId, 'error' => $e->getMessage()]);
            // Fallback: send without keyboard, then send keyboard message separately
            $this->sendPhoto($chatId, $filePath, $caption);
            $this->sendMessageWithKeyboard($chatId, __('telegram.menu.title'), $keyboard);
        }
    }

    /**
     * Render a Blade view into a Telegram-safe HTML message.
     */
    protected function renderView(string $view, array $data = []): string
    {
        try {
            $html = trim(view($view, $data)->render());
            // Normalize to Telegram-safe HTML: allow only simple inline tags and line breaks
            $html = $this->sanitizeTelegramHtml($html);
            return $html;
        } catch (\Throwable $e) {
            Log::error('Telegram view render failed', ['view' => $view, 'error' => $e->getMessage()]);
            return '⚠️ Failed to render message.';
        }
    }

    /**
     * Convert Blade HTML to Telegram-safe subset (no div/span/p tags).
     * Allowed: <b><strong><i><em><u><s><code><pre><a><br>
     */
    protected function sanitizeTelegramHtml(string $html): string
    {
        // Convert common block elements to newlines to preserve readability
        $replacements = [
            '/\r\n|\r/' => "\n",
            '/<\s*br\s*\/?>/i' => "\n",
            '/<\s*\/(div|p)\s*>/i' => "\n\n",
            '/<\s*(div|p)[^>]*>/i' => '',
            '/<\s*\/?span[^>]*>/i' => '',
        ];
        foreach ($replacements as $pattern => $rep) {
            $html = preg_replace($pattern, $rep, $html);
        }

        // Strip all tags except Telegram-allowed subset
        $html = strip_tags($html, '<b><strong><i><em><u><s><code><pre><a><br>');

        // Replace any leaked translation keys (e.g., telegram.xxx) with safe fallbacks
        $html = $this->replaceTranslationKeys($html);

        // Collapse excessive blank lines and spaces
        $html = preg_replace("/\n{3,}/", "\n\n", $html);
        $html = trim($html);

        return $html;
    }

    /**
     * Replace translation-like tokens (telegram.*) with localized fallback text.
     */
    protected function replaceTranslationKeys(string $text): string
    {
        return preg_replace_callback('/(?<![A-Za-z0-9_\-])telegram\.[A-Za-z0-9_\.\-]+/', function($m) {
            $key = $m[0];
            return $this->trans($key);
        }, $text);
    }

    /**
     * Safe translation with fallback to default locale (en) and a humanized last segment.
     */
    protected function trans(string $key, array $replace = [], ?string $fallback = null): string
    {
        try {
            $value = (string) \Illuminate\Support\Facades\Lang::get($key, $replace);
            if ($value !== $key && $value !== '') {
                return $value;
            }
            // Fallback to English
            $en = (string) \Illuminate\Support\Facades\Lang::get($key, $replace, 'en');
            if ($en !== $key && $en !== '') {
                return $en;
            }
        } catch (\Throwable $e) {
            // ignore and use fallback below
        }
        if ($fallback !== null && $fallback !== '') {
            return $fallback;
        }
        // Humanize last segment of the key as a last resort
        $last = trim(strrchr($key, '.') ?: $key, '.');
        $human = ucwords(str_replace(['_', '-'], ' ', $last));
        return $human ?: $key;
    }

    /**
     * Send a persistent reply keyboard with common commands so it's always visible.
     */
    protected function sendPersistentCommandMenu(int $chatId): void
    {
        // No-op: legacy reply keyboard removed. Ensure any old keyboard is hidden.
        $this->hideReplyKeyboard($chatId);
    }

    /**
     * Remove any existing reply keyboard (legacy); sends an unobtrusive message.
     */
    protected function hideReplyKeyboard(int $chatId): void
    {
        try {
            // Send a temporary, invisible message to remove keyboard, then delete it
            $resp = $this->telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => "\u{200B}", // zero-width space
                'reply_markup' => json_encode(['remove_keyboard' => true]),
                'disable_notification' => true,
            ]);
            if (method_exists($resp, 'getMessageId')) {
                $this->telegram->deleteMessage([
                    'chat_id' => $chatId,
                    'message_id' => $resp->getMessageId(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::debug('Failed to remove reply keyboard', ['chat_id' => $chatId, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Append a unified Back to Menu row to an inline keyboard.
     */
    protected function appendBackToMenu(Keyboard $kb, int $chatId, int $userId): Keyboard
    {
        $label = __('telegram.common.back');
        if (Lang::has('telegram.buttons.back_to_menu')) {
            $label = __('telegram.buttons.back_to_menu');
        }
        $kb->row([
            Keyboard::inlineButton(['text' => '⬅️ ' . $label, 'callback_data' => 'open_menu'])
        ]);
        return $kb;
    }

    /**
     * Format traffic in human readable format
     */
    protected function formatTraffic(int $bytes): string
    {
        if ($bytes === 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $base = 1024;
        $exponent = floor(log($bytes, $base));
        $value = round($bytes / pow($base, $exponent), 2);

        return $value . ' ' . $units[$exponent];
    }

    /**
     * Get order status icon
     */
    protected function getOrderStatusIcon(string $status): string
    {
        return match($status) {
            'pending' => '⏳',
            'processing' => '🔄',
            'completed' => '✅',
            'failed' => '❌',
            'cancelled' => '⏹️',
            default => '📋'
        };
    }

    /**
     * Convert ISO country code to flag + short name label.
     * Uses a minimal inline map; falls back to code.
     */
    protected function countryLabel(string $iso2): string
    {
        $iso2 = strtoupper($iso2);
        // Normalise legacy / incorrect codes (e.g. UK -> GB)
        if ($iso2 === 'UK') { $iso2 = 'GB'; }
        // Flag from regional indicator symbols (only for true 2-letter A-Z)
        $flag = ctype_alpha($iso2) && strlen($iso2) === 2 ? $this->countryFlagEmoji($iso2) : '';
        // Common set; keep short to avoid unnecessary memory; extend as needed
        static $names = [
            'US' => 'United States','GB' => 'United Kingdom','DE' => 'Germany','FR' => 'France','JP' => 'Japan','CA' => 'Canada','AU' => 'Australia','NL' => 'Netherlands','SG' => 'Singapore','SE' => 'Sweden','NO' => 'Norway','FI' => 'Finland','DK' => 'Denmark','IT' => 'Italy','ES' => 'Spain','PT' => 'Portugal','BR' => 'Brazil','PL' => 'Poland','UA' => 'Ukraine','RU' => 'Russia','CN' => 'China','IN' => 'India','TR' => 'Turkey','AE' => 'UAE','SA' => 'Saudi Arabia','IR' => 'Iran','IQ' => 'Iraq','HK' => 'Hong Kong','TW' => 'Taiwan'
        ];
        $name = $names[$iso2] ?? $iso2;
        return trim(($flag ? $flag . ' ' : '') . $name);
    }

    protected function countryFlagEmoji(string $iso2): string
    {
        if (strlen($iso2) !== 2) return '';
        $iso2 = strtoupper($iso2);
        $codePoints = [ord($iso2[0]) - 65 + 0x1F1E6, ord($iso2[1]) - 65 + 0x1F1E6];
        return mb_convert_encoding('&#' . $codePoints[0] . ';', 'UTF-8', 'HTML-ENTITIES')
             . mb_convert_encoding('&#' . $codePoints[1] . ';', 'UTF-8', 'HTML-ENTITIES');
    }

    /**
     * Send order completion notification
     */
    public function sendOrderNotification(Order $order): void
    {
        $customer = $order->customer;
        if (!$customer || !$customer->telegram_chat_id) {
            return;
        }

        // Resolve a user-friendly server label from the first order item
        $firstItem = $order->items()->with('serverPlan.server')->first();
        $server = $firstItem?->serverPlan?->server;
        $serverLabel = $server->location
            ?? $server->country
            ?? $server->ip
            ?? '—';

        $status = $order->order_status ?? $order->status ?? '-';

        // Prefer DB template for order_ready
        $message = $this->templates->render('order_ready', 'telegram', [
            'id' => $order->id,
            'server' => $serverLabel,
            'status' => ucfirst((string)$status),
            'url' => config('app.url') . "/account/order-management?order={$order->id}",
        ]);
        if ($message === 'order_ready') {
            $message = __('telegram.messages.order_ready_title') . "\n\n";
            $message .= __('telegram.messages.order_id_line', ['id' => $order->id]) . "\n";
            $message .= __('telegram.messages.order_server_line', ['server' => $serverLabel]) . "\n";
            $message .= __('telegram.messages.order_status_line', ['status' => ucfirst((string)$status)]) . "\n\n";

            if ($status === 'completed' || $order->isFullyProvisioned()) {
                $message .= __('telegram.messages.order_completed_block', [
                    'url' => config('app.url') . "/account/order-management?order={$order->id}"
                ]);
            } else {
                $message .= __('telegram.messages.order_issue');
            }
        }

        $this->sendMessage($customer->telegram_chat_id, $message);
    }

    /**
     * Handle callback queries from inline keyboards
     */
    protected function handleCallbackQuery($callbackQuery): void
    {
        $chatId = $callbackQuery->getMessage()->getChat()->getId();
        $data = $callbackQuery->getData();
        $userId = $callbackQuery->getFrom()->getId();
    $messageId = $callbackQuery->getMessage()->getMessageId();
        // Handle different callback actions
        if (str_starts_with($data, 'reset_confirm_')) {
            $clientId = substr($data, 14);
            $this->handleResetConfirm($chatId, $userId, $clientId);
        } elseif (str_starts_with($data, 'reset_cancel_')) {
            $cid = substr($data, 13);
            $this->sendMessage($chatId, __('telegram.messages.reset_cancelled_client', ['cid' => $cid]));
        } elseif (str_starts_with($data, 'qrcode_')) {
            $cid = substr($data, 7);
            $this->handleQrCode($chatId, $userId, $cid);
        } elseif ($data === 'server_filters') {
            $this->previewLoading($chatId, $messageId);
            $this->showFilterMenu($chatId, $userId);
        } elseif (str_starts_with($data, 'server_filter_country_')) {
            $cc = strtoupper(substr($data, strlen('server_filter_country_')));
            if ($cc === 'ANY' || $cc === 'ALL' || $cc === 'CLEAR' || $cc === 'NONE') {
                $this->setPlanFilter($chatId, 'country', null);
            } else {
                $this->setPlanFilter($chatId, 'country', $cc);
            }
            $this->previewLoading($chatId, $messageId);
            $this->handleServersPage($chatId, $userId, 1);
        } elseif (str_starts_with($data, 'server_filter_category_')) {
            $cat = substr($data, strlen('server_filter_category_'));
            if (in_array(strtolower($cat), ['any','all','clear','none'], true)) {
                $this->setPlanFilter($chatId, 'category', null);
            } else {
                $this->setPlanFilter($chatId, 'category', (int) $cat);
            }
            $this->previewLoading($chatId, $messageId);
            $this->handleServersPage($chatId, $userId, 1);
        } elseif (str_starts_with($data, 'server_filter_days_')) {
            $days = substr($data, strlen('server_filter_days_'));
            if (in_array(strtolower($days), ['any','all','clear','none'], true)) {
                $this->setPlanFilter($chatId, 'days', null);
            } else {
                $this->setPlanFilter($chatId, 'days', (int) $days);
            }
            $this->previewLoading($chatId, $messageId);
            $this->handleServersPage($chatId, $userId, 1);
        } elseif ($data === 'server_filter_clear') {
            $this->clearPlanFilters($chatId);
            $this->previewLoading($chatId, $messageId);
            $this->handleServersPage($chatId, $userId, 1);
        } elseif (str_starts_with($data, 'server_page_')) {
            $page = (int) substr($data, 12);
            $this->previewLoading($chatId, $messageId);
            $this->handleServersPage($chatId, $userId, $page);
        } elseif (str_starts_with($data, 'myproxies_page_')) {
            $page = (int) substr($data, 15);
            $this->previewLoading($chatId, $messageId);
            $this->handleMyProxies($chatId, $userId, $page);
        } elseif (str_starts_with($data, 'orders_page_')) {
            $page = (int) substr($data, 12);
            $this->previewLoading($chatId, $messageId);
            $this->handleOrders($chatId, $userId, $page);
        } elseif (str_starts_with($data, 'set_lang_')) {
            $lc = substr($data, strlen('set_lang_'));
            $norm = LocaleService::normalize($lc);
            if (LocaleService::isSupported($norm)) {
                cache()->put("tg_locale_{$chatId}", $norm, now()->addDays(30));
                app()->setLocale($norm);
            }
            $this->previewLoading($chatId, $messageId);
            $this->handleMenu($chatId, $userId);
        } elseif (str_starts_with($data, 'server_health_page_')) {
            $page = (int) substr($data, 20);
            $this->previewLoading($chatId, $messageId);
            $this->handleServerHealth($chatId, $userId, $page);
        } elseif (str_starts_with($data, 'buy_plan_')) {
            $planId = (int) substr($data, 9);
            // If not linked yet, store pending buy and start signup
            if (!Customer::where('telegram_chat_id', $chatId)->exists()) {
                cache()->put("tg_pending_buy_{$chatId}", $planId, now()->addMinutes(10));
                $this->sendMessage($chatId, __('telegram.messages.need_account_purchase'));
                $this->handleSignup($chatId, $userId);
            } else {
                $this->handleBuyConfirm($chatId, $userId, $planId);
            }
        } elseif (str_starts_with($data, 'confirm_buy_')) {
            $planId = (int) substr($data, 12);
            // Proceed to create order immediately
            $this->handleBuy($chatId, $userId, (string) $planId);
        } elseif ($data === 'cancel_buy') {
                $this->sendMessage($chatId, __('telegram.messages.purchase_cancelled'));
        } elseif ($data === 'noop') {
            // No operation: acknowledge tap without sending a new message
            // Optionally could edit message to show the same content; do nothing here
        } elseif (str_starts_with($data, 'config_open_')) {
            $this->handleConfig($chatId, $userId, substr($data, strlen('config_open_')));
        } elseif (str_starts_with($data, 'reset_open_')) {
            $this->handleReset($chatId, $userId, substr($data, strlen('reset_open_')));
        } else {
            switch ($data) {
                case 'admin_panel':
                    $this->previewLoading($chatId, $messageId);
                    $this->handleAdminPanel($chatId, $userId);
                    break;
                case 'open_menu':
                    $this->previewLoading($chatId, $messageId);
                    $this->handleMenu($chatId, $userId);
                    break;
                // case 'open_more': deprecated

                case 'refresh_balance':
                    $this->previewLoading($chatId, $messageId);
                    $this->handleBalance($chatId, $userId);
                    break;
                case 'open_balance':
                    $this->previewLoading($chatId, $messageId);
                    $this->handleBalance($chatId, $userId);
                    break;

                case 'view_servers':
                    $this->previewLoading($chatId, $messageId);
                    $this->handleServersPage($chatId, $userId, 1);
                    break;
                case 'server_health':
                    $this->previewLoading($chatId, $messageId);
                    $this->handleServerHealth($chatId, $userId);
                    break;

                case 'user_stats':
                    $this->previewLoading($chatId, $messageId);
                    $this->handleUserStats($chatId, $userId);
                    break;

                case 'system_stats':
                    $this->previewLoading($chatId, $messageId);
                    $this->handleSystemStats($chatId, $userId);
                    break;

                case 'view_myproxies':
                    $this->previewLoading($chatId, $messageId);
                    $this->handleMyProxies($chatId, $userId);
                    break;

                case 'view_orders':
                    $this->previewLoading($chatId, $messageId);
                    $this->handleOrders($chatId, $userId);
                    break;
                // case 'open_topup': deprecated from main menu

                case 'admin_broadcast':
                    // Show usage/help for broadcast; actual broadcast text should be sent as /broadcast <message>
                    $text = $this->renderView('telegram.admin.broadcast_help');
                    $this->sendMessage($chatId, $text);
                    break;

                case 'open_support':
                    $this->previewLoading($chatId, $messageId);
                    $this->handleSupport($chatId, $userId, '');
                    break;

                case 'view_promotions':
                    $this->previewLoading($chatId, $messageId);
                    $this->handlePromotions($chatId, $userId);
                    break;

                case 'open_referrals':
                    $this->previewLoading($chatId, $messageId);
                    $this->handleReferrals($chatId, $userId);
                    break;

                case 'signup_start':
                    $this->previewLoading($chatId, $messageId);
                    $this->handleSignup($chatId, $userId);
                    break;

                case 'open_profile':
                    $this->previewLoading($chatId, $messageId);
                    $this->handleProfile($chatId, $userId);
                    break;

                case 'open_link':
                    $this->previewLoading($chatId, $messageId);
                    $this->handleLink($chatId, $userId);
                    break;

                case 'open_language':
                    $this->previewLoading($chatId, $messageId);
                    $this->handleLanguage($chatId, $userId, '');
                    break;

                case 'profile_update_name':
                    $this->handleProfileUpdateCallback($chatId, 'name');
                    break;

                case 'profile_update_email':
                    $this->handleProfileUpdateCallback($chatId, 'email');
                    break;
            }
        }

        // Answer callback query to remove loading state
        if ($this->tdlib) {
            $this->tdlib->answerCallbackQuery([
                'callback_query_id' => $callbackQuery->getId()
            ]);
        } else {
            $this->telegram->answerCallbackQuery([
                'callback_query_id' => $callbackQuery->getId()
            ]);
        }
    }

    /**
     * Show current promotions or referral program info.
     */
    protected function handlePromotions(int $chatId, int $userId): void
    {
        // For now, pull from DB template with fallback to a simple message
        $text = $this->templates->render('promotions_overview', 'telegram', [
            'url' => config('app.url') . '/promotions'
        ]);
        if ($text === 'promotions_overview') {
            $text = '🎁 ' . (Lang::has('telegram.messages.promotions_default') ? __('telegram.messages.promotions_default') : 'Discover limited-time deals and earn rewards for referrals.');
        }

        $kb = Keyboard::make()->inline();
    $referText = '💸 ' . $this->trans('telegram.buttons.refer_friend', [], 'Refer a Friend');
    $couponText = '🎟 ' . $this->trans('telegram.buttons.coupons', [], 'Coupons & Deals');
        $kb->row([
            Keyboard::inlineButton(['text' => $referText, 'callback_data' => 'open_referrals']),
            Keyboard::inlineButton(['text' => $couponText, 'url' => config('app.url') . '/promotions'])
        ]);
    $kb = $this->appendBackToMenu($kb, $chatId, $userId);
    $this->sendMessageWithKeyboard($chatId, $text, $kb);
    }

    /**
     * Show referral link and basic stats (placeholder).
     */
    protected function handleReferrals(int $chatId, int $userId): void
    {
        $customer = Customer::where('telegram_chat_id', $chatId)->first();
        $refUrl = config('app.url') . '/referrals';
        $text = $this->templates->render('referrals_overview', 'telegram', [
            'url' => $refUrl,
            'name' => $customer?->name ?? 'there',
        ]);
        if ($text === 'referrals_overview') {
            $text = '💸 ' . (Lang::has('telegram.messages.referrals_default') ? __('telegram.messages.referrals_default') : 'Share your referral link and earn rewards when friends buy. Open your dashboard to copy your link.');
        }
        $kb = Keyboard::make()->inline();
        $kb->row([
            Keyboard::inlineButton(['text' => '🔗 ' . $this->trans('telegram.buttons.visit_website', [], $this->trans('telegram.common.open_dashboard', [], 'Visit website')), 'url' => $refUrl])
        ]);
    $kb = $this->appendBackToMenu($kb, $chatId, $userId);
    $this->sendMessageWithKeyboard($chatId, $text, $kb);
    }

    /**
     * Begin signup flow: ask for email, then name; create Customer and link to chat.
     */
    protected function handleSignup(int $chatId, int $userId): void
    {
        if (Customer::where('telegram_chat_id', $chatId)->exists()) {
            $this->sendMessage($chatId, __('telegram.messages.already_linked'));
            return;
        }
        cache()->put("tg_flow_{$chatId}", [
            'name' => 'signup',
            'step' => 'email'
        ], now()->addMinutes(10));
        $this->sendMessage($chatId, __('telegram.messages.signup_start'));
    }

    /**
     * Handle ongoing conversational flows based on cached state
     */
    protected function handleOngoingFlow(int $chatId, int $userId, string $text): bool
    {
        $state = cache()->get("tg_flow_{$chatId}");
        if (!$state || !is_array($state)) return false;

        // Signup flow
        if (($state['name'] ?? null) === 'signup') {
            if (($state['step'] ?? null) === 'email') {
                $email = trim($text);
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $this->sendMessage($chatId, __('telegram.messages.invalid_email'));
                    return true;
                }
                $state['email'] = $email;
                $state['step'] = 'name';
                cache()->put("tg_flow_{$chatId}", $state, now()->addMinutes(10));
                $this->sendMessage($chatId, __('telegram.messages.enter_name'));
                return true;
            }
            if (($state['step'] ?? null) === 'name') {
                $name = trim($text);
                if ($name === '' || mb_strlen($name) < 2) {
                    $this->sendMessage($chatId, __('telegram.messages.invalid_name'));
                    return true;
                }
                // Create customer if email free; otherwise attach if unlinked
                $email = $state['email'];
                $existing = Customer::where('email', $email)->first();
                if ($existing) {
                    if ($existing->telegram_chat_id && $existing->telegram_chat_id != $chatId) {
                        $this->sendMessage($chatId, __('telegram.messages.email_linked_other'));
                        cache()->forget("tg_flow_{$chatId}");
                        return true;
                    }
                    $customer = $existing;
                    $customer->name = $name;
                } else {
                    $customer = new Customer();
                    $customer->email = $email;
                    $customer->name = $name;
                    $customer->is_active = true;
                    // Generate a random password the user can reset on web later
                    $customer->password = bcrypt(Str::random(16));
                }
                $customer->telegram_chat_id = $chatId;
                $customer->telegram_username = null;
                $customer->save();

                cache()->forget("tg_flow_{$chatId}");
                $this->sendMessage($chatId, __('telegram.messages.signup_success', ['name' => $customer->name]));

                // If there is a pending buy, resume it
                $pendingPlanId = cache()->pull("tg_pending_buy_{$chatId}");
                if ($pendingPlanId) {
                    $this->sendMessage($chatId, __('telegram.messages.resuming_purchase'));
                    $this->handleBuyConfirm($chatId, $userId, (int) $pendingPlanId);
                } else {
                    // Otherwise open menu
                    $this->handleMenu($chatId, $userId);
                }
                return true;
            }
        }

        // Profile updates
        if (($state['name'] ?? null) === 'profile_update_name') {
            $name = trim($text);
            if ($name === '' || mb_strlen($name) < 2) {
                $this->sendMessage($chatId, __('telegram.messages.invalid_name'));
                return true;
            }
            $customer = Customer::where('telegram_chat_id', $chatId)->first();
            if ($customer) {
                $customer->name = $name;
                $customer->save();
                $this->sendMessage($chatId, __('telegram.messages.name_updated'));
            }
            cache()->forget("tg_flow_{$chatId}");
            return true;
        }

        if (($state['name'] ?? null) === 'profile_update_email') {
            $email = trim($text);
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->sendMessage($chatId, __('telegram.messages.invalid_email'));
                return true;
            }
            if (Customer::where('email', $email)->where('telegram_chat_id', '!=', $chatId)->exists()) {
                $this->sendMessage($chatId, __('telegram.messages.email_in_use'));
                return true;
            }
            $customer = Customer::where('telegram_chat_id', $chatId)->first();
            if ($customer) {
                $customer->email = $email;
                $customer->save();
                $this->sendMessage($chatId, __('telegram.messages.email_updated'));
            }
            cache()->forget("tg_flow_{$chatId}");
            return true;
        }

        return false;
    }

    /**
     * Handle profile update callbacks to prompt for new values
     */
    protected function handleProfileUpdateCallback(int $chatId, string $type): void
    {
        if (!Customer::where('telegram_chat_id', $chatId)->exists()) {
            $this->sendMessage($chatId, __('telegram.messages.need_account_profile'));
            return;
        }
        $stateName = $type === 'name' ? 'profile_update_name' : 'profile_update_email';
        cache()->put("tg_flow_{$chatId}", [ 'name' => $stateName ], now()->addMinutes(10));
        $prompt = $type === 'name' ? __('telegram.messages.prompt_new_name') : __('telegram.messages.prompt_new_email');
        $this->sendMessage($chatId, $prompt);
    }

    /**
     * Get authenticated web User (staff or customer-linked account context)
     */
    protected function getAuthenticatedUser(int $chatId, int $userId): ?User
    {
        // Prefer User model linked to this chat for staff/admin features
        $user = User::where('telegram_chat_id', $chatId)->first();
        if ($user) {
            return $user;
        }
        // Fallback: if a Customer is linked, try to resolve its owning User if applicable
        $customer = Customer::where('telegram_chat_id', $chatId)->first();
        if (method_exists(Customer::class, 'user') && $customer && $customer->user) {
            return $customer->user;
        }
        return null;
    }

    /**
     * Handle /profile command: show current profile with quick edit actions.
     */
    protected function handleProfile(int $chatId, int $userId): void
    {
        $customer = Customer::where('telegram_chat_id', $chatId)->first();
        if (!$customer) {
            $this->sendMessage($chatId, __('telegram.messages.need_account_profile'));
            return;
        }

        $text = $this->renderView('telegram.profile', [
            'name' => $customer->name,
            'email' => $customer->email,
            'joined' => $customer->created_at?->format('M j, Y') ?? '—',
        ]);

        $kb = Keyboard::make()->inline()
            ->row([
                Keyboard::inlineButton(['text' => '✏️ ' . __('telegram.buttons.edit_name'), 'callback_data' => 'profile_update_name']),
                Keyboard::inlineButton(['text' => '✉️ ' . __('telegram.buttons.edit_email'), 'callback_data' => 'profile_update_email'])
            ]);
        $kb = $this->appendBackToMenu($kb, $chatId, $userId);
        $this->sendMessageWithKeyboard($chatId, $text, $kb);
    }

    /**
     * Secondary "More" submenu with extra actions.
     */
    // Note: "More" submenu removed; items merged into main menu.

    /**
     * Small helper to return a minimal inline keyboard with just Back to Menu.
     */
    protected function backKeyboard(): Keyboard
    {
        $kb = Keyboard::make()->inline();
        $kb->row([
            Keyboard::inlineButton(['text' => '⬅️ ' . $this->trans('telegram.buttons.back_to_menu', [], 'Back to Menu'), 'callback_data' => 'open_menu'])
        ]);
        return $kb;
    }

    /**
     * Get current plan filters for a chat.
     */
    protected function getPlanFilters(int $chatId): array
    {
        $filters = cache()->get("tg_plan_filters_{$chatId}");
        if (!is_array($filters)) {
            $filters = [ 'country' => null, 'category' => null, 'days' => null ];
        }
        return $filters;
    }

    /**
     * Persist a single filter value.
     */
    protected function setPlanFilter(int $chatId, string $key, $value): void
    {
        $filters = $this->getPlanFilters($chatId);
        $filters[$key] = $value ?: null;
        cache()->put("tg_plan_filters_{$chatId}", $filters, now()->addHours(1));
    }

    /**
     * Clear all plan filters.
     */
    protected function clearPlanFilters(int $chatId): void
    {
        cache()->forget("tg_plan_filters_{$chatId}");
    }

    /**
     * Show filter selection menu (countries, categories, days).
     */
    protected function showFilterMenu(int $chatId, int $userId): void
    {
        // Load options from model helpers
        $countries = ServerPlan::getAvailableCountries(); // -> country_code, plan_count
        // Restrict categories to those with available plans given current country/days filters
        $filters = $this->getPlanFilters($chatId);
        $catQuery = \App\Models\ServerCategory::query()
            ->where('is_active', true)
            ->whereHas('plans', function($q) use ($filters) {
                $q->where('is_active', true)
                  ->where('in_stock', true)
                  ->where('on_sale', true);
                if (!empty($filters['country'])) {
                    $q->where('country_code', $filters['country']);
                }
                if (!empty($filters['days'])) {
                    $q->where('days', (int)$filters['days']);
                }
            })
            ->orderBy('name');
        $categories = $catQuery->get(['id', 'name']);
        // Common durations we support; filter down to those present
        $dayOptions = [7, 15, 30, 60, 90, 180, 365];
        $existingDays = ServerPlan::query()
            ->select('days')
            ->whereNotNull('days')
            ->where('is_active', true)
            ->groupBy('days')
            ->pluck('days')
            ->map(fn($d) => (int)$d)
            ->all();
        $dayOptions = array_values(array_intersect($dayOptions, $existingDays));

        $kb = Keyboard::make()->inline();
        // Countries row(s)
        $kb->row([Keyboard::inlineButton(['text' => '🌍 ' . $this->trans('telegram.filters.country', [], 'Country'), 'callback_data' => 'noop'])]);
        $row = [];
        // Add an All/Clear option first
        $row[] = Keyboard::inlineButton(['text' => $filters['country'] ? '🧹 ' . $this->trans('telegram.common.clear', [], 'Clear') : '⭐ ' . $this->trans('telegram.common.all', [], 'All'), 'callback_data' => 'server_filter_country_' . ($filters['country'] ? 'clear' : 'ANY')]);
        foreach ($countries as $c) {
            $code = strtoupper($c->country_code);
            // Skip invalid codes
            if (!preg_match('/^[A-Z]{2}$/', $code)) { continue; }
            $label = $this->countryLabel($code);
            // Append count for clarity
            $count = (int) ($c->plan_count ?? 0);
            if ($count > 0) { $label .= " ({$count})"; }
            if ($filters['country'] === $code) { $label = '✅ ' . $label; }
            $row[] = Keyboard::inlineButton(['text' => $label, 'callback_data' => 'server_filter_country_' . $code]);
            if (count($row) >= 3) { $kb->row($row); $row = []; }
        }
        if (!empty($row)) { $kb->row($row); }

        // Categories row(s)
        $kb->row([Keyboard::inlineButton(['text' => '🗂 ' . $this->trans('telegram.filters.category', [], 'Category'), 'callback_data' => 'noop'])]);
        $row = [];
        $row[] = Keyboard::inlineButton(['text' => $filters['category'] ? '🧹 ' . $this->trans('telegram.common.clear', [], 'Clear') : '⭐ ' . $this->trans('telegram.common.all', [], 'All'), 'callback_data' => 'server_filter_category_' . ($filters['category'] ? 'clear' : 'ANY')]);
        foreach ($categories as $cat) {
            $name = $cat->name ?: ('#' . $cat->id);
            if ((int)$filters['category'] === (int)$cat->id) { $name = '✅ ' . $name; }
            $row[] = Keyboard::inlineButton(['text' => $name, 'callback_data' => 'server_filter_category_' . $cat->id]);
            if (count($row) >= 3) { $kb->row($row); $row = []; }
        }
        if (!empty($row)) { $kb->row($row); }

        // Days row(s)
        if (!empty($dayOptions)) {
            $kb->row([Keyboard::inlineButton(['text' => '📅 ' . $this->trans('telegram.filters.duration', [], 'Duration'), 'callback_data' => 'noop'])]);
            $row = [];
            $row[] = Keyboard::inlineButton(['text' => $filters['days'] ? '🧹 ' . $this->trans('telegram.common.clear', [], 'Clear') : '⭐ ' . $this->trans('telegram.common.all', [], 'All'), 'callback_data' => 'server_filter_days_' . ($filters['days'] ? 'clear' : 'ANY')]);
            foreach ($dayOptions as $d) {
                $name = $d . 'd';
                if ((int)$filters['days'] === (int)$d) { $name = '✅ ' . $name; }
                $row[] = Keyboard::inlineButton(['text' => $name, 'callback_data' => 'server_filter_days_' . $d]);
                if (count($row) >= 4) { $kb->row($row); $row = []; }
            }
            if (!empty($row)) { $kb->row($row); }
        }

        $kb = $this->appendBackToMenu($kb, $chatId, $userId);

        $text = '🔎 ' . $this->trans('telegram.messages.choose_filters', [], 'Choose filters to narrow down plans:');
        $this->sendMessageWithKeyboard($chatId, $text, $kb);
    }
}
