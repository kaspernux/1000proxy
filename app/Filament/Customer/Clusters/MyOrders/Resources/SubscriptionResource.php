<?php

namespace App\Filament\Customer\Clusters\MyOrders\Resources;

use App\Filament\Customer\Clusters\MyOrders;
use App\Models\Subscription;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Tabs;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Grid;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\Action;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Resources\Components\Tab;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Colors\Color;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Filament\Customer\Clusters\MyOrders\Resources\SubscriptionResource\Pages;

class SubscriptionResource extends Resource
{
    protected static ?string $model = Subscription::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $cluster = MyOrders::class;

    protected static ?string $navigationLabel = 'Subscriptions';

    protected static ?string $modelLabel = 'Subscription';

    protected static ?string $pluralModelLabel = 'Subscriptions';

    protected static ?int $navigationSort = 4;

    public static function canCreate(): bool
    {
        return false; // Subscriptions are created through orders
    }

    public static function canEdit($record): bool
    {
        return false; // Customers can't edit subscription details
    }

    public static function canDelete($record): bool
    {
        return false; // Customers can't delete subscriptions directly
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('user_id', Auth::guard('customer')->id())
            ->orderBy('created_at', 'desc');
    }

    public static function getTabs(): array
    {
        $customerId = Auth::guard('customer')->id();

        return [
            'all' => Tab::make('All Subscriptions')
                ->icon('heroicon-m-calendar-days')
                ->badge(
                    Subscription::where('user_id', $customerId)->count()
                ),

            'active' => Tab::make('Active')
                ->icon('heroicon-m-check-circle')
                ->modifyQueryUsing(fn (Builder $query) => 
                    $query->where('stripe_status', 'active')
                )
                ->badge(
                    Subscription::where('user_id', $customerId)
                        ->where('stripe_status', 'active')->count()
                )
                ->badgeColor('success'),

            'trialing' => Tab::make('Trial')
                ->icon('heroicon-m-clock')
                ->modifyQueryUsing(fn (Builder $query) => 
                    $query->where('stripe_status', 'trialing')
                )
                ->badge(
                    Subscription::where('user_id', $customerId)
                        ->where('stripe_status', 'trialing')->count()
                )
                ->badgeColor('info'),

            'ending_soon' => Tab::make('Ending Soon')
                ->icon('heroicon-m-exclamation-triangle')
                ->modifyQueryUsing(fn (Builder $query) => 
                    $query->whereNotNull('ends_at')
                          ->where('ends_at', '>', now())
                          ->where('ends_at', '<=', now()->addDays(30))
                )
                ->badge(
                    Subscription::where('user_id', $customerId)
                        ->whereNotNull('ends_at')
                        ->where('ends_at', '>', now())
                        ->where('ends_at', '<=', now()->addDays(30))->count()
                )
                ->badgeColor('warning'),

            'canceled' => Tab::make('Canceled')
                ->icon('heroicon-m-x-circle')
                ->modifyQueryUsing(fn (Builder $query) => 
                    $query->whereIn('stripe_status', ['canceled', 'unpaid', 'incomplete_expired'])
                )
                ->badge(
                    Subscription::where('user_id', $customerId)
                        ->whereIn('stripe_status', ['canceled', 'unpaid', 'incomplete_expired'])->count()
                )
                ->badgeColor('danger'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $customerId = Auth::guard('customer')->id();
        $activeCount = Subscription::where('user_id', $customerId)
            ->where('stripe_status', 'active')->count();
        
        return $activeCount > 0 ? (string) $activeCount : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Form fields would go here but customers can't edit subscription data
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Subscription Name')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::SemiBold)
                    ->color('primary')
                    ->description(fn (Subscription $record): string => 
                        $record->stripe_plan ? "Plan: {$record->stripe_plan}" : ''
                    ),

                BadgeColumn::make('stripe_status')
                    ->label('Status')
                    ->colors([
                        'success' => 'active',
                        'info' => 'trialing',
                        'warning' => ['past_due', 'paused'],
                        'danger' => ['canceled', 'unpaid', 'incomplete_expired'],
                        'gray' => 'incomplete',
                    ])
                    ->icons([
                        'heroicon-m-check-circle' => 'active',
                        'heroicon-m-clock' => 'trialing',
                        'heroicon-m-exclamation-triangle' => ['past_due', 'paused'],
                        'heroicon-m-x-circle' => ['canceled', 'unpaid', 'incomplete_expired'],
                        'heroicon-m-question-mark-circle' => 'incomplete',
                    ])
                    ->formatStateUsing(fn (string $state): string => 
                        ucfirst(str_replace('_', ' ', $state))
                    )
                    ->sortable(),

                TextColumn::make('stripe_plan')
                    ->label('Plan')
                    ->searchable()
                    ->badge()
                    ->color('info')
                    ->visible(fn (Subscription $record): bool => $record->stripe_plan !== null),

                TextColumn::make('quantity')
                    ->label('Quantity')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                TextColumn::make('trial_ends_at')
                    ->label('Trial Ends')
                    ->dateTime('M j, Y')
                    ->placeholder('No trial')
                    ->color(function ($record): string {
                        if (!$record->trial_ends_at) return 'gray';
                        return $record->trial_ends_at->isPast() ? 'danger' : 'warning';
                    })
                    ->visible(fn (Subscription $record): bool => $record->trial_ends_at !== null),

                TextColumn::make('ends_at')
                    ->label('Ends At')
                    ->dateTime('M j, Y')
                    ->placeholder('Active')
                    ->color(function ($record): string {
                        if (!$record->ends_at) return 'success';
                        $ends = Carbon::parse($record->ends_at);
                        if ($ends->isPast()) return 'danger';
                        if ($ends->diffInDays(now()) <= 7) return 'danger';
                        if ($ends->diffInDays(now()) <= 30) return 'warning';
                        return 'success';
                    })
                    ->description(function ($record): ?string {
                        if (!$record->ends_at) return null;
                        $ends = Carbon::parse($record->ends_at);
                        if ($ends->isPast()) return 'Expired';
                        $days = $ends->diffInDays(now());
                        if ($days <= 7) return "In {$days} days";
                        return null;
                    }),

                TextColumn::make('created_at')
                    ->label('Started')
                    ->since()
                    ->sortable()
                    ->color('gray'),
            ])
            ->filters([
                SelectFilter::make('stripe_status')
                    ->label('Status')
                    ->options([
                        'active' => 'Active',
                        'trialing' => 'Trialing',
                        'past_due' => 'Past Due',
                        'canceled' => 'Canceled',
                        'unpaid' => 'Unpaid',
                        'incomplete' => 'Incomplete',
                        'incomplete_expired' => 'Incomplete Expired',
                        'paused' => 'Paused',
                    ])
                    ->indicator('Status'),

                Filter::make('ending_soon')
                    ->label('Ending Soon')
                    ->form([
                        DatePicker::make('ends_before')->label('Ends Before'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['ends_before'],
                            fn (Builder $query, $date): Builder => $query->whereDate('ends_at', '<=', $date),
                        );
                    })
                    ->indicator('Ending Soon'),

                Filter::make('trial_subscriptions')
                    ->label('Trial Subscriptions')
                    ->query(fn (Builder $query): Builder => 
                        $query->whereNotNull('trial_ends_at')
                              ->where('trial_ends_at', '>', now())
                    )
                    ->indicator('Trial Subscriptions'),
            ])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('View Details')
                        ->icon('heroicon-o-eye')
                        ->color('primary'),

                    Action::make('manage_stripe')
                        ->label('Manage in Stripe')
                        ->icon('heroicon-o-credit-card')
                        ->color('info')
                        ->url(fn (Subscription $record): string => 
                            "https://dashboard.stripe.com/subscriptions/{$record->stripe_id}"
                        )
                        ->openUrlInNewTab()
                        ->visible(fn (Subscription $record): bool => !empty($record->stripe_id)),

                    Action::make('cancel_subscription')
                        ->label('Cancel')
                        ->icon('heroicon-o-x-mark')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Cancel Subscription')
                        ->modalDescription('Are you sure you want to cancel this subscription? This action cannot be undone.')
                        ->modalSubmitActionLabel('Yes, Cancel')
                        ->action(function (Subscription $record) {
                            try {
                                $record->cancel();
                                Notification::make()
                                    ->title('Subscription Canceled')
                                    ->body('Your subscription has been successfully canceled.')
                                    ->success()
                                    ->send();
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Cancellation Failed')
                                    ->body('Could not cancel the subscription. Please try again later.')
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->visible(fn (Subscription $record): bool => 
                            $record->stripe_status === 'active'
                        ),

                    Action::make('resume_subscription')
                        ->label('Resume')
                        ->icon('heroicon-o-play')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Resume Subscription')
                        ->modalDescription('Are you sure you want to resume this subscription?')
                        ->modalSubmitActionLabel('Yes, Resume')
                        ->action(function (Subscription $record) {
                            try {
                                $record->renew();
                                Notification::make()
                                    ->title('Subscription Resumed')
                                    ->body('Your subscription has been successfully resumed.')
                                    ->success()
                                    ->send();
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Resume Failed')
                                    ->body('Could not resume the subscription. Please try again later.')
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->visible(fn (Subscription $record): bool => 
                            $record->onGracePeriod()
                        ),
                ])
                ->label('Actions')
                ->icon('heroicon-m-ellipsis-vertical')
                ->size('sm')
                ->color('gray')
                ->button()
            ])
            ->bulkActions([
                // No bulk actions for security
            ])
            ->emptyStateHeading('No Subscriptions Found')
            ->emptyStateDescription('Active subscriptions from your orders will appear here.')
            ->emptyStateIcon('heroicon-o-calendar-days')
            ->defaultSort('created_at', 'desc')
            ->poll('60s')
            ->striped();
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Tabs::make('Subscription Details')
                    ->persistTab()
                    ->columnSpanFull()
                    ->tabs([
                        Tabs\Tab::make('Overview')
                            ->icon('heroicon-m-calendar-days')
                            ->schema([
                                Grid::make(3)
                                    ->schema([
                                        Section::make('Subscription Information')
                                            ->description('Basic subscription details')
                                            ->icon('heroicon-m-document-text')
                                            ->schema([
                                                TextEntry::make('name')
                                                    ->label('Subscription Name')
                                                    ->weight(FontWeight::Bold)
                                                    ->color('primary'),

                                                TextEntry::make('stripe_plan')
                                                    ->label('Plan')
                                                    ->badge()
                                                    ->color('info'),

                                                TextEntry::make('quantity')
                                                    ->label('Quantity')
                                                    ->numeric()
                                                    ->badge()
                                                    ->color('primary'),

                                                TextEntry::make('stripe_id')
                                                    ->label('Stripe ID')
                                                    ->fontFamily('mono')
                                                    ->copyable()
                                                    ->color('gray'),
                                            ]),

                                        Section::make('Status & Billing')
                                            ->description('Current status and billing information')
                                            ->icon('heroicon-m-credit-card')
                                            ->schema([
                                                TextEntry::make('stripe_status')
                                                    ->label('Status')
                                                    ->badge()
                                                    ->color(fn (string $state): string => match ($state) {
                                                        'active' => 'success',
                                                        'trialing' => 'info',
                                                        'past_due' => 'warning',
                                                        'canceled' => 'danger',
                                                        'unpaid' => 'danger',
                                                        'incomplete' => 'gray',
                                                        'incomplete_expired' => 'gray',
                                                        default => 'gray',
                                                    })
                                                    ->formatStateUsing(fn (string $state): string => 
                                                        ucfirst(str_replace('_', ' ', $state))
                                                    ),

                                                TextEntry::make('is_active')
                                                    ->label('Active Status')
                                                    ->formatStateUsing(fn (Subscription $record): string => 
                                                        $record->isActive() ? 'Active' : 'Inactive'
                                                    )
                                                    ->badge()
                                                    ->color(fn (Subscription $record): string => 
                                                        $record->isActive() ? 'success' : 'danger'
                                                    ),

                                                TextEntry::make('on_grace_period')
                                                    ->label('Grace Period')
                                                    ->formatStateUsing(fn (Subscription $record): string => 
                                                        $record->onGracePeriod() ? 'Yes' : 'No'
                                                    )
                                                    ->badge()
                                                    ->color(fn (Subscription $record): string => 
                                                        $record->onGracePeriod() ? 'warning' : 'success'
                                                    ),

                                                TextEntry::make('created_at')
                                                    ->label('Started')
                                                    ->dateTime('F j, Y \a\t g:i A')
                                                    ->since()
                                                    ->color('gray'),
                                            ]),

                                        Section::make('Important Dates')
                                            ->description('Trial and cancellation dates')
                                            ->icon('heroicon-m-clock')
                                            ->schema([
                                                TextEntry::make('trial_ends_at')
                                                    ->label('Trial Ends')
                                                    ->dateTime('F j, Y \a\t g:i A')
                                                    ->placeholder('No trial period')
                                                    ->color(function (Subscription $record): string {
                                                        if (!$record->trial_ends_at) return 'gray';
                                                        return $record->trial_ends_at->isPast() ? 'danger' : 'warning';
                                                    })
                                                    ->visible(fn (Subscription $record): bool => 
                                                        $record->trial_ends_at !== null
                                                    ),

                                                TextEntry::make('ends_at')
                                                    ->label('Subscription Ends')
                                                    ->dateTime('F j, Y \a\t g:i A')
                                                    ->placeholder('Active subscription')
                                                    ->color(function (Subscription $record): string {
                                                        if (!$record->ends_at) return 'success';
                                                        $ends = Carbon::parse($record->ends_at);
                                                        if ($ends->isPast()) return 'danger';
                                                        if ($ends->diffInDays(now()) <= 7) return 'danger';
                                                        if ($ends->diffInDays(now()) <= 30) return 'warning';
                                                        return 'success';
                                                    }),

                                                TextEntry::make('updated_at')
                                                    ->label('Last Updated')
                                                    ->since()
                                                    ->color('gray'),
                                            ]),
                                    ]),
                            ]),

                        Tabs\Tab::make('Management')
                            ->icon('heroicon-m-cog')
                            ->schema([
                                Section::make('Subscription Actions')
                                    ->description('Available actions for this subscription')
                                    ->icon('heroicon-m-wrench-screwdriver')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextEntry::make('stripe_dashboard')
                                                    ->label('Stripe Dashboard')
                                                    ->formatStateUsing(fn (Subscription $record): string => 
                                                        "View in Stripe Dashboard"
                                                    )
                                                    ->url(fn (Subscription $record): string => 
                                                        "https://dashboard.stripe.com/subscriptions/{$record->stripe_id}"
                                                    )
                                                    ->openUrlInNewTab()
                                                    ->color('info')
                                                    ->visible(fn (Subscription $record): bool => 
                                                        !empty($record->stripe_id)
                                                    ),

                                                TextEntry::make('cancel_available')
                                                    ->label('Cancellation')
                                                    ->formatStateUsing(fn (Subscription $record): string => 
                                                        $record->stripe_status === 'active' 
                                                            ? 'Available' 
                                                            : 'Not available'
                                                    )
                                                    ->badge()
                                                    ->color(fn (Subscription $record): string => 
                                                        $record->stripe_status === 'active' ? 'warning' : 'gray'
                                                    ),

                                                TextEntry::make('resume_available')
                                                    ->label('Resume')
                                                    ->formatStateUsing(fn (Subscription $record): string => 
                                                        $record->onGracePeriod() 
                                                            ? 'Available' 
                                                            : 'Not available'
                                                    )
                                                    ->badge()
                                                    ->color(fn (Subscription $record): string => 
                                                        $record->onGracePeriod() ? 'success' : 'gray'
                                                    ),
                                            ]),
                                    ]),
                            ]),
                    ])
                    ->contained(true),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubscriptions::route('/'),
            'view' => Pages\ViewSubscription::route('/{record}'),
        ];
    }
}