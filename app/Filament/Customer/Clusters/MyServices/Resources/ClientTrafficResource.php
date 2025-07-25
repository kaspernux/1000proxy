<?php

namespace App\Filament\Customer\Clusters\MyServices\Resources;

use App\Filament\Customer\Clusters\MyServices;
use App\Models\ClientTraffic;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Colors\Color;
use App\Filament\Customer\Clusters\MyServices\Resources\ClientTrafficResource\Pages;

class ClientTrafficResource extends Resource
{
    protected static ?string $model = ClientTraffic::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $cluster = MyServices::class;

    protected static ?string $navigationLabel = 'Traffic Monitor';

    protected static ?string $modelLabel = 'Client Traffic';

    protected static ?string $pluralModelLabel = 'Client Traffic';

    protected static ?int $navigationSort = 1;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        $customerId = auth('customer')->id();

        return parent::getEloquentQuery()
            ->whereHas('serverClient', function ($query) use ($customerId) {
                $query->where('customer_id', $customerId);
            })
            ->with(['serverClient', 'serverClient.server', 'serverClient.serverInbound']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Form fields would go here but customers can't edit traffic data
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('serverClient.email')
                    ->label('Client Email')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Medium),

                Tables\Columns\TextColumn::make('serverClient.server.name')
                    ->label('Server')
                    ->searchable()
                    ->sortable()
                    ->color(Color::Blue),

                Tables\Columns\TextColumn::make('serverClient.serverInbound.protocol')
                    ->label('Protocol')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'vmess' => 'success',
                        'vless' => 'info',
                        'trojan' => 'warning',
                        'shadowsocks' => 'gray',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('total_up')
                    ->label('Total Upload')
                    ->formatStateUsing(fn ($state) => self::formatBytes($state))
                    ->sortable()
                    ->color(Color::Green),

                Tables\Columns\TextColumn::make('total_down')
                    ->label('Total Download')
                    ->formatStateUsing(fn ($state) => self::formatBytes($state))
                    ->sortable()
                    ->color(Color::Orange),

                Tables\Columns\TextColumn::make('total_traffic')
                    ->label('Total Traffic')
                    ->formatStateUsing(fn ($state) => self::formatBytes($state))
                    ->sortable()
                    ->weight(FontWeight::Bold)
                    ->color(Color::Purple),

                Tables\Columns\TextColumn::make('serverClient.enable')
                    ->label('Status')
                    ->badge()
                    ->color(fn (bool $state): string => $state ? 'success' : 'danger')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Active' : 'Inactive'),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->color(Color::Gray),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('serverClient.server_id')
                    ->label('Server')
                    ->relationship('serverClient.server', 'name')
                    ->preload(),

                Tables\Filters\SelectFilter::make('serverClient.protocol')
                    ->label('Protocol')
                    ->options([
                        'vmess' => 'VMess',
                        'vless' => 'VLESS',
                        'trojan' => 'Trojan',
                        'shadowsocks' => 'Shadowsocks',
                    ]),

                Tables\Filters\Filter::make('active_only')
                    ->label('Active Clients Only')
                    ->query(fn (Builder $query): Builder => $query->whereHas('serverClient', fn ($q) => $q->where('enable', true)))
                    ->default(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([])
            ->defaultSort('updated_at', 'desc')
            ->poll('30s'); // Auto-refresh every 30 seconds
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Grid::make(3)
                    ->schema([
                        Infolists\Components\Card::make([
                            Infolists\Components\TextEntry::make('serverClient.email')
                                ->label('Client Email')
                                ->weight(FontWeight::Bold)
                                ->color(Color::Blue),

                            Infolists\Components\TextEntry::make('serverClient.server.name')
                                ->label('Server')
                                ->weight(FontWeight::Medium),

                            Infolists\Components\TextEntry::make('serverClient.serverInbound.protocol')
                                ->label('Protocol')
                                ->badge()
                                ->color(fn (string $state): string => match ($state) {
                                    'vmess' => 'success',
                                    'vless' => 'info',
                                    'trojan' => 'warning',
                                    'shadowsocks' => 'gray',
                                    default => 'gray',
                                }),
                        ])->columnSpan(1),

                        Infolists\Components\Card::make([
                            Infolists\Components\TextEntry::make('total_up')
                                ->label('Total Upload')
                                ->formatStateUsing(fn ($state) => self::formatBytes($state))
                                ->color(Color::Green)
                                ->weight(FontWeight::Bold),

                            Infolists\Components\TextEntry::make('total_down')
                                ->label('Total Download')
                                ->formatStateUsing(fn ($state) => self::formatBytes($state))
                                ->color(Color::Orange)
                                ->weight(FontWeight::Bold),

                            Infolists\Components\TextEntry::make('total_traffic')
                                ->label('Total Traffic')
                                ->formatStateUsing(fn ($state) => self::formatBytes($state))
                                ->color(Color::Purple)
                                ->weight(FontWeight::Bold)
                                ->size('lg'),
                        ])->columnSpan(1),

                        Infolists\Components\Card::make([
                            Infolists\Components\TextEntry::make('serverClient.enable')
                                ->label('Client Status')
                                ->badge()
                                ->color(fn (bool $state): string => $state ? 'success' : 'danger')
                                ->formatStateUsing(fn (bool $state): string => $state ? 'Active' : 'Inactive'),

                            Infolists\Components\TextEntry::make('updated_at')
                                ->label('Last Updated')
                                ->dateTime()
                                ->since(),

                            Infolists\Components\TextEntry::make('created_at')
                                ->label('Created')
                                ->dateTime()
                                ->since(),
                        ])->columnSpan(1),
                    ]),

                Infolists\Components\Section::make('Traffic Details')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('serverClient.total_gb')
                                    ->label('Traffic Limit')
                                    ->formatStateUsing(fn ($state) => $state > 0 ? self::formatBytes($state) : 'Unlimited')
                                    ->color(fn ($state) => $state > 0 ? Color::Amber : Color::Green),

                                Infolists\Components\TextEntry::make('traffic_usage_percentage')
                                    ->label('Usage Percentage')
                                    ->formatStateUsing(function ($record) {
                                        if ($record->serverClient->total_gb <= 0) {
                                            return 'Unlimited';
                                        }
                                        $percentage = ($record->total_traffic / $record->serverClient->total_gb) * 100;
                                        return number_format($percentage, 2) . '%';
                                    })
                                    ->color(function ($record) {
                                        if ($record->serverClient->total_gb <= 0) {
                                            return Color::Green;
                                        }
                                        $percentage = ($record->total_traffic / $record->serverClient->total_gb) * 100;
                                        if ($percentage >= 90) return Color::Red;
                                        if ($percentage >= 75) return Color::Orange;
                                        if ($percentage >= 50) return Color::Amber;
                                        return Color::Green;
                                    }),
                            ]),
                    ]),

                Infolists\Components\Section::make('Server Information')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('serverClient.server.ip')
                                    ->label('Server IP')
                                    ->copyable(),

                                Infolists\Components\TextEntry::make('serverClient.serverInbound.port')
                                    ->label('Port')
                                    ->badge(),

                                Infolists\Components\TextEntry::make('serverClient.server.location')
                                    ->label('Location'),
                            ]),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClientTraffics::route('/'),
            'view' => Pages\ViewClientTraffic::route('/{record}'),
        ];
    }

    protected static function formatBytes($bytes): string
    {
        if ($bytes == 0) return '0 B';

        $k = 1024;
        $dm = 2;
        $sizes = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];

        $i = floor(log($bytes) / log($k));

        return round($bytes / pow($k, $i), $dm) . ' ' . $sizes[$i];
    }
}
