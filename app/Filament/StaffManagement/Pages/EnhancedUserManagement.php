<?php

namespace App\Filament\StaffManagement\Pages;

use App\Services\EnhancedUserManagementService;
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
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class EnhancedUserManagement extends Page implements HasTable, HasForms
{
    use InteractsWithTable, InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Enhanced User Management';
    protected static ?string $title = 'Enhanced User Management';
    protected static string $view = 'filament.staff-management.pages.enhanced-user-management';
    protected static ?string $cluster = \App\Filament\StaffManagement\StaffManagementCluster::class;
    protected static ?int $navigationSort = 1;

    public array $filters = [];
    public ?array $statistics = null;

    protected EnhancedUserManagementService $userService;

    public function boot(): void
    {
        $this->userService = app(EnhancedUserManagementService::class);
        $this->statistics = $this->userService->getUserStatistics();
    }

    public function getHeaderActions(): array
    {
        return [
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
                ->color('primary'),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->userService->searchUsers($this->filters))
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('name')
                    ->label('Name')
                    ->sortable()
                    ->searchable()
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
                        'warning' => 'support_manager',
                        'success' => 'sales_support',
                    ])
                    ->sortable(),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),

                BadgeColumn::make('telegram_status')
                    ->label('Telegram')
                    ->getStateUsing(fn ($record) => $record->telegram_user_id ? 'Linked' : 'Not Linked')
                    ->colors([
                        'success' => 'Linked',
                        'gray' => 'Not Linked',
                    ]),

                TextColumn::make('last_login_at')
                    ->label('Last Login')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('Never'),

                TextColumn::make('created_at')
                    ->label('Registered')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->options([
                        'admin' => 'Admin',
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
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('telegram_user_id')),

                Filter::make('never_logged_in')
                    ->label('Never Logged In')
                    ->query(fn (Builder $query): Builder => $query->whereNull('last_login_at')),

                Filter::make('inactive_30_days')
                    ->label('Inactive 30+ Days')
                    ->query(fn (Builder $query): Builder => $query->where('last_login_at', '<', now()->subDays(30))),
            ])
            ->bulkActions([
                BulkAction::make('activate')
                    ->label('Activate')
                    ->icon('heroicon-o-check-circle')
                    ->action('bulkActivate')
                    ->requiresConfirmation()
                    ->color('success'),

                BulkAction::make('deactivate')
                    ->label('Deactivate')
                    ->icon('heroicon-o-x-circle')
                    ->action('bulkDeactivate')
                    ->requiresConfirmation()
                    ->color('danger'),

                BulkAction::make('changeRole')
                    ->label('Change Role')
                    ->icon('heroicon-o-user-group')
                    ->form([
                        Select::make('role')
                            ->options([
                                'admin' => 'Admin',
                                'support_manager' => 'Support Manager',
                                'sales_support' => 'Sales Support',
                            ])
                            ->required()
                            ->label('New Role'),
                    ])
                    ->action('bulkChangeRole')
                    ->requiresConfirmation()
                    ->color('warning'),

                BulkAction::make('passwordReset')
                    ->label('Send Password Reset')
                    ->icon('heroicon-o-key')
                    ->action('bulkPasswordReset')
                    ->requiresConfirmation()
                    ->color('primary'),
            ])
            ->defaultSort('created_at', 'desc');
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