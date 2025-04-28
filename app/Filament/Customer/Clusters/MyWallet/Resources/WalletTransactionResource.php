<?php

namespace App\Filament\Customer\Clusters\MyWallet\Resources;

use App\Filament\Customer\Clusters\MyWallet;
use App\Filament\Customer\Clusters\MyWallet\Resources\WalletTransactionResource\Pages;
use App\Models\WalletTransaction;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists\Infolist;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Tabs;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Card;
use Filament\Infolists\Components\KeyValueEntry;

class WalletTransactionResource extends Resource
{
    protected static ?string $model = WalletTransaction::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationLabel = 'Wallet Transactions';
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
                BadgeColumn::make('type')
                    ->label('Type')
                    ->colors([
                        'success' => 'deposit',
                        'danger' => 'withdrawal',
                        'primary' => 'payment',
                        'warning' => 'refund',
                    ])
                    ->formatStateUsing(fn (string $state) => ucfirst($state))
                    ->sortable(),

                TextColumn::make('amount')
                    ->label('Amount')
                    ->money('usd')
                    ->sortable()
                    ->extraAttributes(['class' => 'font-bold text-success']),

                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => 'completed',
                        'warning' => 'pending',
                        'danger' => 'failed',
                    ])
                    ->formatStateUsing(fn (string $state) => ucfirst($state))
                    ->sortable(),

                TextColumn::make('reference')
                    ->label('Reference')
                    ->copyable()
                    ->state(fn ($record) => str($record->reference)->limit(10, '...'))
                    ->tooltip(fn ($record) => $record->reference)
                    ->sortable(),

                TextColumn::make('description')
                    ->label('Description')
                    ->limit(20)
                    ->placeholder('No description')
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime('M d, Y H:i')
                    ->since()
                    ->sortable()
                    ->color('gray'),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No Transactions Yet')
            ->emptyStateDescription('Once you deposit, withdraw, or pay, your transactions will show here.');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Tabs::make('Transaction Details')
                ->persistTab()
                ->tabs([
                    Tabs\Tab::make('Overview')
                        ->icon('heroicon-o-clipboard-document-check')
                        ->schema([
                            Section::make('💸 Transaction Info')
                                ->description('Details of this wallet transaction.')
                                ->columns([
                                    'sm' => 1,
                                    'md' => 2,
                                ])
                                ->schema([
                                    TextEntry::make('type')
                                        ->label('Type')
                                        ->badge()
                                        ->color(fn (string $state) => match ($state) {
                                            'deposit' => 'success',
                                            'withdrawal' => 'danger',
                                            'payment' => 'primary',
                                            'refund' => 'warning',
                                            default => 'gray',
                                        })
                                        ->formatStateUsing(fn (string $state) => ucfirst($state)),

                                    TextEntry::make('status')
                                        ->label('Status')
                                        ->badge()
                                        ->color(fn (string $state) => match ($state) {
                                            'completed' => 'success',
                                            'pending' => 'warning',
                                            'failed' => 'danger',
                                            default => 'gray',
                                        })
                                        ->formatStateUsing(fn (string $state) => ucfirst($state)),

                                    TextEntry::make('amount')
                                        ->label('Amount')
                                        ->money('usd')
                                        ->color('success')
                                        ->weight('bold'),

                                    TextEntry::make('reference')
                                        ->label('Reference')
                                        ->copyable()
                                        ->color('primary')
                                        ->tooltip(fn ($record) => $record->reference),

                                    TextEntry::make('description')
                                        ->label('Description')
                                        ->placeholder('No description')
                                        ->columnSpanFull(),

                                    TextEntry::make('created_at')
                                        ->label('Created At')
                                        ->dateTime('M d, Y H:i')
                                        ->color('gray')
                                        ->icon('heroicon-o-calendar-days'),
                                ]),
                        ]),

                    Tabs\Tab::make('Metadata')
                        ->icon('heroicon-o-code-bracket-square')
                        ->schema([
                            Section::make('📂 Metadata')
                                ->description('Raw metadata for this transaction.')
                                ->schema([
                                    KeyValueEntry::make('metadata')
                                        ->keyLabel('Key')
                                        ->valueLabel('Value')
                                        ->placeholder('No metadata available.'),
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
            'index' => Pages\ListWalletTransactions::route('/'),
            'view' => Pages\ViewWalletTransaction::route('/{record}'),
        ];
    }
}
