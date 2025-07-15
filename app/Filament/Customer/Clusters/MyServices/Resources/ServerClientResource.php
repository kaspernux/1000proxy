<?php

namespace App\Filament\Customer\Clusters\MyServices\Resources;

use App\Filament\Customer\Clusters\MyServices;
use App\Models\ServerClient;
use App\Services\QrCodeService;
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
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Tabs;
use Filament\Infolists\Components\Group;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use App\Filament\Customer\Clusters\MyServices\Resources\ServerClientResource\Pages;

class ServerClientResource extends Resource
{
    protected static ?string $model = ServerClient::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $cluster = MyServices::class;

    protected static ?string $navigationLabel = 'My Clients';

    protected static ?string $modelLabel = 'Proxy Client';

    protected static ?string $pluralModelLabel = 'Proxy Clients';

    protected static ?int $navigationSort = 1;

    public static function canCreate(): bool
    {
        return false; // Clients are created through orders
    }

    public static function canEdit($record): bool
    {
        return false; // Customers can't edit client configurations
    }

    public static function canDelete($record): bool
    {
        return false; // Customers can't delete clients directly
    }

    public static function getEloquentQuery(): Builder
    {
        $customerId = auth('customer')->id();

        return parent::getEloquentQuery()
            ->where('customer_id', $customerId)
            ->with(['server', 'serverInbound', 'order', 'clientTraffic']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Form fields would go here but customers can't edit client data
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('email')
                    ->label('Client Email')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Medium)
                    ->copyable(),

                Tables\Columns\TextColumn::make('server.name')
                    ->label('Server')
                    ->searchable()
                    ->sortable()
                    ->color(Color::Blue),

                Tables\Columns\TextColumn::make('serverInbound.protocol')
                    ->label('Protocol')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'vmess' => 'success',
                        'vless' => 'info',
                        'trojan' => 'warning',
                        'shadowsocks' => 'gray',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('serverInbound.port')
                    ->label('Port')
                    ->badge()
                    ->color(Color::Gray),

                Tables\Columns\IconColumn::make('enable')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('total_gb')
                    ->label('Traffic Limit')
                    ->formatStateUsing(fn ($state) => $state > 0 ? self::formatBytes($state) : 'Unlimited')
                    ->color(fn ($state) => $state > 0 ? Color::Amber : Color::Green),

                Tables\Columns\TextColumn::make('current_traffic')
                    ->label('Used Traffic')
                    ->formatStateUsing(function ($record) {
                        $traffic = $record->clientTraffic?->total_traffic ?? 0;
                        return self::formatBytes($traffic);
                    })
                    ->color(Color::Purple),

                Tables\Columns\TextColumn::make('usage_percentage')
                    ->label('Usage %')
                    ->formatStateUsing(function ($record) {
                        if ($record->total_gb <= 0) return 'Unlimited';
                        $traffic = $record->clientTraffic?->total_traffic ?? 0;
                        $percentage = ($traffic / $record->total_gb) * 100;
                        return number_format($percentage, 1) . '%';
                    })
                    ->color(function ($record) {
                        if ($record->total_gb <= 0) return Color::Green;
                        $traffic = $record->clientTraffic?->total_traffic ?? 0;
                        $percentage = ($traffic / $record->total_gb) * 100;
                        if ($percentage >= 90) return Color::Red;
                        if ($percentage >= 75) return Color::Orange;
                        if ($percentage >= 50) return Color::Amber;
                        return Color::Green;
                    }),

                Tables\Columns\TextColumn::make('expiry_time')
                    ->label('Expires')
                    ->dateTime()
                    ->since()
                    ->placeholder('Never')
                    ->color(function ($state) {
                        if (!$state) return Color::Green;
                        $expiry = \Carbon\Carbon::parse($state);
                        $now = now();
                        if ($expiry->isPast()) return Color::Red;
                        if ($expiry->diffInDays($now) <= 7) return Color::Orange;
                        if ($expiry->diffInDays($now) <= 30) return Color::Amber;
                        return Color::Green;
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('server_id')
                    ->label('Server')
                    ->relationship('server', 'name')
                    ->preload(),

                Tables\Filters\SelectFilter::make('protocol')
                    ->label('Protocol')
                    ->options([
                        'vmess' => 'VMess',
                        'vless' => 'VLESS',
                        'trojan' => 'Trojan',
                        'shadowsocks' => 'Shadowsocks',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if ($data['value']) {
                            return $query->whereHas('serverInbound', function ($q) use ($data) {
                                $q->where('protocol', $data['value']);
                            });
                        }
                        return $query;
                    }),

                Tables\Filters\Filter::make('active_only')
                    ->label('Active Clients Only')
                    ->query(fn (Builder $query): Builder => $query->where('enable', true))
                    ->default(),

                Tables\Filters\Filter::make('expiring_soon')
                    ->label('Expiring Soon (7 days)')
                    ->query(fn (Builder $query): Builder => $query->where('expiry_time', '<=', now()->addDays(7))
                        ->where('expiry_time', '>', now())),

                Tables\Filters\Filter::make('expired')
                    ->label('Expired')
                    ->query(fn (Builder $query): Builder => $query->where('expiry_time', '<', now())),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Action::make('download_config')
                    ->label('Download Config')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->url(fn ($record) => route('customer.download-config', $record->id))
                    ->openUrlInNewTab(),

                Action::make('view_qr')
                    ->label('QR Code')
                    ->icon('heroicon-o-qr-code')
                    ->color('info')
                    ->modalContent(function ($record) {
                        $link = $record->buildClientLink();
                        $qrCodeService = app(QrCodeService::class);
                        $qrCodeBase64 = $qrCodeService->generateClientQrCode($link, [
                            'colorScheme' => 'primary',
                            'style' => 'dot',
                            'eye' => 'circle'
                        ]);

                        return new \Illuminate\Support\HtmlString('
                            <div class="text-center">
                                <div class="mb-4">
                                    <img src="' . $qrCodeBase64 . '" alt="QR Code" class="mx-auto" />
                                </div>
                                <p class="text-sm text-gray-600 font-mono break-all">' . $link . '</p>
                            </div>
                        ');
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc')
            ->poll('60s'); // Auto-refresh every minute
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Grid::make(3)
                    ->schema([
                        Infolists\Components\Card::make([
                            Infolists\Components\TextEntry::make('email')
                                ->label('Client Email')
                                ->weight(FontWeight::Bold)
                                ->color(Color::Blue)
                                ->copyable(),

                            Infolists\Components\TextEntry::make('uuid')
                                ->label('Client UUID')
                                ->fontFamily('mono')
                                ->copyable(),

                            Infolists\Components\IconEntry::make('enable')
                                ->label('Status')
                                ->boolean()
                                ->trueIcon('heroicon-o-check-circle')
                                ->falseIcon('heroicon-o-x-circle')
                                ->trueColor('success')
                                ->falseColor('danger'),
                        ])->columnSpan(1),

                        Infolists\Components\Card::make([
                            Infolists\Components\TextEntry::make('server.name')
                                ->label('Server')
                                ->weight(FontWeight::Medium)
                                ->color(Color::Blue),

                            Infolists\Components\TextEntry::make('serverInbound.protocol')
                                ->label('Protocol')
                                ->badge()
                                ->color(fn (string $state): string => match ($state) {
                                    'vmess' => 'success',
                                    'vless' => 'info',
                                    'trojan' => 'warning',
                                    'shadowsocks' => 'gray',
                                    default => 'gray',
                                }),

                            Infolists\Components\TextEntry::make('serverInbound.port')
                                ->label('Port')
                                ->badge(),
                        ])->columnSpan(1),

                        Infolists\Components\Card::make([
                            Infolists\Components\TextEntry::make('total_gb')
                                ->label('Traffic Limit')
                                ->formatStateUsing(fn ($state) => $state > 0 ? self::formatBytes($state) : 'Unlimited')
                                ->color(fn ($state) => $state > 0 ? Color::Amber : Color::Green),

                            Infolists\Components\TextEntry::make('expiry_time')
                                ->label('Expires')
                                ->dateTime()
                                ->placeholder('Never')
                                ->color(function ($state) {
                                    if (!$state) return Color::Green;
                                    $expiry = \Carbon\Carbon::parse($state);
                                    if ($expiry->isPast()) return Color::Red;
                                    if ($expiry->diffInDays(now()) <= 7) return Color::Orange;
                                    return Color::Green;
                                }),

                            Infolists\Components\TextEntry::make('created_at')
                                ->label('Created')
                                ->dateTime()
                                ->since(),
                        ])->columnSpan(1),
                    ]),

                Infolists\Components\Section::make('Traffic Statistics')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('current_upload')
                                    ->label('Total Upload')
                                    ->formatStateUsing(function ($record) {
                                        $traffic = $record->clientTraffic?->total_up ?? 0;
                                        return self::formatBytes($traffic);
                                    })
                                    ->color(Color::Green),

                                Infolists\Components\TextEntry::make('current_download')
                                    ->label('Total Download')
                                    ->formatStateUsing(function ($record) {
                                        $traffic = $record->clientTraffic?->total_down ?? 0;
                                        return self::formatBytes($traffic);
                                    })
                                    ->color(Color::Orange),

                                Infolists\Components\TextEntry::make('current_total')
                                    ->label('Total Traffic')
                                    ->formatStateUsing(function ($record) {
                                        $traffic = $record->clientTraffic?->total_traffic ?? 0;
                                        return self::formatBytes($traffic);
                                    })
                                    ->color(Color::Purple)
                                    ->weight(FontWeight::Bold),
                            ]),
                    ]),

                Infolists\Components\Section::make('Connection Information')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('server.ip')
                                    ->label('Server IP')
                                    ->copyable(),

                                Infolists\Components\TextEntry::make('server.location')
                                    ->label('Server Location'),
                            ]),
                    ]),

                Infolists\Components\Section::make('Configuration')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('flow')
                                    ->label('Flow')
                                    ->placeholder('None'),

                                Infolists\Components\TextEntry::make('sub_id')
                                    ->label('Subscription ID')
                                    ->fontFamily('mono')
                                    ->copyable(),
                            ]),
                    ]),

                Infolists\Components\Section::make('Actions')
                    ->schema([
                        Infolists\Components\Actions::make([
                            Infolists\Components\Actions\Action::make('download_config')
                                ->label('Download Configuration')
                                ->icon('heroicon-o-arrow-down-tray')
                                ->color('success')
                                ->url(fn ($record) => route('customer.download-config', $record->id))
                                ->openUrlInNewTab(),

                            Infolists\Components\Actions\Action::make('copy_link')
                                ->label('Copy Connection Link')
                                ->icon('heroicon-o-link')
                                ->color('info')
                                ->action(function ($record) {
                                    $link = $record->buildClientLink();
                                    return redirect()->back()->with('link', $link);
                                }),

                            Infolists\Components\Actions\Action::make('view_subscription')
                                ->label('Subscription Link')
                                ->icon('heroicon-o-rss')
                                ->color('warning')
                                ->url(fn ($record) => route('customer.subscription', ['sub_id' => $record->sub_id]))
                                ->openUrlInNewTab()
                                ->visible(fn ($record) => !empty($record->sub_id)),
                        ])
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServerClients::route('/'),
            'view' => Pages\ViewServerClient::route('/{record}'),
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
