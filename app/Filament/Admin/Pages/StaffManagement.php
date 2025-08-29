<?php

namespace App\Filament\Admin\Pages;

use App\Services\StaffManagementService;
use App\Models\User;
use Filament\Pages\Page;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Actions\BulkAction;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use App\Filament\Concerns\HasPerformanceOptimizations;
use BackedEnum;

class StaffManagement extends Page implements HasTable, HasForms
{
    use InteractsWithTable, InteractsWithForms, HasPerformanceOptimizations;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Staff Management';
    protected static ?string $title = 'Staff Management';
    // Explicit Blade view reference to the renamed page template
    protected string $view = 'filament.admin.pages.staff-management';

    public function getViewData(): array
    {
        return [
            'stats' => $this->statistics ?? $this->userService->getUserStatistics(),
        ];
    }
    protected static ?int $navigationSort = 3;

    public array $filters = [];
    public ?array $statistics = null;

    protected StaffManagementService $userService;

    public function boot(): void
    {
        $this->userService = app(StaffManagementService::class);
        $this->statistics = $this->userService->getUserStatistics();
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return (bool) ($user?->isAdmin() || $user?->isManager() || $user?->isSupportManager());
    }

    public function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Refresh')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(fn () => $this->refreshStatistics()),
            Action::make('inviteStaff')
                ->label('Invite Staff')
                ->icon('heroicon-o-user-plus')
                ->color('primary')
                ->visible(fn () => auth()->user()?->isAdmin() || auth()->user()?->isManager())
                ->form([
                    TextInput::make('name')
                        ->label('Full Name')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('email')
                        ->label('Email')
                        ->email()
                        ->required(),
                    Select::make('role')
                        ->label('Role')
                        ->options(\App\Models\User::getAvailableRoles())
                        ->default('sales_support')
                        ->required(),
                    Toggle::make('is_active')
                        ->label('Active on Invite')
                        ->default(true),
                    Textarea::make('note')
                        ->label('Message (optional)')
                        ->rows(3)
                        ->placeholder('Welcome note included in the invite email...')
                        ->columnSpanFull(),
                ])
                ->action('inviteStaff'),
            Action::make('exportUsers')
                ->label('Export Users')
                ->icon('heroicon-o-arrow-down-tray')
                ->action('exportUsers')
                ->color('success'),

            Action::make('bulkNotification')
                ->label('Send Bulk Notification')
                ->icon('heroicon-o-megaphone')
                ->form([
                    TextInput::make('subject')
                        ->required()
                        ->maxLength(255)
                        ->label('Subject'),
                    Textarea::make('message')
                        ->required()
                        ->rows(5)
                        ->label('Message'),
                    Select::make('type')
                        ->options([
                            'info' => 'Information',
                            'warning' => 'Warning',
                            'success' => 'Success',
                            'error' => 'Error',
                        ])
                        ->default('info')
                        ->required()
                        ->label('Type'),
                ])
                ->action('sendBulkNotification')
                ->color('primary')
                ->visible(fn () => auth()->user()?->isAdmin() || auth()->user()?->isManager()),

