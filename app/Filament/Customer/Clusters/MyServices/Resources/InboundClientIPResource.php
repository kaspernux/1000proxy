<?php

namespace App\Filament\Customer\Clusters\MyServices\Resources;

use App\Filament\Customer\Clusters\MyServices;
use App\Models\InboundClientIP;
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
use App\Filament\Customer\Clusters\MyServices\Resources\InboundClientIPResource\Pages;

class InboundClientIPResource extends Resource
{
    protected static ?string $model = InboundClientIP::class;

    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';

    protected static ?string $cluster = MyServices::class;

    protected static ?string $navigationLabel = 'Client IPs';

    protected static ?string $modelLabel = 'Client IP';

    protected static ?string $pluralModelLabel = 'Client IPs';

    protected static ?int $navigationSort = 2;

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
                // Form fields would go here but customers can't edit IP data
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('client_ip')
                    ->label('Client IP')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Medium)
                    ->copyable()
                    ->color(Color::Blue),

                Tables\Columns\TextColumn::make('serverClient.email')
                    ->label('Client Email')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Medium),

                Tables\Columns\TextColumn::make('serverClient.server.name')
                    ->label('Server')
                    ->searchable()
                    ->sortable()
                    ->color(Color::Purple),

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

                Tables\Columns\TextColumn::make('serverClient.serverInbound.port')
                    ->label('Port')
                    ->badge()
                    ->color(Color::Gray),

                Tables\Columns\TextColumn::make('location')
                    ->label('Location')
                    ->placeholder('Unknown')
                    ->color(Color::Amber),

                Tables\Columns\TextColumn::make('serverClient.enable')
                    ->label('Status')
                    ->badge()
                    ->color(fn (bool $state): string => $state ? 'success' : 'danger')
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Active' : 'Inactive'),

                Tables\Columns\TextColumn::make('last_seen')
                    ->label('Last Seen')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->color(Color::Gray),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('First Seen')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),
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

                Tables\Filters\Filter::make('recent')
                    ->label('Seen Recently (24h)')
                    ->query(fn (Builder $query): Builder => $query->where('last_seen', '>=', now()->subDay())),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([])
            ->defaultSort('last_seen', 'desc')
            ->poll('60s'); // Auto-refresh every minute
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Grid::make(3)
                    ->schema([
                        Infolists\Components\Card::make([
                            Infolists\Components\TextEntry::make('client_ip')
                                ->label('Client IP Address')
                                ->weight(FontWeight::Bold)
                                ->color(Color::Blue)
                                ->copyable(),

                            Infolists\Components\TextEntry::make('location')
                                ->label('Geo Location')
                                ->placeholder('Unknown')
                                ->color(Color::Amber),

                            Infolists\Components\TextEntry::make('isp')
                                ->label('ISP')
                                ->placeholder('Unknown')
                                ->color(Color::Gray),
                        ])->columnSpan(1),

                        Infolists\Components\Card::make([
                            Infolists\Components\TextEntry::make('serverClient.email')
                                ->label('Client Email')
                                ->weight(FontWeight::Medium),

                            Infolists\Components\TextEntry::make('serverClient.server.name')
                                ->label('Server')
                                ->color(Color::Purple),

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
                            Infolists\Components\TextEntry::make('serverClient.enable')
                                ->label('Client Status')
                                ->badge()
                                ->color(fn (bool $state): string => $state ? 'success' : 'danger')
                                ->formatStateUsing(fn (bool $state): string => $state ? 'Active' : 'Inactive'),

                            Infolists\Components\TextEntry::make('last_seen')
                                ->label('Last Seen')
                                ->dateTime()
                                ->since(),

                            Infolists\Components\TextEntry::make('created_at')
                                ->label('First Seen')
                                ->dateTime()
                                ->since(),
                        ])->columnSpan(1),
                    ]),

                Infolists\Components\Section::make('Connection Details')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('serverClient.server.ip')
                                    ->label('Server IP')
                                    ->copyable(),

                                Infolists\Components\TextEntry::make('serverClient.serverInbound.port')
                                    ->label('Server Port')
                                    ->badge(),

                                Infolists\Components\TextEntry::make('serverClient.server.location')
                                    ->label('Server Location'),
                            ]),
                    ]),

                Infolists\Components\Section::make('Client Configuration')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('serverClient.uuid')
                                    ->label('Client UUID')
                                    ->copyable()
                                    ->fontFamily('mono'),

                                Infolists\Components\TextEntry::make('serverClient.flow')
                                    ->label('Flow')
                                    ->placeholder('None'),
                            ]),
                    ]),

                Infolists\Components\Section::make('Security Information')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('risk_score')
                                    ->label('Risk Score')
                                    ->placeholder('Not calculated')
                                    ->color(function ($state) {
                                        if (!$state) return Color::Gray;
                                        if ($state >= 80) return Color::Red;
                                        if ($state >= 60) return Color::Orange;
                                        if ($state >= 40) return Color::Amber;
                                        return Color::Green;
                                    }),

                                Infolists\Components\TextEntry::make('is_vpn')
                                    ->label('VPN/Proxy')
                                    ->badge()
                                    ->color(fn ($state) => $state ? 'warning' : 'success')
                                    ->formatStateUsing(fn ($state) => $state ? 'Detected' : 'Not Detected'),
                            ]),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInboundClientIPs::route('/'),
            'view' => Pages\ViewInboundClientIP::route('/{record}'),
        ];
    }
}
