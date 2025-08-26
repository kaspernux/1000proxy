<?php

namespace App\Filament\Admin\Resources\NotificationsResource\Pages;

use App\Models\TelegramNotification;
use App\Filament\Admin\Resources\NotificationsResource;
use BackedEnum;
use Filament\Resources\Pages\Page;
use Filament\Tables; 
use Filament\Tables\Table;
use Filament\Forms; 
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use App\Models\Customer;
use App\Services\TelegramBotService;

class Broadcasts extends Page implements Tables\Contracts\HasTable, \Filament\Forms\Contracts\HasForms
{
    use Tables\Concerns\InteractsWithTable;
    use \Filament\Forms\Concerns\InteractsWithForms;

    protected static string $resource = NotificationsResource::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-paper-airplane';
    protected static ?string $title = 'Broadcasts';

    protected string $view = 'filament.admin.pages.blank';

    public function table(Table $table): Table
    {
        return $table
            ->query(TelegramNotification::query())
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('title')->searchable()->wrap(),
                Tables\Columns\BadgeColumn::make('status')->colors([
                    'success' => 'sent',
                    'warning' => 'scheduled',
                    'danger' => 'failed',
                    'gray' => 'pending',
                ]),
                Tables\Columns\TextColumn::make('scheduled_at')->since()->label('Scheduled'),
                Tables\Columns\TextColumn::make('sent_at')->since()->label('Sent'),
                Tables\Columns\TextColumn::make('created_at')->since(),
            ])
            ->headerActions([
                \Filament\Actions\Action::make('create')
                    ->label('New Broadcast')
                    ->icon('heroicon-o-plus')
                    ->form([
                        Forms\Components\TextInput::make('title')->maxLength(120),
                        Forms\Components\Textarea::make('message')->rows(8)->required(),
                        Forms\Components\Select::make('recipients')->options([
                            'all' => 'All with Telegram',
                            'linked' => 'Only linked',
                        ])->default('all'),
                        Forms\Components\DateTimePicker::make('scheduled_at')->label('Schedule (optional)'),
                    ])
                    ->action(function (array $data) {
                        TelegramNotification::create([
                            'title' => $data['title'] ?? null,
                            'message' => $data['message'] ?? '',
                            'recipients' => $data['recipients'] ?? 'all',
                            'status' => empty($data['scheduled_at']) ? 'pending' : 'scheduled',
                            'scheduled_at' => $data['scheduled_at'] ?? null,
                            'created_by' => Auth::id(),
                        ]);
                        Notification::make()->title('Broadcast created')->success()->send();
                    }),
            ])
            ->actions([
                \Filament\Actions\Action::make('send')
                    ->label('Send Now')
                    ->icon('heroicon-o-paper-airplane')
                    ->requiresConfirmation()
                    ->action(function (TelegramNotification $record) {
                        if (empty($record->message)) {
                            Notification::make()->title('Message required')->danger()->send();
                            return;
                        }
                        \Log::info('Queue broadcast send', [
                            'notification_id' => $record->id,
                            'actor' => Auth::id(),
                        ]);
                        dispatch(new \App\Jobs\SendBroadcastJob($record->id))->onQueue('telegram');
                        Notification::make()->title('Broadcast queued for delivery').info()->send();
                    }),
                \Filament\Actions\Action::make('edit')
                    ->label('Edit')
                    ->icon('heroicon-o-pencil-square')
                    ->form([
                        Forms\Components\TextInput::make('title')->maxLength(120),
                        Forms\Components\Textarea::make('message')->rows(8)->required(),
                        Forms\Components\Select::make('recipients')->options([
                            'all' => 'All with Telegram',
                            'linked' => 'Only linked',
                        ]),
                        Forms\Components\DateTimePicker::make('scheduled_at')->label('Schedule (optional)'),
                    ])
                    ->action(function (TelegramNotification $record, array $data) {
                        $record->update($data);
                        Notification::make()->title('Broadcast updated')->success()->send();
                    }),
                \Filament\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                \Filament\Actions\DeleteBulkAction::make(),
            ]);
    }
}
