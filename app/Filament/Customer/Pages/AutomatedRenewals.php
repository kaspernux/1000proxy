<?php

namespace App\Filament\Customer\Pages;

use Filament\Pages\Page;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
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
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Customer;
use App\Models\ServerClient;
use Filament\Notifications\Notification;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Carbon\Carbon;

class AutomatedRenewals extends Page implements HasTable, HasForms
{
    use InteractsWithTable;
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';
    protected static ?string $navigationLabel = 'Auto Renewals';
    protected static string $view = 'filament.customer.pages.automated-renewals';
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
                                    3 => '3 days before',
                                    7 => '7 days before',
                                    14 => '14 days before',
                                    30 => '30 days before',
                                ])
                                ->helperText('When to attempt renewal before expiration'),

                            Select::make('payment_method')
                                ->label('Payment Method')
                                ->options([
                                    'wallet' => 'Wallet Balance',
                                    'crypto' => 'Cryptocurrency',
                                    'card' => 'Credit Card',
                                ])
                                ->helperText('Preferred payment method for renewals'),

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

            PageAction::make('renewal_history')
                ->label('Renewal History')
                ->icon('heroicon-o-clock')
                ->url(route('filament.customer.pages.renewal-history')),
        ];
    }

    public function saveRenewalSettings(array $data): void
    {
        $customer = Auth::guard('customer')->user();

        // Save renewal settings (in real implementation, this would be stored in database)
        $this->renewalSettings = array_merge($this->renewalSettings, $data);

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
                Order::where('customer_id', Auth::guard('customer')->id())
                    ->whereHas('orderItems', function (Builder $query) {
                        $query->whereNotNull('expires_at');
                    })
                    ->with(['orderItems.serverClient.server'])
            )
            ->columns([
                TextColumn::make('id')
                    ->label('Order #')
                    ->sortable()
                    ->searchable()
                    ->prefix('#'),

                TextColumn::make('orderItems.serverClient.server.name')
                    ->label('Service')
                    ->sortable()
                    ->searchable()
                    ->weight(FontWeight::Bold),

                TextColumn::make('orderItems.expires_at')
                    ->label('Expires')
                    ->dateTime()
                    ->sortable()
                    ->color(fn ($record) => $this->getExpirationColor($record)),

                TextColumn::make('days_until_expiry')
                    ->label('Days Left')
                    ->getStateUsing(function ($record) {
                        $expiresAt = $record->orderItems->first()?->expires_at;
                        return $expiresAt ? now()->diffInDays($expiresAt) : 'N/A';
                    })
                    ->badge()
                    ->color(fn (string $state): string => match (true) {
                        $state === 'N/A' => 'gray',
                        intval($state) <= 1 => 'danger',
                        intval($state) <= 7 => 'warning',
                        default => 'success',
                    }),

                IconColumn::make('auto_renew_enabled')
                    ->label('Auto-Renew')
                    ->getStateUsing(fn () => $this->renewalSettings['auto_renew_enabled'])
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle'),

                TextColumn::make('renewal_status')
                    ->label('Status')
                    ->getStateUsing(function ($record) {
                        $expiresAt = $record->orderItems->first()?->expires_at;
                        if (!$expiresAt) return 'No expiration';

                        $daysLeft = now()->diffInDays($expiresAt);

                        if ($this->renewalSettings['auto_renew_enabled']) {
                            if ($daysLeft <= $this->renewalSettings['renewal_buffer_days']) {
                                return 'Scheduled for renewal';
                            }
                            return 'Auto-renewal enabled';
                        }

                        return 'Manual renewal required';
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Scheduled for renewal' => 'warning',
                        'Auto-renewal enabled' => 'success',
                        'Manual renewal required' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('next_renewal_attempt')
                    ->label('Next Renewal')
                    ->getStateUsing(function ($record) {
                        if (!$this->renewalSettings['auto_renew_enabled']) {
                            return 'N/A';
                        }

                        $expiresAt = $record->orderItems->first()?->expires_at;
                        if (!$expiresAt) return 'N/A';

                        $renewalDate = Carbon::parse($expiresAt)->subDays($this->renewalSettings['renewal_buffer_days']);
                        return $renewalDate->format('M j, Y');
                    })
                    ->date(),
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
                            'expired' => $query->whereHas('orderItems', fn ($q) => $q->where('expires_at', '<', now())),
                            'expiring_soon' => $query->whereHas('orderItems', fn ($q) => $q->whereBetween('expires_at', [now(), now()->addWeek()])),
                            'expiring_month' => $query->whereHas('orderItems', fn ($q) => $q->whereBetween('expires_at', [now(), now()->addMonth()])),
                            'active' => $query->whereHas('orderItems', fn ($q) => $q->where('expires_at', '>', now())),
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
                    ->action(function (Order $record) {
                        // Implement immediate renewal logic
                        Notification::make()
                            ->title('Renewal Initiated')
                            ->body('Your service renewal has been started.')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (Order $record) => $this->canRenewNow($record)),

                Action::make('toggle_auto_renew')
                    ->label(fn (Order $record) => $this->renewalSettings['auto_renew_enabled'] ? 'Disable Auto-Renew' : 'Enable Auto-Renew')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->color(fn (Order $record) => $this->renewalSettings['auto_renew_enabled'] ? 'danger' : 'success')
                    ->action(function (Order $record) {
                        $this->renewalSettings['auto_renew_enabled'] = !$this->renewalSettings['auto_renew_enabled'];

                        Notification::make()
                            ->title('Auto-Renewal ' . ($this->renewalSettings['auto_renew_enabled'] ? 'Enabled' : 'Disabled'))
                            ->body('Auto-renewal settings have been updated for this service.')
                            ->success()
                            ->send();
                    }),

                Action::make('view_renewal_schedule')
                    ->label('View Schedule')
                    ->icon('heroicon-o-calendar')
                    ->modalHeading('Renewal Schedule')
                    ->modalContent(view('filament.customer.components.renewal-schedule'))
                    ->modalActions([
                        \Filament\Actions\Action::make('close')
                            ->label('Close')
                            ->color('gray'),
                    ]),
            ])
            ->bulkActions([
                \Filament\Tables\Actions\BulkAction::make('enable_auto_renew')
                    ->label('Enable Auto-Renewal')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->action(function (Collection $records) {
                        Notification::make()
                            ->title('Auto-Renewal Enabled')
                            ->body('Auto-renewal has been enabled for ' . $records->count() . ' services.')
                            ->success()
                            ->send();
                    }),

                \Filament\Tables\Actions\BulkAction::make('disable_auto_renew')
                    ->label('Disable Auto-Renewal')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->action(function (Collection $records) {
                        Notification::make()
                            ->title('Auto-Renewal Disabled')
                            ->body('Auto-renewal has been disabled for ' . $records->count() . ' services.')
                            ->warning()
                            ->send();
                    }),
            ])
            ->defaultSort('orderItems.expires_at', 'asc')
            ->poll('60s'); // Refresh every minute
    }

    protected function getExpirationColor($record): string
    {
        $expiresAt = $record->orderItems->first()?->expires_at;
        if (!$expiresAt) return 'gray';

        $daysLeft = now()->diffInDays($expiresAt);

        return match (true) {
            $daysLeft <= 1 => 'danger',
            $daysLeft <= 7 => 'warning',
            default => 'success',
        };
    }

    protected function canRenewNow(Order $record): bool
    {
        $expiresAt = $record->orderItems->first()?->expires_at;
        if (!$expiresAt) return false;

        $daysLeft = now()->diffInDays($expiresAt);
        return $daysLeft <= 30; // Allow renewal within 30 days of expiration
    }

    public function getRenewalSettings(): array
    {
        return $this->renewalSettings;
    }

    public function getUpcomingRenewals(): Collection
    {
        $customer = Auth::guard('customer')->user();

        return Order::where('customer_id', $customer->id)
            ->whereHas('orderItems', function (Builder $query) {
                $query->whereBetween('expires_at', [
                    now(),
                    now()->addDays($this->renewalSettings['renewal_buffer_days'])
                ]);
            })
            ->with(['orderItems.serverClient.server'])
            ->get();
    }
}
