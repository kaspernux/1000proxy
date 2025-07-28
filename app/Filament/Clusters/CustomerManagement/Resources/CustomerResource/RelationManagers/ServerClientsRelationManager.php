<?php

namespace App\Filament\Clusters\CustomerManagement\Resources\CustomerResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\ServerClient;
use Filament\Forms\Components\Section;
use Filament\Tables\Columns\BadgeColumn;

class ServerClientsRelationManager extends RelationManager
{
    protected static string $relationship = 'clients';
    protected static ?string $title = 'Proxy Services';
    protected static ?string $modelLabel = 'Proxy Service';
    protected static ?string $pluralModelLabel = 'Proxy Services';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Service Information')
                    ->schema([
                        Forms\Components\Select::make('server_id')
                            ->label('Server')
                            ->relationship('server', 'name')
                            ->required()
                            ->searchable(),
                        
                        Forms\Components\TextInput::make('client_id')
                            ->label('Client ID')
                            ->required()
                            ->maxLength(255),
                        
                        Forms\Components\Select::make('status')
                            ->options([
                                'active' => 'Active',
                                'expired' => 'Expired',
                                'suspended' => 'Suspended',
                                'cancelled' => 'Cancelled',
                            ])
                            ->required(),
                        
                        Forms\Components\DateTimePicker::make('expires_at')
                            ->label('Expires At'),
                    ])
                    ->columns(2),
                
                Section::make('Configuration')
                    ->schema([
                        Forms\Components\Textarea::make('config_url')
                            ->label('Configuration URL')
                            ->rows(3)
                            ->columnSpanFull(),
                        
                        Forms\Components\Textarea::make('qr_code')
                            ->label('QR Code Data')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('client_id')
            ->columns([
                Tables\Columns\TextColumn::make('server.name')
                    ->label('Server')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                
                Tables\Columns\TextColumn::make('client_id')
                    ->label('Client ID')
                    ->searchable()
                    ->limit(20)
                    ->copyable()
                    ->tooltip('Click to copy'),
                
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => 'active',
                        'warning' => 'expired',
                        'danger' => 'suspended',
                        'gray' => 'cancelled',
                    ]),
                
                Tables\Columns\TextColumn::make('server.location')
                    ->label('Location')
                    ->icon('heroicon-o-map-pin')
                    ->color('info'),
                
                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Expires')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->color(fn ($record) => $record->expires_at && $record->expires_at->isPast() ? 'danger' : 'success'),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'expired' => 'Expired',
                        'suspended' => 'Suspended',
                        'cancelled' => 'Cancelled',
                    ]),
                
                Tables\Filters\SelectFilter::make('server')
                    ->relationship('server', 'name')
                    ->searchable(),
                
                Tables\Filters\Filter::make('expiring_soon')
                    ->label('Expiring Soon')
                    ->query(fn (Builder $query): Builder => 
                        $query->where('expires_at', '<=', now()->addDays(7))
                              ->where('expires_at', '>', now())
                    )
                    ->toggle(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\Action::make('view_config')
                    ->label('View Config')
                    ->icon('heroicon-o-qr-code')
                    ->color('info')
                    ->modalContent(function ($record) {
                        return view('filament.modal.server-client-config', ['record' => $record]);
                    })
                    ->modalWidth('lg'),
                
                Tables\Actions\Action::make('extend_service')
                    ->label('Extend')
                    ->icon('heroicon-o-calendar-days')
                    ->color('success')
                    ->form([
                        Forms\Components\Select::make('extend_period')
                            ->label('Extension Period')
                            ->options([
                                '7' => '7 Days',
                                '30' => '30 Days',
                                '90' => '90 Days',
                                '365' => '365 Days',
                            ])
                            ->required(),
                    ])
                    ->action(function ($record, array $data) {
                        $days = (int) $data['extend_period'];
                        $newExpiry = $record->expires_at ? 
                            $record->expires_at->addDays($days) : 
                            now()->addDays($days);
                        
                        $record->update([
                            'expires_at' => $newExpiry,
                            'status' => 'active'
                        ]);
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Service Extended')
                            ->body("Service extended by {$days} days")
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation(),
                
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('suspend')
                        ->label('Suspend Selected')
                        ->icon('heroicon-o-pause')
                        ->color('warning')
                        ->action(fn ($records) => $records->each(fn ($record) => $record->update(['status' => 'suspended'])))
                        ->requiresConfirmation(),
                    
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate Selected')
                        ->icon('heroicon-o-play')
                        ->color('success')
                        ->action(fn ($records) => $records->each(fn ($record) => $record->update(['status' => 'active'])))
                        ->requiresConfirmation(),
                    
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
