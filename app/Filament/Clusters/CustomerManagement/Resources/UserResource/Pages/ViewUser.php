<?php

namespace App\Filament\Clusters\CustomerManagement\Resources\UserResource\Pages;

use App\Filament\Clusters\CustomerManagement\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Split;
use Filament\Infolists\Components\Grid;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    public function getTitle(): string
    {
        return 'User Profile: ' . $this->record->name;
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Split::make([
                    Section::make('User Information')
                        ->schema([
                            TextEntry::make('name')
                                ->label('Full Name')
                                ->weight('bold')
                                ->size('lg'),
                            
                            TextEntry::make('email')
                                ->label('Email Address')
                                ->copyable()
                                ->icon('heroicon-o-envelope'),
                            
                            TextEntry::make('username')
                                ->label('Username')
                                ->placeholder('Not set')
                                ->icon('heroicon-o-user'),
                            
                            TextEntry::make('role')
                                ->label('Role')
                                ->badge()
                                ->color(fn (string $state): string => match ($state) {
                                    'admin' => 'danger',
                                    'customer' => 'primary',
                                    default => 'gray',
                                }),
                            
                            IconEntry::make('is_active')
                                ->label('Account Status')
                                ->boolean()
                                ->trueIcon('heroicon-o-check-circle')
                                ->falseIcon('heroicon-o-x-circle')
                                ->trueColor('success')
                                ->falseColor('danger'),
                        ])
                        ->columns(2),
                    
                    Section::make('Telegram Integration')
                        ->schema([
                            TextEntry::make('telegram_username')
                                ->label('Telegram Username')
                                ->placeholder('Not linked')
                                ->prefix('@')
                                ->icon('heroicon-o-chat-bubble-left-right'),
                            
                            TextEntry::make('telegram_chat_id')
                                ->label('Telegram Chat ID')
                                ->placeholder('Not linked')
                                ->copyable(),
                            
                            TextEntry::make('telegram_first_name')
                                ->label('Telegram First Name')
                                ->placeholder('Not available'),
                            
                            TextEntry::make('telegram_last_name')
                                ->label('Telegram Last Name')
                                ->placeholder('Not available'),
                        ])
                        ->columns(2),
                ])->from('md'),
                
                Section::make('Activity & Statistics')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('orders_count')
                                    ->label('Total Orders')
                                    ->state(fn ($record) => $record->orders()->count())
                                    ->badge()
                                    ->color('info'),
                                
                                TextEntry::make('active_services')
                                    ->label('Active Services')
                                    ->state(fn ($record) => $record->clients()->where('status', 'active')->count())
                                    ->badge()
                                    ->color('success'),
                                
                                TextEntry::make('wallet_balance')
                                    ->label('Wallet Balance')
                                    ->state(fn ($record) => '$' . number_format($record->wallet?->balance ?? 0, 2))
                                    ->badge()
                                    ->color('warning'),
                            ]),
                    ]),
                
                Section::make('Account Timeline')
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('Account Created')
                            ->dateTime()
                            ->since()
                            ->icon('heroicon-o-user-plus'),
                        
                        TextEntry::make('last_login_at')
                            ->label('Last Login')
                            ->dateTime()
                            ->since()
                            ->placeholder('Never logged in')
                            ->icon('heroicon-o-arrow-right-on-rectangle'),
                        
                        TextEntry::make('updated_at')
                            ->label('Last Updated')
                            ->dateTime()
                            ->since()
                            ->icon('heroicon-o-pencil'),
                    ])
                    ->columns(3),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Edit User')
                ->icon('heroicon-o-pencil'),
            
            Actions\DeleteAction::make()
                ->label('Delete User')
                ->icon('heroicon-o-trash')
                ->requiresConfirmation()
                ->modalHeading('Delete User')
                ->modalDescription('Are you sure you want to delete this user? This action cannot be undone.')
                ->modalSubmitActionLabel('Yes, delete user'),
            
            Actions\Action::make('impersonate')
                ->label('Impersonate User')
                ->icon('heroicon-o-eye')
                ->color('warning')
                ->visible(fn (): bool => $this->record->role === 'customer')
                ->action(function () {
                    // Implement impersonation logic here
                    \Filament\Notifications\Notification::make()
                        ->title('Impersonation Started')
                        ->body("Now viewing as: {$this->record->name}")
                        ->warning()
                        ->send();
                })
                ->requiresConfirmation()
                ->modalHeading('Impersonate User')
                ->modalDescription('You will be logged in as this user. Use this feature carefully for customer support purposes only.'),
        ];
    }
}
