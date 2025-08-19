<?php

namespace App\Filament\Clusters\CustomerManagement\Resources;

use App\Filament\Clusters\CustomerManagement;
use App\Filament\Clusters\CustomerManagement\Resources\WalletTransactionResource\Pages;
use App\Filament\Clusters\CustomerManagement\Resources\WalletTransactionResource\RelationManagers;
use App\Models\WalletTransaction;
use App\Filament\Concerns\HasPerformanceOptimizations;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Http\Resources\Json\JsonResource;

use Filament\Actions\Action;
use Filament\Infolists\Components\Tabs;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\KeyValueEntry;
use BackedEnum;

class WalletTransactionResource extends Resource
{
    use HasPerformanceOptimizations;
    protected static ?string $model = WalletTransaction::class;
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationLabel = 'Wallet Transactions';
    protected static ?string $cluster = CustomerManagement::class;

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return (bool) ($user?->isAdmin() || $user?->isManager());
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\Select::make('wallet_id')
                ->label('Wallet')
                ->relationship('wallet', 'id')
                ->searchable()
                ->required()
                ->helperText('Select the wallet this transaction belongs to'),

            Forms\Components\Select::make('customer_id')
                ->label('Customer')
                ->relationship('customer', 'name')
                ->searchable()
                ->required()
                ->helperText('Customer associated with the transaction'),

            Forms\Components\Select::make('type')->options([
                'deposit' => 'Deposit',
                'withdrawal' => 'Withdrawal',
                'payment' => 'Payment',
                'adjustment' => 'Adjustment',
                'refund' => 'Refund',
            ])->required()->helperText('Transaction operation type'),

            Forms\Components\TextInput::make('amount')->numeric()->required()->helperText('Amount in USD'),

            Forms\Components\Select::make('status')->options([
                'pending' => 'Pending',
                'completed' => 'Completed',
                'failed' => 'Failed',
            ])->required()->helperText('Set completed after on-chain confirmation'),

            Forms\Components\TextInput::make('reference')->required()->helperText('External reference or internal note for reconciliation'),
            Forms\Components\TextInput::make('payment_id')->label('Payment ID')->placeholder('The Blockchain transaction ID')->helperText('Hash/ID of the payment on-chain or provider ID'),

            Forms\Components\TextInput::make('address')->label('Address')->helperText('Destination/source address if applicable'),

            Forms\Components\DateTimePicker::make('confirmed_at')->label('Confirmed At')->helperText('When the transaction was confirmed'),

            Forms\Components\Textarea::make('description')->helperText('Internal notes about this transaction'),
            Forms\Components\Textarea::make('qr_code_path')->disabled()->helperText('System field (read-only)'),
            Forms\Components\Textarea::make('metadata')->json()->helperText('Additional structured data as JSON'),
        ]);
    }

    public static function table(Table $table): Table
    {
        $table = $table->columns([
            Tables\Columns\TextColumn::make('customer.name')->label('Customer')->searchable(),
            Tables\Columns\BadgeColumn::make('type')->label('Type')->colors([
                'success' => 'deposit',
                'danger' => 'withdrawal',
                'primary' => 'payment',
                'warning' => 'refund',
            ])->formatStateUsing(fn (string $state) => ucfirst($state)),

            Tables\Columns\TextColumn::make('amount')->money('usd')->label('Amount')->color('success'),

            Tables\Columns\BadgeColumn::make('status')->label('Status')->colors([
                'success' => 'completed',
                'warning' => 'pending',
                'danger' => 'failed',
            ]),

            Tables\Columns\TextColumn::make('reference')
                ->copyable()
                ->tooltip(fn ($record) => $record->reference)
                ->limit(10)
                ->label('Reference'),

            Tables\Columns\TextColumn::make('address')->copyable()->toggleable()->label('Address')->limit(16),

            Tables\Columns\TextColumn::make('created_at')->dateTime()->label('Created'),
    ])
    ->defaultSort('created_at', 'desc')
    ->actions([
            \Filament\Actions\ViewAction::make(),
            \Filament\Actions\EditAction::make(),

            // ✅ Admin Manual Confirmation Action
            Action::make('confirmDeposit')
                ->label('Confirm')
                ->icon('heroicon-o-check-circle')
                ->requiresConfirmation()
                ->visible(fn ($record) => $record->type === 'deposit' && $record->status === 'pending')
                ->action(function (WalletTransaction $record) {
                    $record->wallet->increment('balance', $record->amount);
                    $record->update([
                        'status' => 'completed',
                        'confirmed_at' => now(),
                        'description' => $record->description . ' [confirmed manually]',
                    ]);
                }),
    ]);

        return self::applyTablePreset($table, [
            'defaultPage' => 50,
            'empty' => [
                'icon' => 'heroicon-o-clipboard-document-check',
                'heading' => 'No transactions found',
                'description' => 'No wallet transactions match your filters.',
            ],
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            Tabs::make('Transaction Details')->tabs([
                Tabs\Tab::make('Overview')->schema([
                    Section::make('💸 Transaction Info')->columns(2)->schema([
                        TextEntry::make('customer.name')->label('Customer')->badge()->color('info'),
                        TextEntry::make('wallet.id')->label('Wallet ID')->color('gray'),

                        TextEntry::make('type')
                            ->label('Type')
                            ->badge()
                            ->color(fn ($state) => match ($state) {
                                'deposit' => 'success',
                                'withdrawal' => 'danger',
                                'payment' => 'primary',
                                'refund' => 'warning',
                                default => 'gray',
                            }),

                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->color(fn ($state) => match ($state) {
                                'completed' => 'success',
                                'pending' => 'warning',
                                'failed' => 'danger',
                                default => 'gray',
                            }),

                        TextEntry::make('amount')->money('usd')->label('Amount')->color('success')->weight('bold'),
                        TextEntry::make('reference')->copyable()->label('Reference')->tooltip(fn ($record) => $record->reference),
                        TextEntry::make('address')->copyable()->label('Address')->placeholder('N/A'),
                        TextEntry::make('payment_id')->label('Payment ID')->placeholder('The Blockchain transaction ID')->columnSpanFull(),
                        TextEntry::make('confirmed_at')->dateTime()->label('Confirmed At')->placeholder('Not yet'),

                        TextEntry::make('description')->label('Description')->columnSpanFull(),
                        TextEntry::make('created_at')->label('Created At')->dateTime()->color('gray'),
                    ]),
                ]),
                Tabs\Tab::make('Metadata')->schema([
                    Section::make('📂 Metadata')->schema([
                        KeyValueEntry::make('metadata')->keyLabel('Key')->valueLabel('Value')->placeholder('No metadata available'),
                    ]),
                ]),
            ])->columnSpanFull(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWalletTransactions::route('/'),
            'create' => Pages\CreateWalletTransaction::route('/create'),
            'edit' => Pages\EditWalletTransaction::route('/{record}/edit'),
            'view' => Pages\ViewWalletTransaction::route('/{record}'),
        ];
    }
}

