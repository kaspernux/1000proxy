<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use BackedEnum;

class TelegramBotManagement extends Page
{
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static ?string $navigationLabel = 'Telegram Bot';
    protected static ?string $title = 'Telegram Bot Management';
    protected static ?string $slug = 'telegram-bot-management';
    protected static ?int $navigationSort = 8;

    protected string $view = 'filament.admin.pages.telegram-bot-management';

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && $user->hasStaffPermission('access_telegram_bot');
    }

    public function getHeaderActions(): array
    {
        return [
            Action::make('bot_info')
                ->label('Bot Info')
                ->icon('heroicon-o-sparkles')
                ->action(function () {
                    try {
                        $svc = app(\App\Services\TelegramBotService::class);
                        $info = $svc->testBot();
                        $bot = $info['bot_info']['username'] ?? '—';
                        $wid = $info['webhook_info']['url'] ?? '—';
                        $pending = $info['webhook_info']['pending_update_count'] ?? 0;
                        Notification::make()
                            ->title('Bot: @' . $bot)
                            ->body("Webhook: {$wid}\nPending: {$pending}")
                            ->success()
                            ->send();
                    } catch (\Throwable $e) {
                        Notification::make()->title('Bot info failed')->body($e->getMessage())->danger()->send();
                    }
                }),

            Action::make('set_commands')
                ->label('Set Commands')
                ->icon('heroicon-o-command-line')
                ->action(function () {
                    $ok = app(\App\Services\TelegramBotService::class)->setCommands();
                    $n = Notification::make()
                        ->title($ok ? 'Commands updated' : 'Failed to update commands')
                        ->body($ok ? 'Telegram bot commands were set (localized).' : 'Check bot token and connectivity.');
                    $ok ? $n->success() : $n->danger();
                    $n->send();
                }),

            Action::make('set_webhook')
                ->label('Set Webhook')
                ->icon('heroicon-o-link')
                ->requiresConfirmation()
                ->modalHeading('Set Telegram Webhook')
                ->modalDescription('This will configure the webhook to your application URL and enable enhanced updates.')
                ->form([
                    \Filament\Schemas\Components\Section::make('Confirmation')->schema([
                        \Filament\Forms\Components\Placeholder::make('note')
                            ->content('Ensure APP_URL is HTTPS and publicly reachable. Any existing webhook will be overwritten.'),
                    ])->collapsible(),
                ])
                ->action(function () {
                    $ok = app(\App\Services\TelegramBotService::class)->setWebhook();
                    $n = Notification::make()
                        ->title($ok ? 'Webhook set' : 'Failed to set webhook')
                        ->body($ok ? 'Telegram webhook configured.' : 'Verify APP_URL and HTTPS availability.');
                    $ok ? $n->success() : $n->danger();
                    $n->send();
                }),

            Action::make('remove_webhook')
                ->label('Remove Webhook')
                ->icon('heroicon-o-link-slash')
                ->requiresConfirmation()
                ->modalHeading('Remove Telegram Webhook')
                ->form([
                    \Filament\Schemas\Components\Section::make('Confirmation')->schema([
                        \Filament\Forms\Components\Placeholder::make('warn')
                            ->content('Bot will switch to getUpdates (polling) if applicable. This may reduce responsiveness.'),
                    ])->collapsible(),
                ])
                ->action(function () {
                    $ok = app(\App\Services\TelegramBotService::class)->removeWebhook();
                    $n = Notification::make()
                        ->title($ok ? 'Webhook removed' : 'Failed to remove webhook')
                        ->body($ok ? 'Bot will use getUpdates (polling) if applicable.' : 'See logs for details.');
                    $ok ? $n->success() : $n->danger();
                    $n->send();
                }),

            Action::make('webhook_info')
                ->label('Webhook Info')
                ->icon('heroicon-o-information-circle')
                ->action(function () {
                    $info = app(\App\Services\TelegramBotService::class)->getWebhookInfo();
                    $status = $info['ok'] ?? false;
                    $url = $info['result']['url'] ?? 'n/a';
                    $pending = $info['result']['pending_update_count'] ?? 0;
                    $n = Notification::make()
                        ->title($status ? 'Webhook Active' : 'Webhook Status')
                        ->body("URL: {$url}\nPending updates: {$pending}");
                    $status ? $n->info() : $n->warning();
                    $n->send();
                }),

            Action::make('set_branding')
                ->label('Set Branding')
                ->icon('heroicon-o-pencil-square')
                ->form([
                    \Filament\Schemas\Components\Wizard::make()->steps([
                        \Filament\Schemas\Components\Wizard\Step::make('Basics')->schema([
                \Filament\Schemas\Components\Section::make('Bot Identity')->schema([
                                \Filament\Forms\Components\TextInput::make('name')->label('Bot Name')->placeholder('1000Proxy Bot'),
                                \Filament\Forms\Components\TextInput::make('short')->label('Short Description')->maxLength(120)->placeholder('Fast proxies, instant delivery.'),
                            ])->columns(2),
                        ]),
                        \Filament\Schemas\Components\Wizard\Step::make('Description')->schema([
                            \Filament\Forms\Components\Textarea::make('description')->label('Description')->rows(6)->placeholder('Full description shown in Telegram bot profile.'),
                            \Filament\Forms\Components\Placeholder::make('tip')->content('Keep it concise. Telegram truncates long descriptions in some views.'),
                        ]),
                    ])->skippable(),
                ])
                ->action(function (array $data) {
                    $ok = app(\App\Services\TelegramBotService::class)->setBranding(
                        $data['name'] ?? null,
                        $data['short'] ?? null,
                        $data['description'] ?? null,
                    );
                    $n = Notification::make()
                        ->title($ok ? 'Branding updated' : 'Branding failed')
                        ->body($ok ? 'Bot name and descriptions updated.' : 'Check bot permissions and token.');
                    $ok ? $n->success() : $n->danger();
                    $n->send();
                }),

            Action::make('branding_localized')
                ->label('Localized Branding')
                ->icon('heroicon-o-language')
                ->action(function () {
                    $ok = app(\App\Services\TelegramBotService::class)->setBrandingLocalized();
                    $n = Notification::make()
                        ->title($ok ? 'Localized branding applied' : 'Failed to set localized branding');
                    $ok ? $n->success() : $n->danger();
                    $n->send();
                }),

            Action::make('set_commands_locale')
                ->label('Set Commands (Locale)')
                ->icon('heroicon-o-language')
                ->form([
                    \Filament\Schemas\Components\Section::make('Locale')->schema([
                        \Filament\Forms\Components\Select::make('locale')->label('Locale')->required()->options(function(){
                            $l = (array) (config('locales.supported') ?? ['en']);
                            return array_combine($l, array_map('strtoupper', $l));
                        })->default('en'),
                        \Filament\Forms\Components\Placeholder::make('hint')->content('This will only update commands for the selected locale.'),
                    ])->columns(2)->collapsible(),
                ])
                ->action(function (array $data) {
                    $ok = app(\App\Services\TelegramBotService::class)->setCommandsForLocale((string) $data['locale']);
                    $n = Notification::make()->title($ok ? 'Commands updated for ' . strtoupper($data['locale']) : 'Failed to update commands');
                    $ok ? $n->success() : $n->danger();
                    $n->send();
                }),

            Action::make('set_branding_locale')
                ->label('Branding (Locale)')
                ->icon('heroicon-o-globe-alt')
                ->form([
                    \Filament\Schemas\Components\Wizard::make()->steps([
                        \Filament\Schemas\Components\Wizard\Step::make('Locale')->schema([
                            \Filament\Forms\Components\Select::make('locale')->label('Locale')->required()->options(function(){
                                $l = (array) (config('locales.supported') ?? ['en']);
                                return array_combine($l, array_map('strtoupper', $l));
                            })->default('en'),
                            \Filament\Forms\Components\Placeholder::make('note')->content('Choose the locale to customize. Base branding stays unchanged.'),
                        ]),
                        \Filament\Schemas\Components\Wizard\Step::make('Content')->schema([
                            \Filament\Schemas\Components\Fieldset::make('Localized fields')->schema([
                                \Filament\Forms\Components\TextInput::make('short')->label('Short Description')->placeholder('Localized short description'),
                                \Filament\Forms\Components\Textarea::make('description')->label('Description')->rows(5)->placeholder('Localized full description'),
                            ]),
                        ]),
                    ])->skippable(),
                ])
                ->action(function (array $data) {
                    $ok = app(\App\Services\TelegramBotService::class)->setBrandingForLocale(
                        (string) $data['locale'],
                        null,
                        $data['short'] ?? null,
                        $data['description'] ?? null,
                        ['short','description']
                    );
                    $n = Notification::make()->title($ok ? 'Branding updated for ' . strtoupper($data['locale']) : 'Branding failed');
                    $ok ? $n->success() : $n->danger();
                    $n->send();
                }),

            Action::make('republish_all')
                ->label('Republish All')
                ->icon('heroicon-o-arrow-path')
                ->requiresConfirmation()
                ->modalHeading('Republish Commands & Branding')
                ->modalDescription('Queue a job to republish commands and branding for all locales with pacing/backoff.')
                ->form([
                    \Filament\Forms\Components\Placeholder::make('info')->content('This operation may take a few minutes. Progress is visible in the queue health widgets.'),
                ])
                ->action(function () {
                    try {
                        \App\Jobs\TelegramRepublishAll::dispatch();
                        Notification::make()->title('Republish queued')->info()->send();
                    } catch (\Throwable $e) {
                        Notification::make()->title('Failed to queue republish')->body($e->getMessage())->danger()->send();
                    }
                }),

            Action::make('manage_templates')
                ->label('Manage Templates')
                ->icon('heroicon-o-document-text')
                ->url(fn() => route('filament.admin.resources.notification-templates.index'))
                ->openUrlInNewTab(),

            Action::make('menu_button')
                ->label('Menu Button')
                ->icon('heroicon-o-squares-2x2')
                ->form([
                    \Filament\Schemas\Components\Section::make('Menu Button')->schema([
                        \Filament\Schemas\Components\Grid::make(2)->schema([
                            \Filament\Forms\Components\Select::make('type')->label('Type')->options([
                                'commands' => 'Commands',
                                'web_app' => 'Web App',
                            ])->required()->default('commands'),
                            \Filament\Forms\Components\TextInput::make('text')->label('Button Text')->visible(fn($get) => $get('type') === 'web_app')->placeholder('Open 1000Proxy'),
                        ]),
                        \Filament\Forms\Components\TextInput::make('url')->label('Web App URL')->visible(fn($get) => $get('type') === 'web_app')->url()->placeholder(config('app.url') . '/app'),
                        \Filament\Forms\Components\Placeholder::make('guideline')->content('If you select Web App, ensure the URL serves a valid Telegram WebApp with HTTPS.'),
                    ])->collapsible(),
                ])
                ->action(function (array $data) {
                    $ok = app(\App\Services\TelegramBotService::class)->setMenuButton(
                        $data['type'] ?? 'commands',
                        $data['text'] ?? null,
                        $data['url'] ?? null,
                    );
                    $n = Notification::make()->title($ok ? 'Menu button updated' : 'Failed to update menu button');
                    $ok ? $n->success() : $n->danger();
                    $n->send();
                }),

            Action::make('broadcast')
                ->label('Broadcast')
                ->icon('heroicon-o-megaphone')
                ->visible(fn () => auth()->user()?->isAdmin())
                ->form([
                    \Filament\Schemas\Components\Wizard::make()->steps([
                        \Filament\Schemas\Components\Wizard\Step::make('Compose')->schema([
                            \Filament\Forms\Components\Textarea::make('message')
                                ->label('Message')
                                ->required()
                                ->rows(6)
                                ->placeholder('Write a broadcast message to all linked customers...'),
                            \Filament\Forms\Components\Toggle::make('only_active')
                                ->label('Only active customers')
                                ->default(false),
                        ]),
                        \Filament\Schemas\Components\Wizard\Step::make('Review')->schema([
                            \Filament\Forms\Components\Placeholder::make('preview')
                                ->content('Messages will be queued to the "telegram" queue and delivered in batches to avoid rate limits.'),
                        ]),
                    ])->skippable(),
                ])
                ->action(function (array $data) {
                    $service = app(\App\Services\TelegramBotService::class);
                    $admin = auth()->user();
                    if (!$admin || !$admin->isAdmin()) {
                        Notification::make()->title('Access denied')->danger()->send();
                        return;
                    }

                    $prefix = !empty($data['only_active']) ? 'active: ' : '';
                    $chatId = (int) ($admin->telegram_chat_id ?: 0);
                    $userId = $chatId;
                    try {
                        $ref = new \ReflectionClass($service);
                        $method = $ref->getMethod('handleBroadcast');
                        $method->setAccessible(true);
                        $method->invoke($service, $chatId, $userId, $prefix . (string) $data['message']);
                        Notification::make()->title('Broadcast queued')->info()->send();
                    } catch (\Throwable $e) {
                        Notification::make()->title('Broadcast failed')->body($e->getMessage())->danger()->send();
                    }
                }),

            Action::make('send_test')
                ->label('Send Test Message')
                ->icon('heroicon-o-paper-airplane')
                ->form([
                    \Filament\Schemas\Components\Section::make('Test Delivery')->schema([
                        \Filament\Schemas\Components\Grid::make(2)->schema([
                            \Filament\Forms\Components\TextInput::make('chat_id')->label('Chat ID')->numeric()->required()->placeholder('e.g. 123456789'),
                            \Filament\Forms\Components\Textarea::make('message')->label('Message')->default('Test message from 1000proxy bot')->rows(3),
                        ]),
                        \Filament\Forms\Components\Placeholder::make('help')->content('You can find your Chat ID via @userinfobot in Telegram.'),
                    ])->collapsible(),
                ])
                ->action(function (array $data) {
                    try {
                        app(\App\Services\TelegramBotService::class)->sendDirectMessage((int) $data['chat_id'], (string) $data['message']);
                        Notification::make()->title('Test sent')->success()->send();
                    } catch (\Throwable $e) {
                        Notification::make()->title('Test failed')->body($e->getMessage())->danger()->send();
                    }
                }),
        ];
    }
}
