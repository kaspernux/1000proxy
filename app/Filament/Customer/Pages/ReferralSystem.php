<?php

namespace App\Filament\Customer\Pages;

use Filament\Pages\Page;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\Action;
use Filament\Actions\Action as PageAction;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Contracts\HasForms;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use App\Models\Customer;
use App\Models\Order;
use Filament\Notifications\Notification;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Forms\Concerns\InteractsWithForms;

class ReferralSystem extends Page implements HasTable, HasForms
{
    use InteractsWithTable;
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-gift';
    protected static ?string $navigationLabel = 'Referral Program';
    protected static string $view = 'filament.customer.pages.referral-system';
    protected static ?int $navigationSort = 5;

    public $referralCode;
    public $referralStats = [];
    public $shareableLinks = [];

    public function mount(): void
    {
        $this->loadReferralData();
    }

    protected function getHeaderActions(): array
    {
        return [
            PageAction::make('generate_code')
                ->label('Generate New Code')
                ->icon('heroicon-o-arrow-path')
                ->action('generateNewReferralCode')
                ->visible(fn () => !$this->referralCode),

            PageAction::make('share_referral')
                ->label('Share Referral')
                ->icon('heroicon-o-share')
                ->form([
                    Section::make('Share Your Referral')
                        ->description('Share your referral code and earn rewards')
                        ->schema([
                            Select::make('platform')
                                ->label('Share Platform')
                                ->options([
                                    'email' => 'Email',
                                    'telegram' => 'Telegram',
                                    'twitter' => 'Twitter',
                                    'whatsapp' => 'WhatsApp',
                                    'copy' => 'Copy Link',
                                ])
                                ->required(),

                            Textarea::make('message')
                                ->label('Custom Message (Optional)')
                                ->placeholder('Add a personal message to your referral...')
                                ->rows(3),
                        ]),
                ])
                ->action('shareReferral'),

            PageAction::make('withdrawal')
                ->label('Withdraw Earnings')
                ->icon('heroicon-o-banknotes')
                ->color('success')
                ->action('requestWithdrawal')
                ->visible(fn () => $this->referralStats['available_earnings'] > 0),
        ];
    }

    protected function loadReferralData(): void
    {
        $customer = Auth::guard('customer')->user();

        // Load or generate referral code
        $this->referralCode = $customer->referral_code ?? $this->generateReferralCode();

        // Load referral statistics
        $this->referralStats = [
            'total_referrals' => $this->getTotalReferrals(),
            'active_referrals' => $this->getActiveReferrals(),
            'total_earnings' => $this->getTotalEarnings(),
            'available_earnings' => $this->getAvailableEarnings(),
            'pending_earnings' => $this->getPendingEarnings(),
            'conversion_rate' => $this->getConversionRate(),
        ];

        // Generate shareable links
        $this->shareableLinks = $this->generateShareableLinks();
    }

    protected function generateReferralCode(): string
    {
        $customer = Auth::guard('customer')->user();
        $code = strtoupper(Str::random(8));

        // In real implementation, save to database
        // $customer->update(['referral_code' => $code]);

        return $code;
    }

    public function generateNewReferralCode(): void
    {
        $this->referralCode = $this->generateReferralCode();

        Notification::make()
            ->title('New Referral Code Generated')
            ->body('Your new referral code: ' . $this->referralCode)
            ->success()
            ->send();
    }

