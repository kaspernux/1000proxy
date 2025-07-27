<?php

namespace App\Filament\Widgets;

use App\Models\User;
use App\Models\Order;
use App\Models\ServerClient;
use Carbon\Carbon;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Actions\Action;

class UserActivityMonitoringWidget extends BaseWidget
{
    protected static ?string $heading = 'Recent User Activity';

    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $pollingInterval = '30s';

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                ImageColumn::make('avatar_url')
                    ->label('Avatar')
                    ->circular()
                    ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name=' . urlencode($record->name) . '&color=7F9CF5&background=EBF4FF')
                    ->size(40),
                TextColumn::make('name')
                    ->label('User')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->email),
                BadgeColumn::make('status')
                    ->label('Status')
                    ->getStateUsing(fn ($record) => $this->getUserStatus($record))
                    ->colors([
                        'success' => 'Online',
                        'warning' => 'Away',
                        'danger' => 'Offline',
                        'gray' => 'Never logged in',
                    ])
                    ->icons([
                        'heroicon-m-check-circle' => 'Online',
                        'heroicon-m-clock' => 'Away',
                        'heroicon-m-x-circle' => 'Offline',
                        'heroicon-m-question-mark-circle' => 'Never logged in',
                    ]),
                TextColumn::make('last_activity')
                    ->label('Last Activity')
                    ->getStateUsing(fn ($record) => $this->getLastActivity($record))
                    ->description(fn ($record) => $this->getActivityDescription($record))
                    ->sortable(),
                BadgeColumn::make('active_connections')
                    ->label('Active Proxies')
                    ->getStateUsing(fn ($record) => $this->getActiveConnections($record))
                    ->colors([
                        'success' => fn ($state) => $state > 0,
                        'gray' => fn ($state) => $state === 0,
                    ])
                    ->icons([
                        'heroicon-m-signal' => fn ($state) => $state > 0,
                        'heroicon-m-minus-circle' => fn ($state) => $state === 0,
                    ]),
                TextColumn::make('total_orders')
                    ->label('Orders')
                    ->getStateUsing(fn ($record) => $record->orders()->count())
                    ->description(fn ($record) => '$' . number_format($record->orders()->sum('grand_amount'), 2))
                    ->alignCenter(),
                TextColumn::make('join_date')
                    ->label('Member Since')
                    ->getStateUsing(fn ($record) => $record->created_at->diffForHumans())
                    ->description(fn ($record) => $record->created_at->format('M j, Y'))
                    ->sortable(),
            ])
            ->actions([
                Action::make('view_profile')
                    ->label('View')
                    ->icon('heroicon-m-eye')
                    ->color('info')
                    ->url(fn ($record) => route('filament.admin.resources.users.view', $record))
                    ->openUrlInNewTab(),
                Action::make('send_message')
                    ->label('Message')
                    ->icon('heroicon-m-chat-bubble-left')
                    ->color('success')
                    ->action(function ($record) {
                        $this->notify('success', "Message interface would open for {$record->name}");
                    }),
                Action::make('view_activity')
                    ->label('Activity')
                    ->icon('heroicon-m-chart-bar')
                    ->color('warning')
                    ->action(function ($record) {
                        $this->notify('info', "Activity details for {$record->name}");
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'online' => 'Online',
                        'away' => 'Away',
                        'offline' => 'Offline',
                        'never' => 'Never logged in',
                    ])
                    ->query(fn (Builder $query, $value) => match ($value) {
                        'online' => $query->where('last_login_at', '>=', now()->subMinutes(15)),
                        'away' => $query->whereBetween('last_login_at', [now()->subHours(2), now()->subMinutes(15)]),
                        'offline' => $query->where('last_login_at', '<', now()->subHours(2)),
                        'never' => $query->whereNull('last_login_at'),
                        default => $query,
                    }),
                Tables\Filters\Filter::make('has_active_proxies')
                    ->label('Has Active Proxies')
                    ->query(fn (Builder $query) => $query->whereHas('serverClients', fn ($q) => $q->where('status', 'active'))),
                Tables\Filters\Filter::make('recent_orders')
                    ->label('Recent Orders')
                    ->query(fn (Builder $query): Builder => $query->whereHas('orders', fn ($q) => $q->where('created_at', '>=', now()->subDays(7)))),
            ])
            ->defaultSort('last_login_at', 'desc')
            ->poll('30s');
    }



    private function getUserStatus(User $user): string
    {
        if (!$user->last_login_at) {
            return 'Never logged in';
        }
        $lastLogin = Carbon::parse($user->last_login_at);
        if ($lastLogin->greaterThanOrEqualTo(now()->subMinutes(15))) {
            return 'Online';
        } elseif ($lastLogin->greaterThanOrEqualTo(now()->subHours(2))) {
            return 'Away';
        } else {
            return 'Offline';
        }
    }

    private function getLastActivity(User $user): string
    {
        if (!$user->last_login_at) {
            return 'Never';
        }
        return Carbon::parse($user->last_login_at)->diffForHumans();
    }

    private function getActivityDescription(User $user): string
    {
        if (!$user->last_login_at) {
            return 'User has never logged in';
        }
        $recentOrder = $user->orders()->latest()->first();
        $activeProxies = $user->clients()->where('status', 'active')->count();
        if ($recentOrder && $recentOrder->created_at >= now()->subDays(7)) {
            return "Last order: {$recentOrder->created_at->diffForHumans()}";
        } elseif ($activeProxies > 0) {
            return "Managing {$activeProxies} active " . str('proxy')->plural($activeProxies);
        } else {
            return 'Browsing dashboard';
        }
    }

    private function getActiveConnections(User $user): int
    {
        return Cache::remember("user_active_connections_{$user->id}", 300, function () use ($user) {
            return $user->clients()
                ->where('status', 'active')
                ->count();
        });
    }

    protected function getTableRecordsPerPageSelectOptions(): array
    {
        return [10, 25, 50];
    }

    protected function isTablePaginationEnabled(): bool
    {
        return true;
    }
}