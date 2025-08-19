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
            Action::make('set_commands')
                ->label('Set Commands')
                ->icon('heroicon-o-command-line')
                ->color('primary')
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
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Set Telegram Webhook')
                ->modalDescription('This will configure the webhook to your application URL and enable enhanced updates.')
                ->action(function () {
                    $ok = app(\App\Services\TelegramBotService::class)->setWebhook();
                    $n = Notification::make()
                        ->title($ok ? 'Webhook set' : 'Failed to set webhook')
                        ->body($ok ? 'Telegram webhook configured.' : 'Verify APP_URL and HTTPS availability.');
                    $ok ? $n->success() : $n->danger();
                    $n->send();
                }),

            Action::make('webhook_info')
                ->label('Webhook Info')
                ->icon('heroicon-o-information-circle')
                ->color('gray')
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
                ->color('warning')
                ->form([
                    \Filament\Forms\Components\TextInput::make('name')->label('Bot Name')->placeholder('1000Proxy Bot'),
                    \Filament\Forms\Components\TextInput::make('short')->label('Short Description')->maxLength(120)->placeholder('Fast proxies, instant delivery.'),
                    \Filament\Forms\Components\Textarea::make('description')->label('Description')->rows(4)->placeholder('Full description shown in Telegram bot profile.'),
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

            Action::make('broadcast')
                ->label('Broadcast')
                ->icon('heroicon-o-megaphone')
                ->color('danger')
                ->visible(fn () => auth()->user()?->isAdmin())
                ->form([
                    \Filament\Forms\Components\Textarea::make('message')
                        ->label('Message')
                        ->required()
                        ->rows(5)
                        ->placeholder('Write a broadcast message to all linked customers...'),
                    \Filament\Forms\Components\Toggle::make('only_active')
                        ->label('Only active customers')
                        ->default(false),
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
        ];
    }
}
