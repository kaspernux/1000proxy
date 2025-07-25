<?php

namespace App\Filament\Customer\Clusters\MyServices\Resources;

use App\Filament\Customer\Clusters\MyServices;
use App\Models\Subscription;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Colors\Color;
use Filament\Tables\Actions\Action;
use App\Filament\Customer\Clusters\MyServices\Resources\SubscriptionResource\Pages;

class SubscriptionResource extends Resource
{
    protected static ?string $model = Subscription::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $cluster = MyServices::class;

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
        $customerId = auth('customer')->id();

        return parent::getEloquentQuery()
            ->where('user_id', $customerId)
            ->orderBy('created_at', 'desc');
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
                Tables\Columns\TextColumn::make('name')
                    ->label('Subscription Name')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Medium),

                Tables\Columns\TextColumn::make('stripe_plan')
                    ->label('Plan')
                    ->searchable()
                    ->badge()
                    ->color(Color::Blue),

                Tables\Columns\TextColumn::make('stripe_status')
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
                    ->formatStateUsing(fn (string $state): string => ucfirst(str_replace('_', ' ', $state))),

                Tables\Columns\TextColumn::make('quantity')
                    ->label('Quantity')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('trial_ends_at')
                    ->label('Trial Ends')
                    ->dateTime()
                    ->since()
                    ->placeholder('No trial')
                    ->color(Color::Amber),

                Tables\Columns\TextColumn::make('ends_at')
                    ->label('Ends At')
                    ->dateTime()
                    ->since()
                    ->placeholder('Never')
                    ->color(function ($state) {
                        if (!$state) return Color::Green;
                        $ends = \Carbon\Carbon::parse($state);
                        if ($ends->isPast()) return Color::Red;
                        if ($ends->diffInDays(now()) <= 7) return Color::Orange;
                        if ($ends->diffInDays(now()) <= 30) return Color::Amber;
                        return Color::Green;
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('stripe_status')
                    ->label('Status')
                    ->options([
                        'active' => 'Active',
                        'trialing' => 'Trialing',
                        'past_due' => 'Past Due',
                        'canceled' => 'Canceled',
                        'unpaid' => 'Unpaid',
                        'incomplete' => 'Incomplete',
                        'incomplete_expired' => 'Incomplete Expired',
                    ]),

                Tables\Filters\Filter::make('active_only')
                    ->label('Active Only')
                    ->query(fn (Builder $query): Builder => $query->where('stripe_status', 'active'))
                    ->default(),

                Tables\Filters\Filter::make('ending_soon')
                    ->label('Ending Soon (30 days)')
                    ->query(fn (Builder $query): Builder => $query->where('ends_at', '<=', now()->addDays(30))
                        ->where('ends_at', '>', now())),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Action::make('manage_stripe')
                    ->label('Manage in Stripe')
                    ->icon('heroicon-o-credit-card')
                    ->color('info')
                    ->url(fn ($record) => "https://dashboard.stripe.com/subscriptions/{$record->stripe_id}")
                    ->openUrlInNewTab()
                    ->visible(fn ($record) => !empty($record->stripe_id)),

                Action::make('cancel_subscription')
                    ->label('Cancel')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Cancel Subscription')
                    ->modalDescription('Are you sure you want to cancel this subscription? This action cannot be undone.')
                    ->action(fn ($record) => $record->cancel())
                    ->visible(fn ($record) => $record->stripe_status === 'active'),

                Action::make('resume_subscription')
                    ->label('Resume')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Resume Subscription')
                    ->modalDescription('Are you sure you want to resume this subscription?')
                    ->action(fn ($record) => $record->renew())
                    ->visible(fn ($record) => $record->onGracePeriod()),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc')
            ->poll('120s'); // Auto-refresh every 2 minutes
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Grid::make(3)
                    ->schema([
                        Infolists\Components\Card::make([
                            Infolists\Components\TextEntry::make('name')
                                ->label('Subscription Name')
                                ->weight(FontWeight::Bold)
                                ->color(Color::Blue),

                            Infolists\Components\TextEntry::make('stripe_plan')
                                ->label('Plan')
                                ->badge()
                                ->color(Color::Blue),

                            Infolists\Components\TextEntry::make('quantity')
                                ->label('Quantity')
                                ->numeric(),
                        ])->columnSpan(1),

                        Infolists\Components\Card::make([
                            Infolists\Components\TextEntry::make('stripe_status')
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
                                ->formatStateUsing(fn (string $state): string => ucfirst(str_replace('_', ' ', $state))),

                            Infolists\Components\TextEntry::make('stripe_id')
                                ->label('Stripe ID')
                                ->fontFamily('mono')
                                ->copyable(),

                            Infolists\Components\TextEntry::make('created_at')
                                ->label('Created')
                                ->dateTime()
                                ->since(),
                        ])->columnSpan(1),

                        Infolists\Components\Card::make([
                            Infolists\Components\TextEntry::make('trial_ends_at')
                                ->label('Trial Ends')
                                ->dateTime()
                                ->placeholder('No trial')
                                ->color(Color::Amber),

                            Infolists\Components\TextEntry::make('ends_at')
                                ->label('Subscription Ends')
                                ->dateTime()
                                ->placeholder('Never')
                                ->color(function ($state) {
                                    if (!$state) return Color::Green;
                                    $ends = \Carbon\Carbon::parse($state);
                                    if ($ends->isPast()) return Color::Red;
                                    if ($ends->diffInDays(now()) <= 7) return Color::Orange;
                                    return Color::Green;
                                }),

                            Infolists\Components\TextEntry::make('updated_at')
                                ->label('Last Updated')
                                ->dateTime()
                                ->since(),
                        ])->columnSpan(1),
                    ]),

                Infolists\Components\Section::make('Subscription Details')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('is_active')
                                    ->label('Active Status')
                                    ->formatStateUsing(fn ($record) => $record->isActive() ? 'Active' : 'Inactive')
                                    ->badge()
                                    ->color(fn ($record) => $record->isActive() ? 'success' : 'danger'),

                                Infolists\Components\TextEntry::make('on_grace_period')
                                    ->label('Grace Period')
                                    ->formatStateUsing(fn ($record) => $record->onGracePeriod() ? 'Yes' : 'No')
                                    ->badge()
                                    ->color(fn ($record) => $record->onGracePeriod() ? 'warning' : 'success'),
                            ]),
                    ]),

                Infolists\Components\Section::make('Management Actions')
                    ->schema([
                        Infolists\Components\Actions::make([
                            Infolists\Components\Actions\Action::make('view_stripe')
                                ->label('View in Stripe Dashboard')
                                ->icon('heroicon-o-credit-card')
                                ->color('info')
                                ->url(fn ($record) => "https://dashboard.stripe.com/subscriptions/{$record->stripe_id}")
                                ->openUrlInNewTab()
                                ->visible(fn ($record) => !empty($record->stripe_id)),

                            Infolists\Components\Actions\Action::make('cancel')
                                ->label('Cancel Subscription')
                                ->icon('heroicon-o-x-mark')
                                ->color('danger')
                                ->requiresConfirmation()
                                ->action(fn ($record) => $record->cancel())
                                ->visible(fn ($record) => $record->stripe_status === 'active'),

                            Infolists\Components\Actions\Action::make('resume')
                                ->label('Resume Subscription')
                                ->icon('heroicon-o-play')
                                ->color('success')
                                ->requiresConfirmation()
                                ->action(fn ($record) => $record->renew())
                                ->visible(fn ($record) => $record->onGracePeriod()),
                        ])
                    ]),
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
