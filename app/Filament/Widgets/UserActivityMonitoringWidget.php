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
    protected function getTableQuery(): Builder
    {
        // You can customize this query as needed. Here, we return all users ordered by last login.
        return User::query()->orderByDesc('last_login_at');
    }
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
                TextColumn::make('join_date')
                    ->label('Member Since')
                    ->getStateUsing(fn ($record) => $record->created_at->diffForHumans())
                    ->description(fn ($record) => $record->created_at->format('M j, Y'))
                    ->sortable(),
            ])
            ->actions([
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
                    ->query(function (Builder $query, array $data) {
                        $value = $data['value'] ?? null;
                        return match ($value) {
                            'online' => $query->where('last_login_at', '>=', now()->subMinutes(15)),
                            'away' => $query->whereBetween('last_login_at', [now()->subHours(2), now()->subMinutes(15)]),
                            'offline' => $query->where('last_login_at', '<', now()->subHours(2)),
                            'never' => $query->whereNull('last_login_at'),
                            default => $query,
                        };
                    }),
                // Removed 'Has Active Proxies' filter: users do not have servers
                // Removed 'Recent Orders' filter: only customers have orders
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
        return 'Browsing dashboard';
    }

    // Removed getActiveConnections: users do not have servers

    protected function getTableRecordsPerPageSelectOptions(): array
    {
        return [10, 25, 50];
    }

    protected function isTablePaginationEnabled(): bool
    {
        return true;
    }
}