    public function shareReferral(array $data): void
    {
        $platform = $data['platform'];
        $customMessage = $data['message'] ?? '';

        $referralUrl = $this->shareableLinks['direct_link'];

        $message = $customMessage ?: "Join me on 1000proxy and get premium proxy services! Use my referral code: {$this->referralCode}";

        switch ($platform) {
            case 'email':
                // Generate mailto link
                $subject = 'Join 1000proxy with my referral';
                $emailLink = "mailto:?subject=" . urlencode($subject) . "&body=" . urlencode($message . "\n\n" . $referralUrl);
                break;

            case 'telegram':
                $telegramText = urlencode($message . "\n\n" . $referralUrl);
                $emailLink = "https://t.me/share/url?url=" . urlencode($referralUrl) . "&text=" . $telegramText;
                break;

            case 'twitter':
                $twitterText = urlencode($message . " " . $referralUrl);
                $emailLink = "https://twitter.com/intent/tweet?text=" . $twitterText;
                break;

            case 'whatsapp':
                $whatsappText = urlencode($message . "\n\n" . $referralUrl);
                $emailLink = "https://wa.me/?text=" . $whatsappText;
                break;

            case 'copy':
                $emailLink = $referralUrl;
                break;

            default:
                $emailLink = $referralUrl;
        }

        Notification::make()
            ->title('Referral Shared')
            ->body($platform === 'copy' ? 'Referral link copied to clipboard!' : 'Opening share dialog...')
            ->success()
            ->send();
    }

    public function requestWithdrawal(): void
    {
        $availableEarnings = $this->referralStats['available_earnings'];

        if ($availableEarnings <= 0) {
            Notification::make()
                ->title('No Earnings Available')
                ->body('You have no available earnings to withdraw.')
                ->warning()
                ->send();
            return;
        }

        // Process withdrawal request
        Notification::make()
            ->title('Withdrawal Requested')
            ->body("Withdrawal of \${$availableEarnings} has been requested. It will be processed within 24 hours.")
            ->success()
            ->send();
    }

    protected function getTotalReferrals(): int
    {
        // In real implementation, count referred customers
        return rand(5, 50);
    }

    protected function getActiveReferrals(): int
    {
        // Count active referred customers
        return rand(3, 30);
    }

    protected function getTotalEarnings(): float
    {
        // Calculate total earnings from referrals
        return rand(50, 500) + (rand(0, 99) / 100);
    }

    protected function getAvailableEarnings(): float
    {
        // Calculate available earnings (completed commissions)
        return rand(20, 200) + (rand(0, 99) / 100);
    }

    protected function getPendingEarnings(): float
    {
        // Calculate pending earnings (unconfirmed commissions)
        return rand(10, 100) + (rand(0, 99) / 100);
    }

    protected function getConversionRate(): float
    {
        // Calculate conversion rate percentage
        return rand(10, 80) + (rand(0, 99) / 100);
    }

