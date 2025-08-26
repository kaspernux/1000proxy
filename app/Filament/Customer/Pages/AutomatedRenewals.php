<?php

namespace App\Filament\Customer\Pages;

use Filament\Pages\Page;
use Filament\Forms\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
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
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Customer;
use App\Models\ServerClient;
use Filament\Notifications\Notification;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Carbon\Carbon;
use BackedEnum;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

class AutomatedRenewals extends Page implements HasTable, HasForms
{
    use InteractsWithTable;
    use InteractsWithForms;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-arrow-path';
    protected static ?string $navigationLabel = 'Auto Renewals';
    protected string $view = 'filament.customer.pages.automated-renewals';
    protected static ?int $navigationSort = 7;

    public $renewalSettings = [
        'auto_renew_enabled' => false,
        'renewal_buffer_days' => 7,
        'payment_method' => 'wallet',
        'notification_days' => [7, 3, 1],
    ];

    public function mount(): void
    {
        $customer = Auth::guard('customer')->user();
        $this->loadRenewalSettings();
    }

    protected function getHeaderActions(): array
    {
        return [
            PageAction::make('configure')
                ->label('Configure Auto-Renewal')
                ->icon('heroicon-o-cog-6-tooth')
                ->form([
                    Section::make('Auto-Renewal Settings')
                        ->description('Configure automatic renewal preferences for your services')
                        ->schema([
                            Toggle::make('auto_renew_enabled')
                                ->label('Enable Auto-Renewal')
                                ->helperText('Automatically renew services before expiration'),

                            Select::make('renewal_buffer_days')
                                ->label('Renewal Buffer Days')
                                ->options([
                                    1 => '1 day before',
                                    2 => '2 days before',
                                    3 => '3 days before',
                                ])
                                ->helperText('Wallet-only: renewal will be attempted 1–3 days before expiration'),

                            Select::make('payment_method')
                                ->label('Payment Method')
                                ->options([
                                    'wallet' => 'Wallet Balance',
                                ])
                                ->helperText('Auto-renewals are supported only via Wallet balance')
                                ->disabled(),

                            Select::make('notification_days')
                                ->label('Notification Schedule')
                                ->multiple()
                                ->options([
                                    30 => '30 days before',
                                    14 => '14 days before',
                                    7 => '7 days before',
                                    3 => '3 days before',
                                    1 => '1 day before',
                                ])
                                ->helperText('When to send renewal reminders'),
                        ]),
                ])
                ->action('saveRenewalSettings'),
        ];
    }

    public function saveRenewalSettings(?array $data = null): void
    {
        $customer = Auth::guard('customer')->user();

        $incoming = $data ?? [];
        // Clamp to wallet-only and buffer 1-3 days
        $buffer = (int) ($incoming['renewal_buffer_days'] ?? $this->renewalSettings['renewal_buffer_days'] ?? 3);
        if (!in_array($buffer, [1,2,3], true)) { $buffer = 3; }

        $auto = (bool) ($incoming['auto_renew_enabled'] ?? $this->renewalSettings['auto_renew_enabled'] ?? false);

        $this->renewalSettings = array_merge($this->renewalSettings, [
            'auto_renew_enabled' => $auto,
            'renewal_buffer_days' => $buffer,
            'payment_method' => 'wallet',
            'notification_days' => $incoming['notification_days'] ?? $this->renewalSettings['notification_days'] ?? [3,2,1],
        ]);

        Notification::make()
            ->title('Settings Saved')
            ->body('Auto-renewal settings have been updated successfully.')
            ->success()
            ->send();
    }

