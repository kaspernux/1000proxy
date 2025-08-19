<?php

namespace App\Filament\Admin\Pages;

use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables; 
use Filament\Tables\Table;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Livewire\WithPagination;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\Action;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Hash;
use App\Filament\Concerns\HasPerformanceOptimizations;
use BackedEnum;

class StaffDashboard extends Page implements HasTable
{
    // Include WithPagination to satisfy Livewire requirement; prefer Table's resetPage when both exist
    use AuthorizesRequests, InteractsWithTable, HasPerformanceOptimizations, WithPagination {
        WithPagination::resetPage as resetPaginatorPage;
        InteractsWithTable::resetPage insteadof WithPagination;
    }

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'Staff Dashboard';
    protected static ?string $title = 'Staff Dashboard';
    protected static ?string $slug = 'staff-dashboard';
    protected static ?int $navigationSort = 9;

    protected string $view = 'filament.admin.pages.staff-dashboard';

    // UI state
    public bool $showStats = true;

    // Legacy filter props kept for compatibility; table handles filters/search now
    public string $search = '';
    public string $roleFilter = '';
    public string $statusFilter = '';

    // Computed stats
    public array $staffStats = [
        'total_staff' => 0,
        'active_staff' => 0,
        'admins' => 0,
        'with_telegram' => 0,
    ];

    public function mount(): void
    {
        // Defaults
        $this->refreshStats();
    }

    public function updatingSearch(): void { $this->resetTablePage(); }
    public function updatingRoleFilter(): void { $this->resetTablePage(); }
    public function updatingStatusFilter(): void { $this->resetTablePage(); }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->roleFilter = '';
        $this->statusFilter = '';
    $this->resetTablePage();
    }

    public function table(Table $table): Table
    {
        $table = $table
            ->query(
                User::query()
                    ->whereIn('role', ['admin', 'manager', 'support_manager', 'sales_support'])
            )
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->description(fn (User $record) => $record->email, position: 'below')
                    ->weight('bold'),
                TextColumn::make('role')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'admin' => 'danger',
                        'manager' => 'warning',
                        'support_manager' => 'info',
                        'sales_support' => 'gray',
                        default => 'gray',
                    })
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->sortable(),
                IconColumn::make('telegram_linked')
                    ->label('Telegram')
                    ->state(fn (User $record) => method_exists($record, 'hasTelegramLinked') ? $record->hasTelegramLinked() : !empty($record->telegram_chat_id))
                    ->boolean(),
                TextColumn::make('last_login_at')
                    ->label('Last Login')
                    ->dateTime('M d, Y H:i')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->label('Role')
                    ->options([
                        'admin' => 'Administrator',
                        'manager' => 'Manager',
                        'support_manager' => 'Support Manager',
                        'sales_support' => 'Sales Support',
                    ]),
                SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        '1' => 'Active',
                        '0' => 'Inactive',
                    ]),
            ])
            ->actions([
                Action::make('toggle')
                    ->label(fn (User $record) => $record->is_active ? 'Deactivate' : 'Activate')
                    ->color(fn (User $record) => $record->is_active ? 'danger' : 'success')
                    ->action(fn (User $record) => $this->toggleUserStatus($record->id))
                    ->visible(fn (User $record) => auth()->user()->can('toggleStatus', $record)),
                Action::make('resetPassword')
                    ->label('Reset Password')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(fn (User $record) => $this->resetPassword($record->id))
                    ->visible(fn (User $record) => auth()->user()->can('resetPassword', $record)),
            ])
            ->defaultSort('last_login_at', 'desc');

        return self::applyTablePreset($table, [
            'defaultPage' => 25,
            'empty' => [
                'icon' => 'heroicon-o-user-group',
                'heading' => 'No staff yet',
                'description' => 'Staff users will appear once they are invited or created.',
            ],
        ]);
    }

    protected function resetTablePage(): void
    {
        if (method_exists($this, 'resetPage')) {
            $this->resetPage();
        }
    }

    public function toggleUserStatus(int $userId): void
    {
        $user = User::findOrFail($userId);
        $this->authorize('toggleStatus', $user);

        $user->is_active = !$user->is_active;
        $user->save();

        $this->refreshStats();

        Notification::make()
            ->title($user->name . ' ' . ($user->is_active ? 'activated' : 'deactivated'))
            ->success()
            ->send();
    }

    public function resetPassword(int $userId): void
    {
        $user = User::findOrFail($userId);
        $this->authorize('resetPassword', $user);

        $newPassword = str()->password(12);
        $user->password = Hash::make($newPassword);
        $user->save();

        // For security, do not leak password in UI; log or queue email instead in real system
        Notification::make()
            ->title('Password reset')
            ->body("A password reset was performed for {$user->email}.")
            ->success()
            ->send();
    }

    protected function refreshStats(): void
    {
        $this->staffStats = [
            'total_staff' => User::whereIn('role', ['admin', 'manager', 'support_manager', 'sales_support'])->count(),
            'active_staff' => User::whereIn('role', ['admin', 'manager', 'support_manager', 'sales_support'])->active()->count(),
            'admins' => User::admins()->count(),
            'with_telegram' => User::withTelegram()->count(),
        ];
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return $user && $user->hasAdministrativePrivileges();
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('refresh')
                ->label('Refresh')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(function () {
                    $this->refreshStats();
                    \Filament\Notifications\Notification::make()
                        ->title('Dashboard refreshed')
                        ->success()
                        ->send();
                }),
            \Filament\Actions\Action::make('manage_staff')
                ->label('Manage Staff')
                ->icon('heroicon-o-users')
                ->url(fn () => \App\Filament\Admin\Pages\StaffManagement::getUrl())
                ->color('primary'),
            \Filament\Actions\Action::make('help')
                ->label('Help')
                ->icon('heroicon-o-question-mark-circle')
                ->color('gray')
                ->modalHeading('About Staff Dashboard')
                ->modalContent(new \Illuminate\Support\HtmlString('This dashboard lists internal staff accounts. Use filters to narrow by role or status. Manage roles and bulk actions in Staff Management.'))
                ->modalSubmitAction(false),
        ];
    }
}
