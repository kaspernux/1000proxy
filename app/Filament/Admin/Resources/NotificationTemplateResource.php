<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\NotificationTemplateResource\Pages;
use App\Models\NotificationTemplate;
use BackedEnum;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Notifications\Notification;
use UnitEnum;

class NotificationTemplateResource extends Resource
{
    protected static ?string $model = NotificationTemplate::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Notification Templates';
    protected static UnitEnum|string|null $navigationGroup = 'Telegram & Notifications';
    protected static ?int $navigationSort = 9;

    public static function canCreate(): bool
    {
        $user = auth()->user();
        return (bool) ($user && in_array($user->role, ['admin','support_manager']));
    }

    public static function form(Schema $schema): Schema
    {
        $locales = (array) (config('locales.supported') ?? ['en']);
        $localeOptions = array_combine($locales, array_map('strtoupper', $locales));

        return $schema
            ->schema([
                Forms\Components\TextInput::make('key')
                    ->label('Key')
                    ->required()
                    ->maxLength(100)
                    ->helperText('Machine key, e.g., order_ready, help, welcome_customer'),
                Forms\Components\TextInput::make('name')
                    ->label('Name')
                    ->maxLength(150),
                Forms\Components\Select::make('channel')
                    ->label('Channel')
                    ->options([
                        'telegram' => 'Telegram',
                        'email' => 'Email',
                        'sms' => 'SMS',
                        'system' => 'System',
                    ])
                    ->required()
                    ->default('telegram'),
                Forms\Components\Select::make('locale')
                    ->label('Locale')
                    ->options($localeOptions)
                    ->required()
                    ->default('en'),
                Forms\Components\TextInput::make('subject')
                    ->label('Subject (email)')
                    ->maxLength(200)
                    ->visible(fn (callable $get) => $get('channel') === 'email'),
                Forms\Components\Textarea::make('body')
                    ->label('Body')
                    ->rows(10)
                    ->required()
                    ->helperText('Telegram: use simple HTML only (<b>, <i>, <u>, <code>, <a>, <br>). Placeholders: :name, :url, ...'),
                Forms\Components\Toggle::make('enabled')
                    ->label('Enabled')
                    ->default(true),
                Forms\Components\Textarea::make('notes')
                    ->label('Notes')
                    ->rows(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        $locales = (array) (config('locales.supported') ?? ['en']);
        $localeOptions = array_combine($locales, array_map('strtoupper', $locales));

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('key')->label('Key')->sortable()->searchable(),
                Tables\Columns\BadgeColumn::make('channel')->colors([
                    'primary' => 'telegram',
                    'success' => 'email',
                    'warning' => 'sms',
                    'gray' => 'system',
                ])->sortable(),
                Tables\Columns\TextColumn::make('locale')->label('Locale')->sortable(),
                Tables\Columns\IconColumn::make('enabled')->boolean()->label('Enabled')->sortable(),
                Tables\Columns\TextColumn::make('name')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')->dateTime()->since()->label('Updated'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('channel')->options([
                    'telegram' => 'Telegram',
                    'email' => 'Email',
                    'sms' => 'SMS',
                    'system' => 'System',
                ]),
                Tables\Filters\SelectFilter::make('locale')->options($localeOptions),
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
                            ->addButtonLabel('Add')
                            ->reorderable()
                            ->columnSpanFull(),
                    ])
                    ->action(function (NotificationTemplate $record, array $data) {
                        try {
                            $renderer = app(\App\Services\TemplateRenderer::class);
                            $message = $renderer->render($record->key, $record->channel, (array) ($data['data'] ?? []), $record->locale);
                            Notification::make()
                                ->title('Rendered Preview')
                                ->body(mb_strimwidth($message, 0, 800, '…'))
                                ->success()
                                ->send();
                        } catch (\Throwable $e) {
                            Notification::make()->title('Preview failed')->body($e->getMessage())->danger()->send();
                        }
                    }),
                \Filament\Actions\Action::make('send_test')
                    ->label('Send Test (Telegram)')
                    ->icon('heroicon-o-paper-airplane')
                    ->visible(fn (NotificationTemplate $record) => $record->channel === 'telegram')
                    ->form([
                        Forms\Components\TextInput::make('chat_id')->label('Chat ID')->numeric()->required(),
                        Forms\Components\KeyValue::make('data')->label('Sample Data')->keyLabel('Key')->valueLabel('Value')->reorderable(),
                    ])
                    ->action(function (NotificationTemplate $record, array $data) {
                        try {
                            $renderer = app(\App\Services\TemplateRenderer::class);
                            $service = app(\App\Services\TelegramBotService::class);
                            $message = $renderer->render($record->key, 'telegram', (array) ($data['data'] ?? []), $record->locale);
                            $service->sendDirectMessage((int) $data['chat_id'], (string) $message);
                            Notification::make()->title('Test sent')->success()->send();
                        } catch (\Throwable $e) {
                            Notification::make()->title('Send failed')->body($e->getMessage())->danger()->send();
                        }
                    }),
                \Filament\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                \Filament\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNotificationTemplates::route('/'),
            'create' => Pages\CreateNotificationTemplate::route('/create'),
            'edit' => Pages\EditNotificationTemplate::route('/{record}/edit'),
        ];
    }
}
