<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ReferralWithdrawalResource\Pages;
use App\Models\ReferralWithdrawal;
use UnitEnum;
use BackedEnum;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;
use Illuminate\Support\Str;

class ReferralWithdrawalResource extends Resource
{
    protected static ?string $model = ReferralWithdrawal::class;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';
    protected static string|UnitEnum|null $navigationGroup = 'Finance';
    protected static ?int $navigationSort = 35;
    protected static ?string $modelLabel = 'Referral Withdrawal';
    protected static ?string $pluralModelLabel = 'Referral Withdrawals';

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->hasRole('admin');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return (bool) auth()->user()?->hasRole('admin');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\Select::make('customer_id')
                ->relationship('customer', 'email')
                ->searchable()
                ->required(),
            Forms\Components\TextInput::make('amount')->numeric()->required(),
            Forms\Components\TextInput::make('status')
                ->datalist(['pending','approved','rejected','paid'])
                ->required(),
            Forms\Components\TextInput::make('destination')->columnSpanFull(),
            Forms\Components\KeyValue::make('metadata')->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(function (): Builder {
                return ReferralWithdrawal::query()->with('customer');
            })
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('customer.email')->label('Customer')->searchable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Amount')
                    ->alignRight()
                    ->formatStateUsing(fn($state) => number_format((float) $state, 2))
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'warning' => ['pending'],
                        'info' => ['approved'],
                        'danger' => ['rejected'],
                        'success' => ['paid'],
                    ])
                    ->sortable(),
                Tables\Columns\TextColumn::make('destination')->limit(30),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->since()->sortable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('approve')
                    ->visible(fn(ReferralWithdrawal $r) => $r->status === 'pending')
                    ->requiresConfirmation()
                    ->color('info')
                    ->action(function (ReferralWithdrawal $r) { $r->update(['status' => 'approved']); }),
                Tables\Actions\Action::make('reject')
                    ->visible(fn(ReferralWithdrawal $r) => in_array($r->status, ['pending','approved']))
                    ->requiresConfirmation()
                    ->color('danger')
                    ->action(function (ReferralWithdrawal $r) { $r->update(['status' => 'rejected']); }),
                Tables\Actions\Action::make('markPaid')
                    ->visible(fn(ReferralWithdrawal $r) => in_array($r->status, ['approved']))
                    ->requiresConfirmation()
                    ->color('success')
                    ->action(function (ReferralWithdrawal $r) {
                        $customer = $r->customer;
                        $wallet = $customer->getWallet();
                        // Ensure sufficient funds are available in wallet before payout
                        if (bccomp((string) $wallet->balance, (string) $r->amount, 8) < 0) {
                            Notification::make()->title('Insufficient wallet balance to payout')->danger()->send();
                            return;
                        }

                        // Debit the wallet and record a transaction
                        $wallet->decrement('balance', $r->amount);
                        $wallet->transactions()->create([
                            'wallet_id' => $wallet->id,
                            'customer_id' => $customer->id,
                            'type' => 'withdrawal',
                            'amount' => -((float) $r->amount),
                            'status' => 'completed',
                            'reference' => 'ReferralPayout_' . strtoupper(Str::random(8)),
                            'description' => 'Referral withdrawal payout',
                            'metadata' => [
                                'referral_withdrawal_id' => $r->id,
                                'destination' => $r->destination,
                            ],
                        ]);

                        $r->update(['status' => 'paid']);
                        Notification::make()->title('Withdrawal marked as paid')->success()->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReferralWithdrawals::route('/'),
            'view' => Pages\ViewReferralWithdrawal::route('/{record}'),
        ];
    }
}
