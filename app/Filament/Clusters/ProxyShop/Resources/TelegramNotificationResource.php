<?php

namespace App\Filament\Clusters\ProxyShop\Resources;

use App\Models\TelegramNotification;
use App\Models\Customer;
use App\Services\TelegramBotService;
use Filament\Forms;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use BackedEnum;
use UnitEnum;
use App\Filament\Clusters\ProxyShop\Resources\TelegramNotificationResource\Pages;

class TelegramNotificationResource extends Resource
{
    protected static ?string $model = TelegramNotification::class;
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-bell-alert';
    protected static UnitEnum|string|null $navigationGroup = 'Telegram';
    protected static ?string $navigationLabel = 'Notifications';
    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('title')->label('Title')->maxLength(120),
            Textarea::make('message')->label('Message')->rows(8)->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('id')->sortable(),
            TextColumn::make('title')->searchable()->wrap(),
            TextColumn::make('status')->badge(),
            TextColumn::make('sent_at')->since(),
            TextColumn::make('created_at')->since(),
        ])->actions([
            \Filament\Actions\Action::make('send')
                ->label('Send Now')
                ->icon('heroicon-o-paper-airplane')
                ->requiresConfirmation()
                ->action(function (TelegramNotification $record) {
                    $message = $record->message ?: '';
                    if ($message === '') {
                        Notification::make()->title('Message required')->danger()->send();
                        return;
                    }
                    $svc = app(TelegramBotService::class);
                    $sent = 0; $failed = 0;
                    Customer::whereNotNull('telegram_chat_id')->chunk(500, function($chunk) use (&$sent, &$failed, $svc, $message) {
                        foreach ($chunk as $c) {
                            try { $svc->sendDirectMessage((int)$c->telegram_chat_id, $message); $sent++; usleep(75000); } catch (\Throwable $e) { $failed++; }
                        }
                    });
                    $record->update(['status' => 'sent', 'sent_at' => now()]);
                    Notification::make()->title("Sent: {$sent} • Failed: {$failed}")->success()->send();
                }),
            \Filament\Actions\EditAction::make(),
            \Filament\Actions\DeleteAction::make(),
        ])->bulkActions([
            \Filament\Actions\DeleteBulkAction::make(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTelegramNotifications::route('/'),
            'create' => Pages\CreateTelegramNotification::route('/create'),
            'edit' => Pages\EditTelegramNotification::route('/{record}/edit'),
        ];
    }
}
