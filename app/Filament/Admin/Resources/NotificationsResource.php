<?php

namespace App\Filament\Admin\Resources;

use App\Models\NotificationTemplate;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use UnitEnum;
use BackedEnum;

class NotificationsResource extends Resource
{
    protected static ?string $model = NotificationTemplate::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-bell';
    protected static ?string $navigationLabel = 'Notifications';
    protected static UnitEnum|string|null $navigationGroup = 'Telegram & Notifications';
    protected static ?int $navigationSort = 1;

    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();
        return (bool) ($user && ($user->hasRole('admin') || in_array($user->role ?? null, ['admin','support_manager'])));
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        return (bool) ($user && ($user->hasRole('admin') || in_array($user->role ?? null, ['admin','support_manager'])));
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();
        return (bool) ($user && ($user->hasRole('admin') || in_array($user->role ?? null, ['admin','support_manager'])));
    }

    public static function canEdit($record): bool
    {
        return static::canCreate();
    }

    public static function canDelete($record): bool
    {
        $user = auth()->user();
        return (bool) ($user && ($user->hasRole('admin') || in_array($user->role ?? null, ['admin'])));
    }

    public static function canDeleteAny(): bool
    {
        return static::canDelete(null);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            \Filament\Schemas\Components\Grid::make(2)->schema([
                Forms\Components\TextInput::make('key')->required()->maxLength(100)->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('name')->required()->maxLength(150),
                Forms\Components\Select::make('channel')->options([
                    'telegram' => 'Telegram',
                    'email' => 'Email',
                    'sms' => 'SMS',
                    'system' => 'System',
                ])->required()->default('telegram'),
                Forms\Components\TextInput::make('locale')->label('Locale (2 letters)')->required()->maxLength(8)->default('en'),
                Forms\Components\TextInput::make('subject')->maxLength(200)->visible(fn ($get) => $get('channel') === 'email'),
                Forms\Components\Toggle::make('enabled')->default(true),
            ]),
            Forms\Components\Textarea::make('body')->label('Body')->rows(10)->columnSpanFull()->helperText('Telegram: simple HTML only. Email: Markdown/HTML allowed.'),
            Forms\Components\Textarea::make('notes')->label('Notes')->rows(3)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
    $emailEnabled = !empty(config('mail.mailers.smtp.host')) || !empty(config('services.postmark.token')) || !empty(config('services.ses.key'));
    $smsEnabled = !empty(config('services.twilio.sid')) || !empty(config('services.vonage.key'));

    return $table
            ->columns([
                Tables\Columns\TextColumn::make('key')->searchable()->sortable(),
                Tables\Columns\BadgeColumn::make('channel')->colors([
                    'primary' => 'telegram',
                    'success' => 'email',
                    'warning' => 'sms',
                    'gray' => 'system',
                ]),
                Tables\Columns\TextColumn::make('locale')->sortable(),
                Tables\Columns\IconColumn::make('enabled')->boolean(),
                Tables\Columns\TextColumn::make('updated_at')->dateTime()->since(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('channel')->options([
                    'telegram' => 'Telegram',
                    'email' => 'Email',
                    'sms' => 'SMS',
                    'system' => 'System',
                ]),
            ])
            ->actions([
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\Action::make('preview')
                    ->label('Preview')
                    ->icon('heroicon-o-eye')
                    ->form([
                        Forms\Components\KeyValue::make('data')
                            ->label('Sample Data (placeholders)')
                            ->keyLabel('Key')
                            ->valueLabel('Value')
                            ->reorderable()
                            ->columnSpanFull(),
                    ])
                    ->action(function ($record, array $data) {
                        try {
                            $renderer = app(\App\Services\TemplateRenderer::class);
                            $message = $renderer->render($record->key, $record->channel, (array) ($data['data'] ?? []), $record->locale);
                            \Filament\Notifications\Notification::make()
                                ->title('Rendered Preview')
                                ->body(mb_strimwidth((string) $message, 0, 800, '…'))
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            \Filament\Notifications\Notification::make()->title('Preview failed')->body($e->getMessage())->danger()->send();
                        }
                    }),
                \Filament\Actions\Action::make('send_test_email')
                    ->label('Send Test Email')
                    ->icon('heroicon-o-envelope')
                    ->visible(fn ($record) => ($record->channel === 'email') && $emailEnabled)
                    ->form([
                        Forms\Components\TextInput::make('to')->email()->required(),
                        Forms\Components\KeyValue::make('data')->label('Template data')->columnSpanFull(),
                    ])
                    ->action(function ($record, array $data) {
                        $renderer = app(\App\Services\TemplateRenderer::class);
                        $subject = $record->subject ?: $record->name;
                        $body = $renderer->render($record->key, 'email', (array) ($data['data'] ?? []), $record->locale);
                        \Mail::raw($body, function($m) use ($data, $subject) { $m->to($data['to'])->subject($subject); });
                    }),
                \Filament\Actions\Action::make('send_test_sms')
                    ->label('Send Test SMS')
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->visible(fn ($record) => ($record->channel === 'sms') && $smsEnabled)
                    ->form([
                        Forms\Components\TextInput::make('to')->tel()->required(),
                        Forms\Components\KeyValue::make('data')->label('Template data')->columnSpanFull(),
                    ])
                    ->action(function ($record, array $data) {
                        $renderer = app(\App\Services\TemplateRenderer::class);
                        $message = $renderer->render($record->key, 'sms', (array) ($data['data'] ?? []), $record->locale);
                        if (!empty(config('services.twilio.sid'))) {
                            app(\App\Services\Sms\TwilioService::class)->send($data['to'], $message);
                        } elseif (!empty(config('services.vonage.key'))) {
                            app(\App\Services\Sms\VonageService::class)->send($data['to'], $message);
                        }
                    }),
                \Filament\Actions\Action::make('send_test_telegram')
                    ->label('Send Test Telegram')
                    ->icon('heroicon-o-paper-airplane')
                    ->visible(fn ($record) => ($record->channel === 'telegram'))
                    ->form([
                        Forms\Components\TextInput::make('chat_id')->numeric()->required()->label('Telegram Chat ID'),
                        Forms\Components\KeyValue::make('data')->label('Template data')->columnSpanFull(),
                    ])
                    ->action(function ($record, array $data) {
                        try {
                            $renderer = app(\App\Services\TemplateRenderer::class);
                            $service = app(\App\Services\TelegramBotService::class);
                            $message = $renderer->render($record->key, 'telegram', (array) ($data['data'] ?? []), $record->locale);
                            $service->sendDirectMessage((int) $data['chat_id'], (string) $message);
                            \Filament\Notifications\Notification::make()->title('Telegram sent').success()->send();
                        } catch (\Throwable $e) {
                            \Filament\Notifications\Notification::make()->title('Telegram failed')->body($e->getMessage())->danger()->send();
                        }
                    }),
                \Filament\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => NotificationsResource\Pages\Templates::route('/'),
            'broadcasts' => NotificationsResource\Pages\Broadcasts::route('/broadcasts'),
            'telegram-templates' => NotificationsResource\Pages\TelegramTemplates::route('/telegram-templates'),
            'push-notifications' => NotificationsResource\Pages\PushNotifications::route('/push-notifications'),
        ];
    }
}