            Action::make('help')
                ->label('Help')
                ->icon('heroicon-o-question-mark-circle')
                ->color('gray')
                ->modalHeading('About Staff Management')
                ->modalContent(new \Illuminate\Support\HtmlString('Manage internal staff accounts, roles, and bulk actions. Use filters to narrow by role or status.'))
                ->modalSubmitAction(false),
        ];
    }

    public function table(Table $table): Table
    {
        $table = $table
            ->query($this->userService->searchUsers($this->filters))
            ->columns([
                // Avatar column with tooltip
                TextColumn::make('avatar')
                    ->label('Avatar')
                    ->formatStateUsing(fn ($record) => view('components.avatar', ['user' => $record]))
                    ->html(),

                TextColumn::make('name')
                    ->label('Name')
                    ->sortable()
                    ->searchable()
                    ->tooltip(fn ($record) => $record->email)
                    ->limit(30),

                TextColumn::make('email')
                    ->label('Email')
                    ->sortable()
                    ->searchable()
                    ->copyable()
                    ->limit(40),

                BadgeColumn::make('role')
                    ->label('Role')
                    ->colors([
                        'danger' => 'admin',
                        'primary' => 'manager',
                        'warning' => 'support_manager',
                        'success' => 'sales_support',
                    ])
                    ->tooltip(fn ($record) => ucwords(str_replace('_', ' ', $record->role)))
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->tooltip(fn ($record) => $record->is_active ? 'Active' : 'Inactive')
                    ->sortable(),

                BadgeColumn::make('telegram_status')
                    ->label('Telegram')
                    ->getStateUsing(fn ($record) => $record->telegram_chat_id ? 'Linked' : 'Not Linked')
                    ->colors([
                        'success' => 'Linked',
                        'gray' => 'Not Linked',
                    ])
                    ->tooltip(fn ($record) => $record->telegram_chat_id ? 'Telegram Linked' : 'Not Linked'),

                TextColumn::make('last_login_at')
                    ->label('Last Login')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('Never'),

                TextColumn::make('last_activity_at')
                    ->label('Last Activity')
                    ->description('Most recent user action')
                    ->dateTime()
                    ->sortable()
                    ->getStateUsing(fn ($record) => $record->last_activity_at ?? $record->last_login_at)
                    ->placeholder('No activity'),

                TextColumn::make('created_at')
                    ->label('Registered')
                    ->dateTime()
                    ->sortable(),
            ])
            ->actions([
                Action::make('toggle')
                    ->label(fn (User $record) => $record->is_active ? 'Deactivate' : 'Activate')
                    ->color(fn (User $record) => $record->is_active ? 'danger' : 'success')
                    ->visible(fn () => auth()->user()?->isAdmin() || auth()->user()?->isManager())
                    ->action(fn (User $record) => $this->toggleUserStatus($record->id)),
                Action::make('sendResetLink')
                    ->label('Send Reset Link')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn () => auth()->user()?->isAdmin() || auth()->user()?->isManager())
                    ->action(fn (User $record) => $this->sendResetLink($record->id)),
                Action::make('viewActivity')
                    ->label('View Activity')
                    ->icon('heroicon-o-list-bullet')
                    ->color('info')
                    ->modalHeading(fn (User $record) => 'Activity Log for ' . $record->name)
                    ->modalContent(fn (User $record) => view('filament.admin.pages.partials.user-activity-log', ['user' => $record]))
                    ->modalSubmitAction(false)
                    ->visible(fn () => auth()->user()?->isAdmin() || auth()->user()?->isManager() || auth()->user()?->isSupportManager()),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->options([
                        'admin' => 'Admin',
                        'manager' => 'Manager',
                        'support_manager' => 'Support Manager',
                        'sales_support' => 'Sales Support',
                    ]),

                SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        1 => 'Active',
                        0 => 'Inactive',
                    ]),

                Filter::make('has_telegram')
                    ->label('Telegram Linked')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('telegram_chat_id')),

                Filter::make('never_logged_in')
                    ->label('Never Logged In')
                    ->query(fn (Builder $query): Builder => $query->whereNull('last_login_at')),

                Filter::make('inactive_30_days')
                    ->label('Inactive 30+ Days')
                    ->query(fn (Builder $query): Builder => $query->where('last_login_at', '<', now()->subDays(30))),

                Filter::make('recent_activity')
                    ->label('Recent Activity (7d)')
                    ->query(fn (Builder $query): Builder => $query->where('last_activity_at', '>=', now()->subDays(7))),
            ])
            ->bulkActions([
                BulkAction::make('activate')
                    ->label('Activate')
                    ->icon('heroicon-o-check-circle')
                    ->action('bulkActivate')
                    ->requiresConfirmation()
                    ->color('success')
                    ->visible(fn () => auth()->user()?->isAdmin() || auth()->user()?->isManager()),

                BulkAction::make('deactivate')
                    ->label('Deactivate')
                    ->icon('heroicon-o-x-circle')
                    ->action('bulkDeactivate')
                    ->requiresConfirmation()
                    ->color('danger')
                    ->visible(fn () => auth()->user()?->isAdmin() || auth()->user()?->isManager()),

                BulkAction::make('changeRole')
                    ->label('Change Role')
                    ->icon('heroicon-o-user-group')
                    ->form([
                        Select::make('role')
                            ->options([
                                'admin' => 'Admin',
                                'manager' => 'Manager',
                                'support_manager' => 'Support Manager',
                                'sales_support' => 'Sales Support',
                            ])
                            ->required()
                            ->label('New Role'),
                    ])
                    ->action('bulkChangeRole')
                    ->requiresConfirmation()
                    ->color('warning')
                    ->visible(fn () => auth()->user()?->isAdmin()),

                BulkAction::make('passwordReset')
                    ->label('Send Password Reset')
                    ->icon('heroicon-o-key')
                    ->action('bulkPasswordReset')
                    ->requiresConfirmation()
                    ->color('primary')
                    ->visible(fn () => auth()->user()?->isAdmin() || auth()->user()?->isManager()),
            ])
            ->defaultSort('created_at', 'desc');

        return self::applyTablePreset($table, [
            'defaultPage' => 25,
            'empty' => [
                'icon' => 'heroicon-o-users',
                'heading' => 'No staff found',
                'description' => 'Try changing filters or add new staff users.',
            ],
        ]);
    }

    public function refreshStatistics(): void
    {
        $this->statistics = $this->userService->getUserStatistics();
        Notification::make()->title('Statistics refreshed')->success()->send();
    }

    public function toggleUserStatus(int $userId): void
    {
        $user = User::findOrFail($userId);
        if (! (auth()->user()?->isAdmin() || auth()->user()?->isManager())) {
            Notification::make()->title('Not authorized')->danger()->send();
            return;
        }
        $user->is_active = ! $user->is_active;
        $user->save();
        $this->refreshStatistics();
        Notification::make()
            ->title(($user->is_active ? 'Activated ' : 'Deactivated ') . $user->name)
            ->success()
            ->send();
    }

    public function sendResetLink(int $userId): void
    {
        if (! (auth()->user()?->isAdmin() || auth()->user()?->isManager())) {
            Notification::make()->title('Not authorized')->danger()->send();
            return;
        }
        $user = User::findOrFail($userId);
        $status = Password::sendResetLink(['email' => $user->email]);
        if ($status === Password::RESET_LINK_SENT) {
            Notification::make()->title('Password reset email sent')->success()->send();
        } else {
            Notification::make()->title('Failed to send reset email')->danger()->send();
        }
    }

    public function inviteStaff(array $data): void
    {
        if (! (auth()->user()?->isAdmin() || auth()->user()?->isManager())) {
            Notification::make()->title('Not authorized')->danger()->send();
            return;
        }

        // Normalize email
        $email = strtolower(trim($data['email'] ?? ''));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Notification::make()->title('Invalid email address')->danger()->send();
            return;
        }

        $existing = User::where('email', $email)->first();
        if ($existing) {
            // Send reset link as invite for existing user
            $status = Password::sendResetLink(['email' => $existing->email]);
            if ($status === Password::RESET_LINK_SENT) {
                Notification::make()->title('Invitation (reset link) sent to existing user.')->success()->send();
            } else {
                Notification::make()->title('Failed to send invite to existing user.')->danger()->send();
            }
            return;
        }

        // Create a placeholder account with a random password (hashed by cast)
        $user = new User();
        $user->name = trim((string)($data['name'] ?? '')) ?: 'New Staff';
        $user->email = $email;
        $user->role = $data['role'] ?? 'sales_support';
        $user->is_active = (bool)($data['is_active'] ?? true);
        $user->password = Str::random(32);
        $user->save();

        // Send reset link as invitation
        $status = Password::sendResetLink(['email' => $user->email]);

        if ($status === Password::RESET_LINK_SENT) {
            Notification::make()
                ->title('Invitation sent')
                ->body('An invitation email with a secure link was sent to ' . $user->email)
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Invitation failed')
                ->body('We could not send the invitation email. Please try again.')
                ->danger()
                ->send();
        }

        $this->refreshStatistics();
    }

    public function bulkActivate(Collection $records): void
    {
        $result = $this->userService->bulkActivateUsers($records->pluck('id')->toArray());

        if ($result['success']) {
            Notification::make()
                ->title('Users Activated')
                ->body($result['message'])
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Activation Failed')
                ->body($result['message'])
                ->danger()
                ->send();
        }
    }

    public function bulkDeactivate(Collection $records): void
    {
        $result = $this->userService->bulkDeactivateUsers($records->pluck('id')->toArray());

        if ($result['success']) {
            Notification::make()
                ->title('Users Deactivated')
                ->body($result['message'])
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Deactivation Failed')
                ->body($result['message'])
                ->danger()
                ->send();
        }
    }

    public function bulkChangeRole(Collection $records, array $data): void
    {
        $result = $this->userService->bulkChangeRole($records->pluck('id')->toArray(), $data['role']);

        if ($result['success']) {
            Notification::make()
                ->title('Roles Changed')
                ->body($result['message'])
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Role Change Failed')
                ->body($result['message'])
                ->danger()
                ->send();
        }
    }

    public function bulkPasswordReset(Collection $records): void
    {
        $result = $this->userService->bulkPasswordReset($records->pluck('id')->toArray());

        if ($result['success']) {
            Notification::make()
                ->title('Password Resets Sent')
                ->body($result['message'])
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Password Reset Failed')
                ->body($result['message'])
                ->danger()
                ->send();
        }
    }

    public function sendBulkNotification(array $data): void
    {
        // Get all user IDs (or selected ones if we implement selection)
        $userIds = User::pluck('id')->toArray();

        $result = $this->userService->sendBulkNotification(
            $userIds,
            $data['subject'],
            $data['message'],
            $data['type']
        );

        if ($result['success']) {
            Notification::make()
                ->title('Notification Sent')
                ->body($result['message'])
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Notification Failed')
                ->body($result['message'])
                ->danger()
                ->send();
        }
    }

    public function exportUsers(): void
    {
        try {
            $filepath = $this->userService->exportUsers($this->filters, 'csv');

            Notification::make()
                ->title('Export Complete')
                ->body('Users exported to: ' . basename($filepath))
                ->success()
                ->send();

            // Trigger download
            response()->download($filepath)->deleteFileAfterSend();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Export Failed')
                ->body('Failed to export users: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function getStatisticsData(): array
    {
        return $this->statistics ?? [];
    }
}