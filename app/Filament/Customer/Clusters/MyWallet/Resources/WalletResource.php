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

class WalletResource extends Resource
{
    protected static ?string $model = Wallet::class;
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationLabel = 'My Wallet';
    protected static ?string $cluster = MyWallet::class;

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
                Tables\Actions\ViewAction::make()
                    ->label('View Wallet')
                    ->color('primary')
                    ->button(),
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
                            ->description('Deposit cryptocurrencies to these addresses to top-up your USD balance.')
                            ->schema([
                                Split::make([
                                    // Bitcoin Card
                                    Card::make([
                                        ImageEntry::make('btc_qr')
                                            ->disk('public')
                                            ->label('')
                                            ->size(220)
                                            ->alignCenter()
                                            ->openUrlInNewTab()
                                            ->tooltip('Scan to deposit Bitcoin')
                                            ->visible(fn ($record) => filled($record->btc_qr)),
                                        Section::make()
                                            ->schema([
                                                TextEntry::make('btc_address')
                                                    ->label('Bitcoin (BTC) Address')
                                                    ->copyable()
                                                    ->state(fn ($record) => str($record->btc_address)->limit(12, '...'))
                                                    ->tooltip(fn ($record) => $record->btc_address)
                                                    ->alignCenter()
                                                    ->color('primary')
                                                    ->placeholder('Not Available')
                                                    ->extraAttributes([
                                                        'class' => 'break-words text-sm text-green-400 cursor-pointer',
                                                    ]),
                                            ])
                                            ->extraAttributes([
                                                'class' => 'w-full p-3 bg-gray-900 rounded-xl text-center mt-4',
                                            ]),
                                    ])->extraAttributes([
                                        'class' => 'flex flex-col items-center justify-center p-6 rounded-2xl shadow-lg bg-gray-800 hover:bg-gray-700 transition-all',
                                    ]),
                                    // Monero Card
                                    Card::make([
                                        ImageEntry::make('xmr_qr')
                                            ->disk('public')
                                            ->label('')
                                            ->size(220)
                                            ->alignCenter()
                                            ->openUrlInNewTab()
                                            ->tooltip('Scan to deposit Monero')
                                            ->visible(fn ($record) => filled($record->xmr_qr)),
                                        Section::make()
                                            ->schema([
                                                TextEntry::make('xmr_address')
                                                    ->label('Monero (XMR) Address')
                                                    ->copyable()
                                                    ->state(fn ($record) => str($record->xmr_address)->limit(12, '...'))
                                                    ->tooltip(fn ($record) => $record->xmr_address)
                                                    ->alignCenter()
                                                    ->color('primary')
                                                    ->placeholder('Not Available')
                                                    ->extraAttributes([
                                                        'class' => 'break-words text-sm text-green-400 cursor-pointer',
                                                    ]),
                                            ])
                                            ->extraAttributes([
                                                'class' => 'w-full p-3 bg-gray-900 rounded-xl text-center mt-4',
                                            ]),
                                    ])->extraAttributes([
                                        'class' => 'flex flex-col items-center justify-center p-6 rounded-2xl shadow-lg bg-gray-800 hover:bg-gray-700 transition-all',
                                    ]),
                                    // Solana Card
                                    Card::make([
                                        ImageEntry::make('sol_qr')
                                            ->disk('public')
                                            ->label('')
                                            ->size(220)
                                            ->alignCenter()
                                            ->openUrlInNewTab()
                                            ->tooltip('Scan to deposit Solana')
                                            ->visible(fn ($record) => filled($record->sol_qr)),
                                        Section::make()
                                            ->schema([
                                                TextEntry::make('sol_address')
                                                    ->label('Solana (SOL) Address')
                                                    ->copyable()
                                                    ->state(fn ($record) => str($record->sol_address)->limit(12, '...'))
                                                    ->tooltip(fn ($record) => $record->sol_address)
                                                    ->alignCenter()
                                                    ->color('primary')
                                                    ->placeholder('Not Available')
                                                    ->extraAttributes([
                                                        'class' => 'break-words text-sm text-green-400 cursor-pointer',
                                                    ]),
                                            ])
                                            ->extraAttributes([
                                                'class' => 'w-full p-3 bg-gray-900 rounded-xl text-center mt-4',
                                            ]),
                                    ])->extraAttributes([
                                        'class' => 'flex flex-col items-center justify-center p-6 rounded-2xl shadow-lg bg-gray-800 hover:bg-gray-700 transition-all',
                                    ]),
                                ])->from('md'),
                            ]),
                    ]),
            ])
            ->contained(true)
            ->columnSpanFull(), // ✅ Removed erroneous semicolon
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
