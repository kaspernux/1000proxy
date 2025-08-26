<?php

namespace App\Filament\Admin\Resources\NotificationsResource\Pages;

use App\Filament\Admin\Resources\NotificationsResource;
use App\Models\PushNotification;
use Filament\Resources\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class PushNotifications extends Page implements Tables\Contracts\HasTable, \Filament\Forms\Contracts\HasForms
{
    use Tables\Concerns\InteractsWithTable;
    use \Filament\Forms\Concerns\InteractsWithForms;

    protected static string $resource = NotificationsResource::class;

    protected static ?string $title = 'Push Notifications';
    protected string $view = 'filament.admin.pages.blank';

    public function table(Table $table): Table
    {
        return $table
            ->query(PushNotification::query()->latest('id'))
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('customer_id')->label('Customer')->sortable(),
                Tables\Columns\TextColumn::make('device_id')->label('Device')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('title')->searchable()->limit(40),
                Tables\Columns\TextColumn::make('notification_type')->label('Type')->badge(),
                Tables\Columns\BadgeColumn::make('status')->colors([
                    'warning' => 'queued',
                    'success' => 'sent',
                    'danger' => 'failed',
                    'gray' => 'draft',
                    'info' => 'delivered',
                    'primary' => 'read',
                ]),
                Tables\Columns\TextColumn::make('sent_at')->since()->label('Sent'),
                Tables\Columns\TextColumn::make('created_at')->since(),
            ])
            ->headerActions([
                \Filament\Actions\Action::make('create')
                    ->label('New Push')
                    ->icon('heroicon-o-plus')
                    ->form([
                        Forms\Components\TextInput::make('customer_id')->numeric()->label('Customer ID')->nullable(),
                        Forms\Components\TextInput::make('device_id')->label('Device ID')->nullable(),
                        Forms\Components\TextInput::make('title')->required()->maxLength(120),
                        Forms\Components\Textarea::make('body')->required()->rows(5),
                        Forms\Components\TextInput::make('notification_type')->default('info')->maxLength(50),
                        Forms\Components\KeyValue::make('data')->label('Data')->keyLabel('Key')->valueLabel('Value')->reorderable()->nullable(),
                    ])
                    ->action(function (array $data) {
                        $record = PushNotification::create([
                            'customer_id' => $data['customer_id'] ?? null,
                            'device_id' => $data['device_id'] ?? null,
                            'title' => $data['title'] ?? null,
                            'body' => $data['body'] ?? null,
                            'notification_type' => $data['notification_type'] ?? 'info',
                            'data' => $data['data'] ?? null,
                            'status' => 'draft',
                        ]);
                        Notification::make()->title('Push created #'.$record->id)->success()->send();
                    }),
            ])
            ->actions([
                \Filament\Actions\Action::make('queue_send')
                    ->label('Queue Send')
                    ->icon('heroicon-o-paper-airplane')
                    ->requiresConfirmation()
                    ->action(function (PushNotification $record) {
                        \Log::info('Queue push notification', [
                            'push_id' => $record->id,
                            'actor' => Auth::id(),
                        ]);
                        dispatch(new \App\Jobs\SendPushNotificationJob($record->id))
                            ->onQueue('default');
                        $record->update(['status' => 'queued']);
                        Notification::make()->title('Queued for sending')->success()->send();
                    }),
                \Filament\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                \Filament\Actions\DeleteBulkAction::make(),
            ]);
    }
}
