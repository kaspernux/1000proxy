<?php

namespace App\Filament\Clusters\CustomerManagement\Resources\CustomerResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\WalletTransaction;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\BadgeColumn;

class WalletTransactionsRelationManager extends RelationManager
{
    protected static string $relationship = 'walletTransactions';
    protected static ?string $title = 'Wallet Transactions';
    protected static ?string $modelLabel = 'Transaction';
    protected static ?string $pluralModelLabel = 'Wallet Transactions';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Transaction Information')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->options([
                                'deposit' => 'Deposit',
                                'withdrawal' => 'Withdrawal',
                                'purchase' => 'Purchase',
                                'refund' => 'Refund',
                                'bonus' => 'Bonus',
                                'fee' => 'Fee',
                            ])
                            ->required(),
                        
                        Forms\Components\TextInput::make('amount')
                            ->label('Amount ($)')
                            ->numeric()
                            ->prefix('$')
                            ->step(0.01)
                            ->required(),
                        
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'completed' => 'Completed',
                                'failed' => 'Failed',
                                'cancelled' => 'Cancelled',
                            ])
                            ->required(),
                        
                        Forms\Components\TextInput::make('reference')
                            ->label('Reference')
                            ->maxLength(255),
                    ])
                    ->columns(2),
                
                Section::make('Details')
                    ->schema([
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->columnSpanFull(),
                        
                        Forms\Components\KeyValue::make('metadata')
                            ->label('Additional Data')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('reference')
            ->columns([
                Tables\Columns\BadgeColumn::make('type')
                    ->label('Type')
                    ->colors([
                        'success' => 'deposit',
                        'warning' => 'withdrawal',
                        'primary' => 'purchase',
                        'info' => 'refund',
                        'secondary' => 'bonus',
                        'danger' => 'fee',
                    ])
                    ->icons([
                        'heroicon-o-arrow-down' => 'deposit',
                        'heroicon-o-arrow-up' => 'withdrawal',
                        'heroicon-o-shopping-cart' => 'purchase',
                        'heroicon-o-arrow-path' => 'refund',
                        'heroicon-o-gift' => 'bonus',
                        'heroicon-o-minus' => 'fee',
                    ]),
                
                Tables\Columns\TextColumn::make('amount')
                    ->label('Amount')
                    ->money('USD')
                    ->sortable()
                    ->color(fn ($record) => match ($record->type) {
                        'deposit', 'refund', 'bonus' => 'success',
                        'withdrawal', 'purchase', 'fee' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($record) => match ($record->type) {
                        'deposit', 'refund', 'bonus' => '+$' . number_format($record->amount, 2),
                        'withdrawal', 'purchase', 'fee' => '-$' . number_format($record->amount, 2),
                        default => '$' . number_format($record->amount, 2),
                    }),
                
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'completed',
                        'danger' => 'failed',
                        'gray' => 'cancelled',
                    ]),
                
                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->description),
                
                Tables\Columns\TextColumn::make('reference')
                    ->label('Reference')
                    ->copyable()
                    ->limit(15)
                    ->tooltip('Click to copy'),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime()
                    ->sortable()
                    ->since(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'deposit' => 'Deposits',
                        'withdrawal' => 'Withdrawals',
                        'purchase' => 'Purchases',
                        'refund' => 'Refunds',
                        'bonus' => 'Bonuses',
                        'fee' => 'Fees',
                    ]),
                
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'completed' => 'Completed',
                        'failed' => 'Failed',
                        'cancelled' => 'Cancelled',
                    ]),
                
                Tables\Filters\Filter::make('amount_range')
                    ->form([
                        Forms\Components\TextInput::make('amount_from')
                            ->label('Amount From')
                            ->numeric()
                            ->prefix('$'),
                        Forms\Components\TextInput::make('amount_to')
                            ->label('Amount To')
                            ->numeric()
                            ->prefix('$'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['amount_from'],
                                fn (Builder $query, $amount): Builder => $query->where('amount', '>=', $amount),
                            )
                            ->when(
                                $data['amount_to'],
                                fn (Builder $query, $amount): Builder => $query->where('amount', '<=', $amount),
                            );
                    }),
            ])
            ->headerActions([
                    \Filament\Actions\CreateAction::make(),
                
                    \Filament\Actions\Action::make('manual_deposit')
                    ->label('Manual Deposit')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->form([
                        Forms\Components\TextInput::make('amount')
                            ->label('Deposit Amount ($)')
                            ->numeric()
                            ->prefix('$')
                            ->step(0.01)
                            ->required()
                            ->minValue(0.01),
                        
                        Forms\Components\TextInput::make('description')
                            ->label('Description')
                            ->required()
                            ->placeholder('Manual deposit by admin'),
                        
                        Forms\Components\TextInput::make('reference')
                            ->label('Reference')
                            ->placeholder('REF-' . strtoupper(uniqid())),
                    ])
                    ->action(function (array $data) {
                        $wallet = $this->ownerRecord->wallet ?? $this->ownerRecord->wallet()->create(['balance' => 0]);
                        
                        $transaction = $wallet->transactions()->create([
                            'type' => 'deposit',
                            'amount' => $data['amount'],
                            'status' => 'completed',
                            'description' => $data['description'],
                            'reference' => $data['reference'] ?? 'MANUAL-' . strtoupper(uniqid()),
                        ]);
                        
                        $wallet->increment('balance', $data['amount']);
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Manual Deposit Added')
                            ->body("{$data['amount']} deposited to user wallet")
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation(),
            ])
            ->actions([
                \Filament\Actions\ViewAction::make(),
                \Filament\Actions\EditAction::make(),
                
                    \Filament\Actions\Action::make('reverse')
                    ->label('Reverse')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(fn ($record) => $record->status === 'completed' && in_array($record->type, ['deposit', 'withdrawal']))
                    ->action(function ($record) {
                        $wallet = $record->wallet;
                        
                        // Create reverse transaction
                        $reverseType = $record->type === 'deposit' ? 'withdrawal' : 'deposit';
                        $wallet->transactions()->create([
                            'type' => $reverseType,
                            'amount' => $record->amount,
                            'status' => 'completed',
                            'description' => "Reversal of transaction: {$record->reference}",
                            'reference' => 'REV-' . $record->reference,
                        ]);
                        
                        // Update wallet balance
                        if ($record->type === 'deposit') {
                            $wallet->decrement('balance', $record->amount);
                        } else {
                            $wallet->increment('balance', $record->amount);
                        }
                        
                        // Mark original as cancelled
                        $record->update(['status' => 'cancelled']);
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Transaction Reversed')
                            ->body("Transaction {$record->reference} has been reversed")
                            ->warning()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Reverse Transaction')
                    ->modalDescription('This will create a reverse transaction and update the wallet balance. This action cannot be undone.'),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s'); // Auto-refresh every 30 seconds
    }
}