    protected function generateShareableLinks(): array
    {
        $baseUrl = config('app.url');
        $code = $this->referralCode;

        return [
            'direct_link' => "{$baseUrl}/register?ref={$code}",
            'qr_code' => "{$baseUrl}/qr/{$code}",
            'short_link' => "{$baseUrl}/r/{$code}",
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                // In real implementation, this would be a referrals table or related customers
                collect([
                    (object) [
                        'id' => 1,
                        'name' => 'John Doe',
                        'email' => 'john@example.com',
                        'joined_at' => now()->subDays(30),
                        'status' => 'Active',
                        'orders_count' => 3,
                        'total_spent' => 150.00,
                        'commission_earned' => 15.00,
                        'commission_status' => 'Paid',
                    ],
                    (object) [
                        'id' => 2,
                        'name' => 'Jane Smith',
                        'email' => 'jane@example.com',
                        'joined_at' => now()->subDays(15),
                        'status' => 'Active',
                        'orders_count' => 1,
                        'total_spent' => 50.00,
                        'commission_earned' => 5.00,
                        'commission_status' => 'Pending',
                    ],
                    (object) [
                        'id' => 3,
                        'name' => 'Bob Wilson',
                        'email' => 'bob@example.com',
                        'joined_at' => now()->subDays(60),
                        'status' => 'Inactive',
                        'orders_count' => 0,
                        'total_spent' => 0.00,
                        'commission_earned' => 0.00,
                        'commission_status' => 'None',
                    ],
                ])->toQuery()
            )
            ->columns([
                TextColumn::make('name')
                    ->label('Referred Customer')
                    ->sortable()
                    ->searchable()
                    ->weight(FontWeight::Bold),

                TextColumn::make('email')
                    ->label('Email')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('joined_at')
                    ->label('Joined Date')
                    ->dateTime()
                    ->sortable()
                    ->since(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Active' => 'success',
                        'Inactive' => 'danger',
                        'Pending' => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('orders_count')
                    ->label('Orders')
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                TextColumn::make('total_spent')
                    ->label('Total Spent')
                    ->money('USD')
                    ->sortable(),

                TextColumn::make('commission_earned')
                    ->label('Your Commission')
                    ->money('USD')
                    ->sortable()
                    ->weight(FontWeight::Bold),

                TextColumn::make('commission_status')
                    ->label('Commission Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Paid' => 'success',
                        'Pending' => 'warning',
                        'Processing' => 'info',
                        'None' => 'gray',
                        default => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'Active' => 'Active',
                        'Inactive' => 'Inactive',
                        'Pending' => 'Pending',
                    ]),

                SelectFilter::make('commission_status')
                    ->options([
                        'Paid' => 'Paid',
                        'Pending' => 'Pending',
                        'Processing' => 'Processing',
                        'None' => 'None',
                    ]),
            ])
            ->actions([
                Action::make('view_details')
                    ->label('View Details')
                    ->icon('heroicon-o-eye')
                    ->modalHeading('Referral Details')
                    ->modalContent(view('filament.customer.components.referral-details'))
                    ->modalActions([
                        \Filament\Actions\Action::make('close')
                            ->label('Close')
                            ->color('gray'),
                    ]),

                Action::make('send_reminder')
                    ->label('Send Reminder')
                    ->icon('heroicon-o-envelope')
                    ->action(function ($record) {
                        Notification::make()
                            ->title('Reminder Sent')
                            ->body("Reminder sent to {$record->name}")
                            ->success()
                            ->send();
                    })
                    ->visible(fn ($record) => $record->status === 'Inactive'),
            ])
            ->defaultSort('joined_at', 'desc')
            ->emptyStateHeading('No Referrals Yet')
            ->emptyStateDescription('Start sharing your referral code to earn commissions!')
            ->emptyStateIcon('heroicon-o-user-group');
    }

    public function getReferralCode(): string
    {
        return $this->referralCode;
    }

    public function getReferralStats(): array
    {
        return $this->referralStats;
    }

    public function getShareableLinks(): array
    {
        return $this->shareableLinks;
    }

    public function getReferralTiers(): array
    {
        return [
            [
                'name' => 'Bronze',
                'min_referrals' => 0,
                'commission_rate' => 10,
                'bonuses' => ['Basic support'],
            ],
            [
                'name' => 'Silver',
                'min_referrals' => 10,
                'commission_rate' => 15,
                'bonuses' => ['Priority support', '+5% commission'],
            ],
            [
                'name' => 'Gold',
                'min_referrals' => 25,
                'commission_rate' => 20,
                'bonuses' => ['VIP support', '+10% commission', 'Monthly bonus'],
            ],
            [
                'name' => 'Platinum',
                'min_referrals' => 50,
                'commission_rate' => 25,
                'bonuses' => ['Dedicated account manager', '+15% commission', 'Quarterly bonus'],
            ],
        ];
    }

    public function getCurrentTier(): array
    {
        $referrals = $this->referralStats['total_referrals'];
        $tiers = $this->getReferralTiers();

        $currentTier = $tiers[0];

        foreach ($tiers as $tier) {
            if ($referrals >= $tier['min_referrals']) {
                $currentTier = $tier;
            }
        }

        return $currentTier;
    }

    public function getNextTier(): ?array
    {
        $referrals = $this->referralStats['total_referrals'];
        $tiers = $this->getReferralTiers();

        foreach ($tiers as $tier) {
            if ($referrals < $tier['min_referrals']) {
                return $tier;
            }
        }

        return null;
    }
}
