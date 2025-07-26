<?php

namespace App\Filament\Customer\Clusters\MyServices\Resources;

use App\Filament\Customer\Clusters\MyServices;
use App\Filament\Customer\Clusters\MyServices\Resources\ServerInboundResource\Pages;
use App\Models\ServerInbound;
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

class ServerInboundResource extends Resource
{
    protected static ?string $model = ServerInbound::class;

    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';

    protected static ?string $cluster = MyServices::class;

    protected static ?string $navigationLabel = 'My Inbounds';

    protected static ?string $pluralLabel = 'My Inbounds';

    protected static ?string $recordTitleAttribute = 'remark';

    protected static ?int $navigationSort = 2;

    // Only show inbounds from servers where customer has active clients
    public static function getEloquentQuery(): Builder
    {
        $customerId = auth('customer')->id();

        return parent::getEloquentQuery()
            ->whereHas('server.clients', function (Builder $query) use ($customerId) {
                $query->where('email', 'LIKE', "%#ID {$customerId}");
            })
            ->where('enable', true);
    }

    public static function canCreate(): bool
    {
        return false; // Customers cannot create inbounds
    }

    public static function canEdit($record): bool
    {
        return false; // Customers cannot edit inbounds
    }

    public static function canDelete($record): bool
    {
        return false; // Customers cannot delete inbounds
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('remark')
                    ->label('Inbound Name')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                TextColumn::make('protocol')
                    ->label('Protocol')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'vless' => 'success',
                        'vmess' => 'info',
                        'trojan' => 'warning',
                        'shadowsocks' => 'primary',
                        default => 'gray',
                    }),

                TextColumn::make('port')
                    ->label('Port')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('server.name')
                    ->label('Server')
                    ->badge()
                    ->color('info')
                    ->searchable(),

                TextColumn::make('my_clients_count')
                    ->label('My Clients')
                    ->badge()
                    ->color('success')
                    ->getStateUsing(function ($record) {
                        $customerId = auth('customer')->id();
                        return $record->clients()
                            ->where('email', 'LIKE', "%#ID {$customerId}")
                            ->count();
                    }),

                IconColumn::make('enable')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                TextColumn::make('expiry_time')
                    ->label('Expires')
                    ->dateTime()
                    ->since()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('protocol')
                    ->options([
                        'vless' => 'VLESS',
                        'vmess' => 'VMess',
                        'trojan' => 'Trojan',
                        'shadowsocks' => 'Shadowsocks',
                    ])
                    ->multiple(),

                SelectFilter::make('server_id')
                    ->relationship('server', 'name')
                    ->label('Server')
                    ->preload(),

                Tables\Filters\TernaryFilter::make('enable')
                    ->label('Status')
                    ->placeholder('All')
                    ->trueLabel('Active')
                    ->falseLabel('Inactive'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Action::make('view_my_clients')
                    ->label('My Clients')
                    ->icon('heroicon-o-users')
                    ->color('success')
                    ->url(fn ($record) => route('filament.customer.resources.customer-server-clients.index', [
                        'filters' => ['server_inbound_id' => $record->id]
                    ]))
                    ->tooltip('View my clients on this inbound'),
            ])
            ->bulkActions([
                // No bulk actions for customers
            ])
            ->defaultSort('remark', 'asc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Tabs::make('Inbound Information')
                    ->tabs([
                        Tabs\Tab::make('Basic Information')
                            ->icon('heroicon-m-information-circle')
                            ->schema([
                                Section::make('🌐 Inbound Details')
                                    ->description('Basic information about this inbound.')
                                    ->columns(2)
                                    ->schema([
                                        TextEntry::make('remark')
                                            ->label('Inbound Name')
                                            ->color('primary'),

                                        TextEntry::make('protocol')
                                            ->label('Protocol')
                                            ->badge()
                                            ->color('success'),

                                        TextEntry::make('port')
                                            ->label('Port')
                                            ->badge(),

                                        TextEntry::make('server.name')
                                            ->label('Server')
                                            ->color('info'),

                                        IconEntry::make('enable')
                                            ->label('Active Status')
                                            ->boolean(),

                                        TextEntry::make('expiry_time')
                                            ->label('Expires At')
                                            ->dateTime(),
                                    ]),

                                Section::make('🔧 Configuration')
                                    ->description('Technical configuration details.')
                                    ->columns(2)
                                    ->schema([
                                        TextEntry::make('listen')
                                            ->label('Listen Address')
                                            ->default('0.0.0.0'),

                                        TextEntry::make('network')
                                            ->label('Network Type')
                                            ->default('tcp'),

                                        TextEntry::make('security')
                                            ->label('Security')
                                            ->default('none'),

                                        TextEntry::make('created_at')
                                            ->label('Created')
                                            ->dateTime(),
                                    ]),
                            ]),

                        Tabs\Tab::make('My Services')
                            ->icon('heroicon-m-user-group')
                            ->schema([
                                Section::make('👥 My Active Clients')
                                    ->description('Your proxy clients on this inbound.')
                                    ->schema([
                                        Grid::make(1)
                                            ->schema([
                                                TextEntry::make('my_clients_summary')
                                                    ->label('Summary')
                                                    ->getStateUsing(function ($record) {
                                                        $customerId = auth('customer')->id();
                                                        $clients = $record->clients()
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
                                Section::make('📈 Inbound Statistics')
                                    ->description('Inbound performance and usage statistics.')
                                    ->columns(3)
                                    ->schema([
                                        TextEntry::make('total_clients_count')
                                            ->label('Total Clients')
                                            ->getStateUsing(fn ($record) => $record->clients()->count()),

                                        TextEntry::make('total_up')
                                            ->label('Total Upload')
                                            ->formatStateUsing(fn (?int $state): string =>
                                                $state ? number_format($state / 1073741824, 2) . ' GB' : '0 GB'),

                                        TextEntry::make('total_down')
                                            ->label('Total Download')
                                            ->formatStateUsing(fn (?int $state): string =>
                                                $state ? number_format($state / 1073741824, 2) . ' GB' : '0 GB'),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServerInbounds::route('/'),
            'view' => Pages\ViewServerInbound::route('/{record}'),
        ];
    }
}
