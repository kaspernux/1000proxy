<?php

namespace App\Filament\Customer\Clusters\MyWallet\Resources;

use App\Filament\Customer\Clusters\MyWallet;
use App\Filament\Customer\Clusters\MyWallet\Resources\WalletResource\Pages;
use App\Models\Wallet;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Tabs;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ImageEntry;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists\Components\Card;
use Filament\Infolists\Components\Split;
use Filament\Infolists\Components\Grid;

class WalletResource extends Resource
{
    protected static ?string $model = Wallet::class;
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationLabel = 'My Wallet';
    protected static ?string $cluster = MyWallet::class;
    protected static ?string $panel = 'customer';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('customer_id', auth('customer')->id());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('balance')
                    ->label('USD Balance')
                    ->money('usd')
                    ->badge()
                    ->color('success')
                    ->sortable()
                    ->extraAttributes(['class' => 'font-bold text-success']),

                TextColumn::make('created_at')
                    ->since()
                    ->label('Created')
                    ->sortable(),
                TextColumn::make('last_synced_at')
                    ->dateTime()
                    ->label('Last Synced')
                    ->sortable()
                    ->color('gray'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('View Wallet')
                        ->color('primary')
                        ->button(),
                    Tables\Actions\Action::make('regenerateQr')
                        ->label('Regenerate QRs')
                        ->icon('heroicon-o-arrow-path')
                        ->requiresConfirmation()
                        ->action(fn (Wallet $record) => $record->generateQrCodes())
                        ->visible(fn (Wallet $record) => $record->customer_id === auth('customer')->id())
                        ->color('warning'),
                ]),
            ])
            ->headerActions([
                Action::make('topup')
                    ->label('Top-Up Wallet')
                    ->icon('heroicon-o-arrow-up-on-square')
                    ->url(fn () => url('/wallet/btc/top-up'))
                    ->openUrlInNewTab()
                    ->button()
                    ->color('success')
                    ->size('lg')
                    ->outlined(),
            ])
            ->emptyStateHeading('No Wallet Yet')
            ->emptyStateDescription('Your wallet will be created automatically after your first deposit.')
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Tabs::make('Wallet Details')
                ->persistTab()
                ->tabs([
                    Tabs\Tab::make('Overview')
                        ->icon('heroicon-o-wallet')
                        ->schema([
                            Section::make('💰 Wallet Overview')
                                ->description('Your current wallet balance and sync status.')
                                ->columns([
                                    'sm' => 1,
                                    'md' => 2,
                                ])
                                ->schema([
                                    TextEntry::make('balance')
                                        ->label('Balance (USD)')
                                        ->money('usd')
                                        ->badge()
                                        ->color('success'),
                                    TextEntry::make('last_synced_at')
                                        ->dateTime()
                                        ->label('Last Synced')
                                        ->color('gray')
                                        ->placeholder('Never synced'),
                                ]),
                        ]),

                    Tabs\Tab::make('Deposit Addresses')
                        ->icon('heroicon-o-arrow-up-tray')
                        ->schema([
                            Section::make('📥 Deposit Wallets')
                                ->description('Scan QR or copy full address for each currency.')
                                ->columns([
                                    'default' => 1,
                                    'sm' => 2,
                                    'lg' => 3,
                                ])
                                ->schema([
                                    // BTC
                                    ImageEntry::make('btc_qr')
                                        ->label('Bitcoin (BTC)')
                                        ->disk('public')
                                        ->tooltip('Click to view QR')
                                        ->openUrlInNewTab()
                                        ->visible(fn ($record) => filled($record->btc_qr)),

                                    TextEntry::make('btc_address')
                                        ->label('BTC Address')
                                        ->copyable()
                                        ->tooltip(fn ($record) => $record->btc_address)
                                        ->placeholder('Not Available')
                                        ->color('primary')
                                        ->extraAttributes([
                                            'class' => 'overflow-x-auto whitespace-nowrap scrollbar-thin scrollbar-thumb-yellow-600 text-sm p-2 bg-gray-900 text-green-400 rounded-md',
                                        ]),

                                    // XMR
                                    ImageEntry::make('xmr_qr')
                                        ->label('Monero (XMR)')
                                        ->disk('public')
                                        ->tooltip('Click to view QR')
                                        ->openUrlInNewTab()
                                        ->visible(fn ($record) => filled($record->xmr_qr)),

                                    TextEntry::make('xmr_address')
                                        ->label('XMR Address')
                                        ->copyable()
                                        ->tooltip(fn ($record) => $record->xmr_address)
                                        ->placeholder('Not Available')
                                        ->color('primary')
                                        ->extraAttributes([
                                            'class' => 'overflow-x-auto whitespace-nowrap scrollbar-thin scrollbar-thumb-yellow-600 text-sm p-2 bg-gray-900 text-green-400 rounded-md',
                                        ]),

                                    // SOL
                                    ImageEntry::make('sol_qr')
                                        ->label('Solana (SOL)')
                                        ->disk('public')
                                        ->tooltip('Click to view QR')
                                        ->openUrlInNewTab()
                                        ->visible(fn ($record) => filled($record->sol_qr)),

                                    TextEntry::make('sol_address')
                                        ->label('SOL Address')
                                        ->copyable()
                                        ->tooltip(fn ($record) => $record->sol_address)
                                        ->placeholder('Not Available')
                                        ->color('primary')
                                        ->extraAttributes([
                                            'class' => 'overflow-x-auto whitespace-nowrap scrollbar-thin scrollbar-thumb-yellow-600 text-sm p-2 bg-gray-900 text-green-400 rounded-md',
                                        ]),
                                ]),
                        ]),
                ])
                ->contained(true)
                ->columnSpanFull(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWallets::route('/'),
            'view' => Pages\ViewWallet::route('/{record}'),
        ];
    }
}
