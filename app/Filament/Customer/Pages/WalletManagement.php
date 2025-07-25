<?php

namespace App\Filament\Customer\Pages;

use Filament\Pages\Page;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Form;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Actions\Action as PageAction;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WalletManagement extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-wallet';
    protected static ?string $navigationLabel = 'My Wallet';
    protected static string $view = 'filament.customer.pages.wallet-management';
    protected static ?int $navigationSort = 4;

    public ?array $data = [];
    public $walletBalance = 0;
    public $walletId = null;

    public function mount(): void
    {
        $customer = Auth::guard('customer')->user();
        $wallet = DB::table('wallets')->where('customer_id', $customer->id)->first();

        if (!$wallet) {
            // Create wallet if it doesn't exist
            $this->walletId = DB::table('wallets')->insertGetId([
                'customer_id' => $customer->id,
                'balance' => 0.00,
                'currency' => 'USD',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->walletBalance = 0.00;
        } else {
            $this->walletId = $wallet->id;
            $this->walletBalance = $wallet->balance;
        }

        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('amount')
                    ->label('Top-up Amount')
                    ->numeric()
                    ->prefix('$')
                    ->required()
                    ->minValue(5)
                    ->maxValue(1000)
                    ->placeholder('50.00'),

                Select::make('payment_method')
                    ->label('Payment Method')
                    ->options([
                        'bitcoin' => '₿ Bitcoin',
                        'monero' => 'ⓧ Monero',
                        'solana' => '◎ Solana',
                        'ethereum' => 'Ξ Ethereum',
                        'usdt' => '₮ USDT (TRC20)',
                        'paypal' => 'PayPal',
                        'stripe' => 'Credit Card',
                    ])
                    ->required()
                    ->default('bitcoin'),

                Hidden::make('customer_id')
                    ->default(Auth::guard('customer')->id()),
            ])
            ->statePath('data');
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Transaction History')
            ->description('Your wallet transaction history and payment records')
            ->columns([
                TextColumn::make('id')
                    ->label('Transaction ID')
                    ->prefix('#')
                    ->sortable(),

                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'deposit' => 'success',
                        'withdrawal' => 'warning',
                        'purchase' => 'info',
                        'refund' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                TextColumn::make('amount')
                    ->label('Amount')
                    ->money('USD')
                    ->color(fn (string $state, $record): string =>
                        $record->type === 'deposit' || $record->type === 'refund' ? 'success' : 'danger'
                    )
                    ->prefix(fn ($record): string =>
                        $record->type === 'deposit' || $record->type === 'refund' ? '+' : '-'
                    )
                    ->sortable(),

                TextColumn::make('description')
                    ->label('Description')
                    ->limit(50),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'completed' => 'success',
                        'failed' => 'danger',
                        'cancelled' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                TextColumn::make('payment_method')
                    ->label('Method')
                    ->formatStateUsing(fn (?string $state): string =>
                        $state ? ucfirst($state) : 'N/A'
                    )
                    ->badge(),

                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('M j, Y H:i')
                    ->sortable(),
            ])
            ->paginated([10, 25, 50]);
    }

    protected function getHeaderActions(): array
    {
        return [
            PageAction::make('add_funds')
                ->label('Add Funds')
                ->icon('heroicon-o-plus')
                ->color('primary')
                ->form([
                    TextInput::make('amount')
                        ->label('Amount')
                        ->numeric()
                        ->prefix('$')
                        ->required()
                        ->minValue(5)
                        ->maxValue(1000),

                    Select::make('payment_method')
                        ->label('Payment Method')
                        ->options([
                            'bitcoin' => '₿ Bitcoin',
                            'monero' => 'ⓧ Monero',
                            'solana' => '◎ Solana',
                            'ethereum' => 'Ξ Ethereum',
                            'usdt' => '₮ USDT (TRC20)',
                            'paypal' => 'PayPal',
                            'stripe' => 'Credit Card',
                        ])
                        ->required(),
                ])
                ->action('createTopUpOrder'),

            PageAction::make('request_withdrawal')
                ->label('Withdraw Funds')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('warning')
                ->visible(fn () => $this->walletBalance >= 10)
                ->form([
                    TextInput::make('amount')
                        ->label('Withdrawal Amount')
                        ->numeric()
                        ->prefix('$')
                        ->required()
                        ->minValue(10)
                        ->maxValue($this->walletBalance),

                    Select::make('withdrawal_method')
                        ->label('Withdrawal Method')
                        ->options([
                            'bitcoin' => '₿ Bitcoin',
                            'monero' => 'ⓧ Monero',
                            'paypal' => 'PayPal',
                            'bank_transfer' => 'Bank Transfer',
                        ])
                        ->required(),

                    TextInput::make('withdrawal_address')
                        ->label('Address/Account')
                        ->required()
                        ->placeholder('Enter your wallet address or account details'),
                ])
                ->action('requestWithdrawal'),

            PageAction::make('transaction_history')
                ->label('Full History')
                ->icon('heroicon-o-document-text')
                ->color('info')
                ->url(fn (): string => route('filament.customer.pages.wallet-management') . '?tab=transactions'),
        ];    }

    public function createTopUpOrder(array $data): void
    {
        try {
            $customer = Auth::guard('customer')->user();

            // Create wallet transaction record
            $transactionId = DB::table('wallet_transactions')->insertGetId([
                'customer_id' => $customer->id,
                'wallet_id' => $this->walletId,
                'type' => 'deposit',
                'amount' => $data['amount'],
                'status' => 'pending',
                'payment_method' => $data['payment_method'],
                'description' => "Wallet top-up via " . ucfirst($data['payment_method']),
                'reference_id' => 'TXN-' . strtoupper(uniqid()),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Generate payment details based on method
            $paymentDetails = $this->generatePaymentDetails($data['payment_method'], $data['amount'], $transactionId);

            Notification::make()
                ->title('Payment Created')
                ->body("Your {$data['payment_method']} payment has been generated. Complete the payment to add funds.")
                ->success()
                ->actions([
                    \Filament\Notifications\Actions\Action::make('view_payment')
                        ->label('View Payment Details')
                        ->url('#') // Would redirect to payment page
                ])
                ->send();

            // Refresh balance
            $this->mount();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Payment Creation Failed')
                ->body('Unable to create payment. Please try again.')
                ->danger()
                ->send();
        }
    }

    public function requestWithdrawal(array $data): void
    {
        try {
            $customer = Auth::guard('customer')->user();

            if ($data['amount'] > $this->walletBalance) {
                Notification::make()
                    ->title('Insufficient Balance')
                    ->body('You do not have enough funds for this withdrawal.')
                    ->warning()
                    ->send();
                return;
            }

            // Create withdrawal transaction
            $transactionId = DB::table('wallet_transactions')->insertGetId([
                'customer_id' => $customer->id,
                'wallet_id' => $this->walletId,
                'type' => 'withdrawal',
                'amount' => $data['amount'],
                'status' => 'pending',
                'payment_method' => $data['withdrawal_method'],
                'description' => "Withdrawal to " . ucfirst($data['withdrawal_method']),
                'metadata' => json_encode([
                    'withdrawal_address' => $data['withdrawal_address'],
                    'withdrawal_method' => $data['withdrawal_method'],
                ]),
                'reference_id' => 'WTH-' . strtoupper(uniqid()),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Deduct from wallet (will be restored if withdrawal fails)
            DB::table('wallets')
                ->where('id', $this->walletId)
                ->decrement('balance', $data['amount']);

            Notification::make()
                ->title('Withdrawal Requested')
                ->body('Your withdrawal request has been submitted and will be processed within 24-48 hours.')
                ->success()
                ->send();

            // Refresh balance
            $this->mount();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Withdrawal Request Failed')
                ->body('Unable to process withdrawal request. Please try again.')
                ->danger()
                ->send();
        }
    }

    public function downloadReceipt($transaction): void
    {
        // Generate receipt PDF or redirect to receipt page
        Notification::make()
            ->title('Receipt Download')
            ->body('Receipt generation would be implemented here.')
            ->info()
            ->send();
    }

    private function generatePaymentDetails(string $method, float $amount, int $transactionId): array
    {
        switch ($method) {
            case 'bitcoin':
                return [
                    'address' => 'bc1qxy2kgdygjrsqtzq2n0yrf2493p83kkfjhx0wlh',
                    'amount_btc' => $amount / 45000, // Rough BTC conversion
                    'network' => 'Bitcoin',
                ];
            case 'monero':
                return [
                    'address' => '4AdUdXHHZ44E1hHKuQiGWvVANdwu3RnNUKt8TYhm4VUc3LJhdTXzV6dJQCqSVcpFJ6FkPJn4kJtJjbdqPJd4k1jBFHVrLKd',
                    'amount_xmr' => $amount / 160, // Rough XMR conversion
                    'network' => 'Monero',
                ];
            case 'solana':
                return [
                    'address' => 'DYw8jCTfKHYCJUQV8vDDsD2TqB5SJJQTXjQTXjQTXjQT',
                    'amount_sol' => $amount / 20, // Rough SOL conversion
                    'network' => 'Solana',
                ];
            default:
                return [
                    'redirect_url' => '/payment/' . $transactionId,
                    'amount' => $amount,
                ];
        }
    }
}
