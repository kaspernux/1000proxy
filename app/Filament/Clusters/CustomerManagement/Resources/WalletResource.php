<?php

namespace App\Filament\Clusters\CustomerManagement\Resources;

use App\Filament\Clusters\CustomerManagement;
use App\Filament\Clusters\CustomerManagement\Resources\WalletResource\Pages;
use App\Models\Wallet;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\Card;
use Filament\Infolists\Components\Tabs;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Split;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;


class WalletResource extends Resource
{
    protected static ?string $model = Wallet::class;
    protected static ?string $navigationIcon = 'heroicon-o-wallet';
    protected static ?string $cluster = CustomerManagement::class;

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('customer.name')->label('Customer')->searchable()->sortable(),
            TextColumn::make('balance')->label('USD Balance')->money('usd')->badge()->color('success')->sortable(),
            IconColumn::make('is_default')->boolean()->label('Default?'),
            TextColumn::make('last_synced_at')->label('Last Synced')->dateTime()->sortable(),
            TextColumn::make('created_at')->label('Created')->dateTime()->sortable(),
        ])
        ->filters([])
        ->actions([
            Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('View Wallet')
                        ->color('primary')
                        ->button(),
                    Tables\Actions\ViewAction::make()->label('Details'),
                ]),
        ])
        ->bulkActions([
            Tables\Actions\DeleteBulkAction::make(),
        ]);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('balance')->label('USD Balance')->numeric()->disabled(),

            Forms\Components\Toggle::make('is_default')->label('Default Wallet')->disabled(),
            Forms\Components\DateTimePicker::make('last_synced_at')->label('Last Synced')->disabled(),

            Forms\Components\TextInput::make('btc_address')->label('BTC Address')->disabled()->columnSpanFull(),
            Forms\Components\TextInput::make('xmr_address')->label('XMR Address')->disabled()->columnSpanFull(),
            Forms\Components\TextInput::make('sol_address')->label('SOL Address')->disabled()->columnSpanFull(),
        ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Tabs::make('Wallet Details')->tabs([
                Tabs\Tab::make('Overview')->schema([
                    Section::make('Wallet Info')->columns(2)->schema([
                        TextEntry::make('customer.name')->label('Customer')->color('primary'),
                        TextEntry::make('balance')->money('usd')->label('USD Balance')->color('success')->badge(),
                        TextEntry::make('last_synced_at')->label('Last Synced')->dateTime()->placeholder('Never'),
                        TextEntry::make('created_at')->label('Created At')->dateTime(),
                    ]),
                ]),
                Tabs\Tab::make('Deposit QRs')->schema([
                    Section::make('📥 Deposit QR Codes & Addresses')
                        ->description('These addresses are used to receive crypto deposits.')
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
            ])->columnSpanFull(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWallets::route('/'),
            'create' => Pages\CreateWallet::route('/create'),
            'edit' => Pages\EditWallet::route('/{record}/edit'),
            'view' => Pages\ViewWallet::route('/{record}'),
        ];
    }
}

