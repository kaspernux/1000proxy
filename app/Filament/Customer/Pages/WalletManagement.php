<?php

namespace App\Filament\Customer\Pages;

use Filament\Pages\Page;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Hidden;
use Filament\Schemas\Schema;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Actions\Action as PageAction;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use BackedEnum;

class WalletManagement extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-wallet';
    protected static ?string $navigationLabel = 'My Wallet';
    protected string $view = 'filament.customer.pages.wallet-management';
    protected static ?int $navigationSort = 4;

    public ?array $data = [];
    public $walletBalance = 0;
    public $walletId = null;

    public function mount(): void
    {
        $customer = Auth::guard('customer')->user();
        $wallet = Wallet::firstOrCreate(
            ['customer_id' => $customer->id],
            ['balance' => 0.00, 'currency' => 'USD']
        );

        $this->walletId = $wallet->id;
        $this->walletBalance = (float) $wallet->balance;

        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
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
            ->heading('Recent Transactions')
            ->description('Your most recent wallet transactions. Use Full History to see all records.')
            ->query(function () {
                $customer = Auth::guard('customer')->user();
                return WalletTransaction::query()
                    ->where('customer_id', $customer->id)
                    ->latest();
            })
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
                    ->formatStateUsing(function ($state, $record) {
                        $sign = in_array($record->type, ['deposit','refund']) ? '+' : '-';
                        return $sign . '$' . number_format((float) $state, 2);
                    })
                    ->color(fn ($record): string => in_array($record->type, ['deposit','refund']) ? 'success' : 'danger')
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
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50]);
    }

    protected function getHeaderActions(): array
    {
        return [
            PageAction::make('add_funds')
                ->label('Add Funds')
                ->icon('heroicon-o-plus')
                ->color('primary')
                ->action(function () {
                    // Redirect to Livewire TopupWallet page (default to BTC)
                    return redirect()->to(route('customer.wallet.topup', ['currency' => 'btc']));
                }),

            PageAction::make('request_withdrawal')
                ->label('Request Refund')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('warning')
                ->action(function () {
                    $customer = Auth::guard('customer')->user();
                    $mailto = 'mailto:support@1000proxy.io'
                        . '?subject=' . rawurlencode('Refund Request - ' . ($customer->email ?? 'customer'))
                        . '&body=' . rawurlencode("Hello 1000proxy support,%0D%0A%0D%0AI'd like to request a refund. I acknowledge refunds are processed via Monero (XMR) with a 30% commission.%0D%0A%0D%0AMy account email: " . ($customer->email ?? '') . "%0D%0AXMR address: %0D%0ATransaction reference(s): %0D%0AOrder ID (if any): %0D%0A%0D%0AThanks.");

                    Notification::make()
                        ->title('Refund policy')
                        ->body('Refunds are handled by support and paid only via Monero (XMR) with a 30% commission. Your email app will open to contact support.')
                        ->warning()
                        ->persistent()
                        ->send();

                    return redirect()->away($mailto);
                }),

            PageAction::make('transaction_history')
                ->label('Full History')
                ->icon('heroicon-o-document-text')
                ->color('info')
                ->url(fn (): string => route('transactions.index')),
        ];
    }

    public function requestWithdrawal(array $data = null): void
    {
        Notification::make()
            ->title('Contact Support to Withdraw')
            ->body('For security reasons, withdrawals are handled by our support team. Please contact support@1000proxy.io or use in-app chat to initiate your withdrawal.')
            ->warning()
            ->persistent()
            ->send();
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

     /**
     * Get available gateways for the top-up form, aligned with PaymentProcessor and PaymentController
     */
    public static function getAvailableGatewaysForForm(): array
    {
        // Dynamically load only active ("live") payment methods from DB
        try {
            $methods = \App\Models\PaymentMethod::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['slug','name','gateway','type']);

            // Map known crypto slugs to nicer labels / symbols
            $iconMap = [
                'bitcoin' => '₿',
                'btc' => '₿',
                'monero' => 'ⓧ',
                'xmr' => 'ⓧ',
                'solana' => '◎',
                'sol' => '◎',
                'ethereum' => 'Ξ',
                'eth' => 'Ξ',
                'usdt' => '₮',
            ];

            $options = [];
            foreach ($methods as $m) {
                // Exclude wallet itself as a funding source for top-up
                if (in_array($m->slug, ['wallet','internal-wallet'])) {
                    continue;
                }
                $symbol = $iconMap[$m->slug] ?? '';
                $label = trim(($symbol ? $symbol.' ' : '').$m->name);
                // Fallback if name missing
                if (!$label) {
                    $label = ucfirst($m->slug);
                }
                $options[$m->slug] = $label;
            }

            // If nothing configured yet, fall back to safe defaults
            if (empty($options)) {
                $options = [
                    'stripe' => 'Credit Card',
                    'paypal' => 'PayPal',
                ];
            }

            return $options;
        } catch (\Throwable $e) {
            // On any DB error, return minimal safe set so UI still works
            return [
                'stripe' => 'Credit Card',
                'paypal' => 'PayPal',
            ];
        }
    }

}
