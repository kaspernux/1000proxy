<?php

namespace App\Filament\Customer\Pages;

use Filament\Pages\Page;
use Filament\Forms\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\Action;
use Filament\Actions\Action as PageAction;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Contracts\HasForms;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;
use App\Models\Customer;
use App\Models\Order;
use Filament\Notifications\Notification;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Forms\Concerns\InteractsWithForms;
use BackedEnum;

class ReferralSystem extends Page implements HasTable, HasForms
{
    use InteractsWithTable;
    use InteractsWithForms;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-gift';
    protected static ?string $navigationLabel = 'Referral Program';
    protected string $view = 'filament.customer.pages.referral-system';
    protected static ?int $navigationSort = 8;

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

        // Load or generate referral code and persist it if missing
        if (Schema::hasColumn('customers', 'referral_code')) {
            if (!$customer->referral_code) {
                // Use forceFill + saveQuietly to avoid mass-assignment blocks and events
                $customer->forceFill(['referral_code' => $this->generateReferralCode()])->saveQuietly();
                // Refresh to see updated value
                $customer->refresh();
            }
            $this->referralCode = $customer->referral_code;
        } else {
            // Fallback gracefully if migration hasn't run yet
            $this->referralCode = $customer->refcode;
        }

        // Load referral statistics
        $this->referralStats = [
            'total_referrals' => $this->getTotalReferrals(),
            'active_referrals' => $this->getActiveReferrals(),
            'total_earnings' => $this->getTotalEarnings(),
            'available_earnings' => $this->getAvailableEarnings(),
            'pending_earnings' => $this->getPendingEarnings(),
            'conversion_rate' => $this->getConversionRate(),
            'monthly_referrals' => $this->getMonthlyReferrals(),
            'avg_commission' => $this->getAverageCommission(),
        ];