    protected function loadRenewalSettings(): void
    {
        $customer = Auth::guard('customer')->user();
        // Load existing settings from database (placeholder implementation)
        // In real implementation, these would come from a user_settings table
        $this->renewalSettings = [
            'auto_renew_enabled' => $customer->auto_renew_enabled ?? false,
            'renewal_buffer_days' => $customer->renewal_buffer_days ?? 7,
            'payment_method' => $customer->renewal_payment_method ?? 'wallet',
            'notification_days' => $customer->renewal_notification_days ?? [7, 3, 1],
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                OrderItem::query()
                    ->whereHas('order', function ($q) {
                        $q->where('customer_id', Auth::guard('customer')->id());
                    })
                    ->with(['serverPlan.server', 'order', 'serverClients.server', 'serverClients.inbound'])
            )
            ->columns([
                TextColumn::make('order_id')
                    ->label('Order #')
                    ->sortable()
                    ->searchable()
                    ->prefix('#'),

                TextColumn::make('serverPlan.name')
                    ->label('Plan')
                    ->sortable()
                    ->searchable()
                    ->weight(FontWeight::Bold),

                TextColumn::make('serverPlan.server.name')
                    ->label('Server')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('expires_at')
                    ->label('Expires')
                    ->date('M j, Y')
                    ->sortable()
                    ->color(function (OrderItem $record) {
                        $expiresAt = $record->expires_at;
                        if (!$expiresAt) return 'gray';
                        $daysLeft = now()->diffInDays($expiresAt);
                        return match (true) {
                            $daysLeft <= 1 => 'danger',
                            $daysLeft <= 7 => 'warning',
                            default => 'success',
                        };
                    }),

                TextColumn::make('days_until_expiry')
                    ->label('Days Left')
                    ->getStateUsing(function (OrderItem $record) {
                        $expiresAt = $record->expires_at;
                        return $expiresAt ? now()->diffInDays($expiresAt) : '—';
                    })
                    ->badge()
                    ->color(fn (string $state): string => match (true) {
                        $state === '—' => 'gray',
                        intval($state) <= 1 => 'danger',
                        intval($state) <= 7 => 'warning',
                        default => 'success',
                    }),

                \Filament\Tables\Columns\ToggleColumn::make('auto_renew')
                    ->label('Auto-Renew')
                    ->alignCenter()
                    ->updateStateUsing(function (OrderItem $record, $state) {
                        // Map to provisioning_summary safely and persist
                        $sum = $record->provisioning_summary ?? [];
                        $sum['auto_renew_enabled'] = (bool) $state;
                        $buffer = (int) ($sum['renewal_buffer_days'] ?? ($this->renewalSettings['renewal_buffer_days'] ?? 3));
                        if (! in_array($buffer, [1,2,3], true)) { $buffer = 3; }
                        $sum['renewal_buffer_days'] = $buffer;
                        $record->provisioning_summary = $sum;
                        $record->save();
                        // Return the computed state for UI
                        return $sum['auto_renew_enabled'];
                    }),

                TextColumn::make('renewal_status')
                    ->label('Status')
                    ->getStateUsing(function (OrderItem $record) {
                        $expiresAt = $record->expires_at;
                        if (!$expiresAt) return 'No expiration';

                        if (!$this->isEligibleOrderItem($record)) {
                            return 'Ineligible (inactive service)';
                        }

                        $auto = (bool) ($record->provisioning_summary['auto_renew_enabled'] ?? false);
                        $buffer = (int) ($record->provisioning_summary['renewal_buffer_days'] ?? $this->renewalSettings['renewal_buffer_days'] ?? 3);
                        if (!in_array($buffer, [1,2,3], true)) { $buffer = 3; }

                        $daysLeft = now()->diffInDays($expiresAt, false);
                        $inWindow = $daysLeft >= 1 && $daysLeft <= 3 && $daysLeft <= $buffer;

                        if (! $auto) {
                            return 'Manual renewal required';
                        }

                        $customer = Auth::guard('customer')->user();
                        $cost = $this->estimateRenewalCost($record);
                        if ($inWindow) {
                            if ($customer->hasSufficientWalletBalance($cost)) {
                                return 'Scheduled for renewal';
                            }
                            $this->scheduleLowBalanceEmail($customer, $record, $cost);
                            return 'Insufficient balance (email scheduled)';
                        }

                        return 'Auto-renewal enabled';
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Scheduled for renewal' => 'warning',
                        'Auto-renewal enabled' => 'success',
                        'Manual renewal required' => 'danger',
                        'Ineligible (inactive service)' => 'gray',
                        'Insufficient balance (email scheduled)' => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('next_renewal_attempt')
                    ->label('Next Renewal')
                    ->getStateUsing(function (OrderItem $record) {
                        $auto = (bool) ($record->provisioning_summary['auto_renew_enabled'] ?? false);
                        if (! $auto) return '—';
                        $buffer = (int) ($record->provisioning_summary['renewal_buffer_days'] ?? $this->renewalSettings['renewal_buffer_days'] ?? 3);
                        if (!in_array($buffer, [1,2,3], true)) { $buffer = 3; }
                        $expiresAt = $record->expires_at;
                        if (!$expiresAt) return '—';
                        $renewalDate = Carbon::parse($expiresAt)->subDays($buffer);
                        return $renewalDate->format('M j, Y');
                    }),
            ])
            ->filters([
                SelectFilter::make('expiration')
                    ->options([
                        'expired' => 'Expired',
                        'expiring_soon' => 'Expiring Soon (7 days)',
                        'expiring_month' => 'Expiring This Month',
                        'active' => 'Active',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return match ($data['value'] ?? null) {
                            'expired' => $query->where('expires_at', '<', now()),
                            'expiring_soon' => $query->whereBetween('expires_at', [now(), now()->addWeek()]),
                            'expiring_month' => $query->whereBetween('expires_at', [now(), now()->addMonth()]),
                            'active' => $query->where('expires_at', '>', now()),
                            default => $query,
                        };
                    }),

                SelectFilter::make('auto_renew')
                    ->options([
                        'enabled' => 'Auto-Renewal Enabled',
                        'disabled' => 'Auto-Renewal Disabled',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        // In real implementation, this would filter based on stored settings
                        return $query;
                    }),
            ])
            ->actions([
                Action::make('renew_now')
                    ->label('Renew Now')
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->action(function (OrderItem $record) {
                        $item = $record;
                        if (!$item || !$this->isEligibleOrderItem($item)) {
                            Notification::make()->title('Service not eligible')->danger()->send();
                            return;
                        }
                        $expiresAt = $item->expires_at;
                        $daysLeft = $expiresAt ? now()->diffInDays($expiresAt, false) : null;
                        if ($daysLeft === null || $daysLeft < 1 || $daysLeft > 3) {
                            Notification::make()->title('Outside renewal window (1–3 days before)')->warning()->send();
                            return;
                        }
                        $customer = Auth::guard('customer')->user();
                        $cost = $this->estimateRenewalCost($item);
                        if (! $customer->hasSufficientWalletBalance($cost)) {
                            Notification::make()->title('Insufficient Wallet Balance')->danger()->body('Please top up your wallet to renew.')->send();
                            return;
                        }
                        $customer->payFromWallet($cost, 'Service Auto-Renewal');
                        Notification::make()
                            ->title('Renewal Initiated')
                            ->body('Your service renewal has been charged from your wallet.')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (OrderItem $record) => $this->canRenewNowItem($record)),

                Action::make('view_renewal_schedule')
                    ->label('View Schedule')
                    ->icon('heroicon-o-calendar')
                    ->modalHeading('Renewal Schedule')
                    ->modalContent(function () {
                        $upcoming = $this->getUpcomingRenewals();
                        return view('filament.customer.components.renewal-schedule', [
                            'upcomingRenewals' => $upcoming,
                        ]);
                    })
                    ->modalActions([
                        \Filament\Actions\Action::make('close')
                            ->label('Close')
                            ->color('gray'),
                    ]),
            ])
            ->bulkActions([
                \Filament\Actions\BulkAction::make('enable_auto_renew')
                    ->label('Enable Auto-Renewal')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->action(function (Collection $records) {
                        $count = 0;
                        foreach ($records as $record) {
                            if ($record instanceof OrderItem) {
                                $sum = $record->provisioning_summary ?? [];
                                $sum['auto_renew_enabled'] = true;
                                $sum['renewal_buffer_days'] = in_array(($sum['renewal_buffer_days'] ?? 3), [1,2,3], true) ? $sum['renewal_buffer_days'] : 3;
                                $record->update(['provisioning_summary' => $sum]);
                                $count++;
                            }
                        }
                        Notification::make()->title('Auto-Renewal Enabled')->body('Enabled for ' . $count . ' services.')->success()->send();
                    }),

                \Filament\Actions\BulkAction::make('disable_auto_renew')
                    ->label('Disable Auto-Renewal')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->action(function (Collection $records) {
                        $count = 0;
                        foreach ($records as $record) {
                            if ($record instanceof OrderItem) {
                                $sum = $record->provisioning_summary ?? [];
                                $sum['auto_renew_enabled'] = false;
                                $record->update(['provisioning_summary' => $sum]);
                                $count++;
                            }
                        }
                        Notification::make()->title('Auto-Renewal Disabled')->body('Disabled for ' . $count . ' services.')->warning()->send();
                    }),
            ])
            // Remove defaultSort on orderItems.expires_at, as this column does not exist on orders table
            ->poll('60s'); // Refresh every minute
    }

    protected function getExpirationColor($record): string
    {
        $expiresAt = $record->items->first()?->expires_at;
        if (!$expiresAt) return 'gray';

        $daysLeft = now()->diffInDays($expiresAt);

        return match (true) {
            $daysLeft <= 1 => 'danger',
            $daysLeft <= 7 => 'warning',
            default => 'success',
        };
    }

    protected function canRenewNowItem(OrderItem $record): bool
    {
    $item = $record;
    if (! $this->isEligibleOrderItem($item)) return false;

    $expiresAt = $item->expires_at;
    if (!$expiresAt) return false;

    $daysLeft = now()->diffInDays($expiresAt, false);
    // Only allow renew now within 1–3 day window
    return $daysLeft >= 1 && $daysLeft <= 3;
    }

    public function getRenewalSettings(): array
    {
        return $this->renewalSettings;
    }

    public function getUpcomingRenewals(): Collection
    {
        $customer = Auth::guard('customer')->user();
        $buffer = (int) ($this->renewalSettings['renewal_buffer_days'] ?? 3);
        if (!in_array($buffer, [1,2,3], true)) { $buffer = 3; }

        return OrderItem::query()
            ->whereHas('order', fn ($q) => $q->where('customer_id', $customer->id))
            ->whereBetween('expires_at', [now(), now()->addDays($buffer)])
            ->with(['serverPlan.server'])
            ->orderBy('expires_at')
            ->get();
    }

    private function isEligibleOrderItem(OrderItem $item): bool
    {
        $client = $item->server_client; // virtual accessor to first client
        if (! $client) return false;
        if (($client->status ?? 'active') !== 'active') return false;
        if (! $client->inbound || ! $client->server) return false;
        if (($client->inbound->status ?? 'active') !== 'active') return false;
        if (! ($client->enable ?? true)) return false;
        return true;
    }

    private function estimateRenewalCost(OrderItem $item): float
    {
        $plan = $item->serverPlan;
        return (float) ($plan->price ?? 0);
    }

    private function scheduleLowBalanceEmail(Customer $customer, OrderItem $item, float $cost): void
    {
        // throttle key per customer+item per day
        $key = 'renewal_low_balance_notified:' . $customer->id . ':' . $item->id . ':' . now()->format('Y-m-d');
        if (Cache::has($key)) {
            return;
        }
        Cache::put($key, true, now()->addDay());

        try {
            $details = [
                'subject' => 'Low Wallet Balance for Upcoming Renewal',
                'greeting' => 'Hello ' . ($customer->name ?? 'Customer'),
                'line1' => 'We attempted to schedule an automatic renewal, but your wallet balance is insufficient.',
                'line2' => 'Service: ' . ($item->serverPlan->name ?? 'Plan') . ' • Cost: $' . number_format($cost, 2),
                'actionText' => 'Top Up Wallet',
                'actionUrl' => url('/account/wallet'),
            ];
            Mail::raw($details['greeting'] . "\n\n" . $details['line1'] . "\n" . $details['line2'] . "\n\n" . 'Top up here: ' . $details['actionUrl'], function ($m) use ($customer, $details) {
                $m->to($customer->email)->subject($details['subject']);
            });
        } catch (\Throwable $e) {
            // swallow
        }
    }
}
