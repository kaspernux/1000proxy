<?php

namespace App\Filament\Customer\Clusters\MyServices\Resources;

use App\Filament\Customer\Clusters\MyServices;
use App\Filament\Customer\Clusters\MyServices\Resources\ServerResource\Pages;
use App\Models\Server;
use App\Models\ServerClient;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\Action;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Tabs;
use Filament\Support\Colors\Color;

class ServerResource extends Resource
{
    protected static ?string $model = Server::class;

    protected static ?string $navigationIcon = 'heroicon-o-server';

    protected static ?string $cluster = MyServices::class;

    protected static ?string $navigationLabel = 'My Servers';

    protected static ?string $pluralLabel = 'My Servers';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 1;

    // Only show servers where customer has active clients
    public static function getEloquentQuery(): Builder
    {
        $customerId = auth('customer')->id();

        return parent::getEloquentQuery()
            ->whereHas('serverClients', function (Builder $query) use ($customerId) {
                $query->where('email', 'LIKE', "%#ID {$customerId}");
            })
            ->where('is_active', true);
    }

    public static function canCreate(): bool
    {
        return false; // Customers cannot create servers
    }

    public static function canEdit($record): bool
    {
        return false; // Customers cannot edit servers
    }

    public static function canDelete($record): bool
    {
        return false; // Customers cannot delete servers
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Server Name')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                BadgeColumn::make('type')
                    ->label('Panel Type')
                    ->colors([
                        'primary' => 'sanaei',
                        'success' => 'alireza',
                        'warning' => 'mhsanaei',
                    ]),

                TextColumn::make('serverBrand.name')
                    ->label('Provider')
                    ->badge()
                    ->color('info')
                    ->default('Unknown'),

                TextColumn::make('location')
                    ->label('Location')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (?string $state): string => $state ? strtoupper($state) : 'Unknown'),

                TextColumn::make('my_clients_count')
                    ->label('My Clients')
                    ->badge()
                    ->color('success')
                    ->getStateUsing(function ($record) {
                        $customerId = auth('customer')->id();
                        return $record->serverClients()
                            ->where('email', 'LIKE', "%#ID {$customerId}")
                            ->count();
                    }),

                IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                TextColumn::make('last_api_sync_at')
                    ->label('Last Sync')
                    ->dateTime()
                    ->since()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options([
                        'sanaei' => 'X-RAY (3X-UI Sanaei)',
                        'alireza' => 'X-RAY (3X-UI Alireza)',
                        'mhsanaei' => 'X-RAY (3X-UI MHSanaei)',
                    ]),

                SelectFilter::make('server_brand_id')
                    ->relationship('serverBrand', 'name')
                    ->label('Provider')
                    ->preload(),

                SelectFilter::make('location')
                    ->options([
                        'us' => 'United States',
                        'eu' => 'Europe',
                        'as' => 'Asia',
                        'ca' => 'Canada',
                        'au' => 'Australia',
                    ])
                    ->multiple(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Action::make('view_my_clients')
                    ->label('My Clients')
                    ->icon('heroicon-o-users')
                    ->color('success')
                    ->url(fn ($record) => route('filament.customer.resources.customer-server-clients.index', [
                        'filters' => ['server_id' => $record->id]
                    ]))
                    ->tooltip('View my clients on this server'),
            ])
            ->bulkActions([
                // No bulk actions for customers
            ])
            ->defaultSort('name', 'asc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Tabs::make('Server Information')
                    ->tabs([
                        Tabs\Tab::make('Basic Information')
                            ->icon('heroicon-m-information-circle')
                            ->schema([
                                Section::make('🖥️ Server Details')
                                    ->description('Basic information about this server.')
                                    ->columns(2)
                                    ->schema([
                                        TextEntry::make('name')
                                            ->label('Server Name')
                                            ->color('primary'),

                                        TextEntry::make('type')
                                            ->label('Panel Type')
                                            ->badge()
                                            ->color('success'),

                                        TextEntry::make('location')
                                            ->label('Location')
                                            ->formatStateUsing(fn (?string $state): string => $state ? strtoupper($state) : 'Unknown'),

                                        TextEntry::make('serverBrand.name')
                                            ->label('Provider')
                                            ->default('Unknown Provider'),

                                        TextEntry::make('serverCategory.name')
                                            ->label('Category')
                                            ->default('General'),

                                        IconEntry::make('is_active')
                                            ->label('Active Status')
                                            ->boolean(),
                                    ]),

                                Section::make('📊 Connection Information')
                                    ->description('Server connection details.')
                                    ->columns(2)
                                    ->schema([
                                        TextEntry::make('host')
                                            ->label('Server Host')
                                            ->copyable(),

                                        TextEntry::make('port')
                                            ->label('Main Port'),

                                        TextEntry::make('port_type')
                                            ->label('Port Type')
                                            ->badge(),

                                        TextEntry::make('last_api_sync_at')
                                            ->label('Last Sync')
                                            ->dateTime(),
                                    ]),
                            ]),

                        Tabs\Tab::make('My Services')
                            ->icon('heroicon-m-user-group')
                            ->schema([
                                Section::make('👥 My Active Clients')
                                    ->description('Your proxy clients on this server.')
                                    ->schema([
                                        Grid::make(1)
                                            ->schema([
                                                TextEntry::make('my_clients_summary')
                                                    ->label('Summary')
                                                    ->getStateUsing(function ($record) {
                                                        $customerId = auth('customer')->id();
                                                        $clients = $record->serverClients()
                                                            ->where('email', 'LIKE', "%#ID {$customerId}")
                                                            ->get();

                                                        $active = $clients->where('enable', true)->count();
                                                        $total = $clients->count();

                                                        return "Active: {$active} / Total: {$total}";
                                                    })
                                                    ->color('success'),
                                            ]),
                                    ]),
                            ]),

                        Tabs\Tab::make('Statistics')
                            ->icon('heroicon-m-chart-bar')
                            ->schema([
                                Section::make('📈 Server Statistics')
                                    ->description('Server performance and usage statistics.')
                                    ->columns(3)
                                    ->schema([
                                        TextEntry::make('total_clients_count')
                                            ->label('Total Clients')
                                            ->getStateUsing(fn ($record) => $record->serverClients()->count()),

                                        TextEntry::make('active_inbounds_count')
                                            ->label('Active Inbounds')
                                            ->getStateUsing(fn ($record) => $record->serverInbounds()->where('enable', true)->count()),

                                        TextEntry::make('created_at')
                                            ->label('Server Created')
                                            ->dateTime(),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServers::route('/'),
            'view' => Pages\ViewServer::route('/{record}'),
        ];
    }
}