        // Generate shareable links
        $this->shareableLinks = $this->generateShareableLinks();
    }

    protected function generateReferralCode(): string
    {
    return strtoupper(Str::random(8));
    }

    public function generateNewReferralCode(): void
    {
    $customer = Auth::guard('customer')->user();
    $code = $this->generateReferralCode();
        if (Schema::hasColumn('customers', 'referral_code')) {
            $customer->forceFill(['referral_code' => $code])->saveQuietly();
        } else {
            // Fallback to legacy field if needed
            $customer->forceFill(['refcode' => $code])->saveQuietly();
        }
    $this->referralCode = $code;

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

        // Create a pending withdrawal request (admin can process later)
        $me = Auth::guard('customer')->user();
        \App\Models\ReferralWithdrawal::create([
            'customer_id' => $me->id,
            'amount' => $availableEarnings,
            'status' => 'pending',
            'metadata' => [
                'note' => 'Requested from Referral System page',
            ],
        ]);

        Notification::make()
            ->title('Withdrawal Requested')
            ->body("Withdrawal of $" . number_format($availableEarnings, 2) . " has been requested. You'll be contacted for payout details.")
            ->success()
            ->send();
    }

    protected function getTotalReferrals(): int
    {
    $me = Auth::guard('customer')->user();
    return Customer::query()->where('refered_by', $me->id)->count();
    }

    protected function getActiveReferrals(): int
    {
        $me = Auth::guard('customer')->user();
        return \App\Models\Order::query()
            ->whereIn('customer_id', function($q) use ($me) { $q->select('id')->from('customers')->where('refered_by', $me->id); })
            ->where('payment_status', 'paid')
            ->distinct('customer_id')
            ->count('customer_id');
    }

    protected function getTotalEarnings(): float
    {
        $me = Auth::guard('customer')->user();
        // Sum wallet credits tagged as referral
        $sum = \App\Models\WalletTransaction::query()
            ->where('customer_id', $me->id)
            ->where('type', 'credit')
            ->where('metadata->referral', true)
            ->sum('amount');
        return (float) $sum;
    }

    protected function getAvailableEarnings(): float
    {
    // For now, available equals total credited referral amounts (no holds implemented)
    return $this->getTotalEarnings();
    }

    protected function getPendingEarnings(): float
    {
    // No pending tracking yet; could be implemented with holds after N days
    return 0.0;
    }

    protected function getConversionRate(): float
    {
    $total = $this->getTotalReferrals();
    $active = $this->getActiveReferrals();
    return $total > 0 ? round(($active / max(1,$total)) * 100, 1) : 0.0;
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

    protected function getMonthlyReferrals(): int
    {
        $me = Auth::guard('customer')->user();
        return Customer::query()
            ->where('refered_by', $me->id)
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();
    }

    protected function getAverageCommission(): float
    {
        $totalRef = max(1, $this->getTotalReferrals());
        return round($this->getTotalEarnings() / $totalRef, 2);
    }

    public function table(Table $table): Table
    {
        // Use Eloquent Builder for referred customers to avoid type error
        $customer = Auth::guard('customer')->user();
        return $table
            ->query(
                Customer::query()
                    ->where('refered_by', $customer->id)
                    ->withCount(['orders as orders_count' => function($q){ $q->where('payment_status','paid'); }])
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

                TextColumn::make('created_at')
                    ->label('Joined Date')
                    ->dateTime()
                    ->sortable()
                    ->since(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->getStateUsing(function ($record) {
                        $hasPaid = \App\Models\Order::where('customer_id', $record->id)->where('payment_status','paid')->exists();
                        return $hasPaid ? 'Active' : 'Pending';
                    })
                    ->color(fn ($state) => $state === 'Active' ? 'success' : 'warning'),

                TextColumn::make('orders_count')
                    ->label('Paid Orders')
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                TextColumn::make('total_spent')
                    ->label('Total Spent')
                    ->money('USD')
                    ->sortable()
                    ->getStateUsing(function ($record) {
                        return (float) \App\Models\Order::where('customer_id', $record->id)->where('payment_status','paid')->sum('total_amount');
                    }),

                TextColumn::make('commission_earned')
                    ->label('Your Commission')
                    ->money('USD')
                    ->sortable()
                    ->weight(FontWeight::Bold)
                    ->getStateUsing(function ($record) use ($customer) {
                        // Sum referrer's referral credits coming from this referred customer
                        $me = $customer;
                        return (float) \App\Models\WalletTransaction::query()
                            ->where('customer_id', $me->id)
                            ->where('type', 'credit')
                            ->where('metadata->referral', true)
                            ->where('metadata->referred_customer_id', $record->id)
                            ->sum('amount');
                    }),

                TextColumn::make('commission_status')
                    ->label('Commission Status')
                    ->badge()
                    ->getStateUsing(function ($record) use ($customer) {
                        $sum = (float) \App\Models\WalletTransaction::query()
                            ->where('customer_id', $customer->id)
                            ->where('type', 'credit')
                            ->where('metadata->referral', true)
                            ->where('metadata->referred_customer_id', $record->id)
                            ->sum('amount');
                        if ($sum > 0) { return 'Paid'; }
                        $hasPaid = \App\Models\Order::where('customer_id', $record->id)->where('payment_status','paid')->exists();
                        return $hasPaid ? 'Pending' : 'None';
                    })
                    ->color(fn ($state) => match ($state) { 'Paid' => 'success', 'Pending' => 'warning', default => 'gray' }),
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
            ->defaultSort('created_at', 'desc')
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

    /**
     * Progress (0-100) toward next referral tier.
     */
    public function getTierProgress(): array
    {
        $current = $this->getCurrentTier();
        $next = $this->getNextTier();
        $total = $this->referralStats['total_referrals'] ?? 0;

        if (!$next) {
            return [
                'percentage' => 100,
                'remaining' => 0,
                'label' => 'Max tier reached',
            ];
        }

        $neededForCurrent = $current['min_referrals'];
        $neededForNext = $next['min_referrals'];
        $range = max(1, $neededForNext - $neededForCurrent);
        $progressInRange = max(0, $total - $neededForCurrent);
        $percentage = min(100, round(($progressInRange / $range) * 100));

        return [
            'percentage' => $percentage,
            'remaining' => max(0, $neededForNext - $total),
            'label' => $next['name'] . ' in ' . max(0, $neededForNext - $total) . ' referrals',
        ];
    }
}
