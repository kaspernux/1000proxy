<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ActivityLogResource\Pages;
use App\Models\ActivityLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use UnitEnum;
use BackedEnum;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use App\Filament\Concerns\HasPerformanceOptimizations;

class ActivityLogResource extends Resource
{
    use HasPerformanceOptimizations;

    protected static ?string $model = ActivityLog::class;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static string|UnitEnum|null $navigationGroup = 'System';
    protected static ?int $navigationSort = 2;
    protected static ?string $slug = 'activity-logs';
    protected static ?string $modelLabel = 'Activity Log';
    protected static ?string $pluralModelLabel = 'Activity Logs';

    public static function canAccess(): bool
    {
        // Allow route access for authenticated users; page-level checks will enforce 403 for non-admin.
        return auth()->check();
    }

    public static function shouldRegisterNavigation(): bool
    {
        // Only show in navigation for administrators
        return (bool) auth()->user()?->hasRole('admin');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Forms\Components\Textarea::make('properties')->disabled(),
        ]);
    }

    public static function table(Table $table): Table
    {
        $table = $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')->since()->sortable()->label('When'),
                Tables\Columns\TextColumn::make('user.name')->label('User')->toggleable()->searchable(),
                Tables\Columns\TextColumn::make('action')->badge()->colors([
                    'success' => 'created',
                    'warning' => 'updated',
                    'danger' => 'deleted',
                ])->sortable(),
                Tables\Columns\TextColumn::make('subject_type')->label('Subject')->formatStateUsing(fn ($state) => class_basename($state))->searchable(),
                Tables\Columns\TextColumn::make('subject_id')->label('ID')->sortable(),
                Tables\Columns\TextColumn::make('ip_address')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('created_today')->label('Today')
                    ->query(fn (Builder $q) => $q->whereDate('created_at', now()->toDateString())),
                Tables\Filters\Filter::make('date_range')
                    ->form([
                        Forms\Components\DatePicker::make('from'),
                        Forms\Components\DatePicker::make('to'),
                    ])->query(function (Builder $q, array $data) {
                        if ($data['from'] ?? null) {
                            $q->whereDate('created_at', '>=', $data['from']);
                        }
                        if ($data['to'] ?? null) {
                            $q->whereDate('created_at', '<=', $data['to']);
                        }
                        return $q;
                    }),
                Tables\Filters\Filter::make('action')
                    ->form([
                        Forms\Components\Select::make('action')->options([
                            'created' => 'Created',
                            'updated' => 'Updated',
                            'deleted' => 'Deleted',
                        ]),
                    ])->query(fn (Builder $q, array $data) => ($data['action'] ?? null) ? $q->where('action', $data['action']) : $q),
                Tables\Filters\Filter::make('subject_type')
                    ->form([
                        Forms\Components\Select::make('subject_type')
                            ->label('Subject')
                            ->options(collect([
                                'Order', 'Server', 'PaymentMethod', 'Customer', 'ServerPlan', 'ServerClient', 'Invoice',
                            ])->mapWithKeys(fn ($c) => ["App\\Models\\$c" => $c])),
                    ])
                    ->query(fn (Builder $q, array $data) => ($data['subject_type'] ?? null) ? $q->where('subject_type', $data['subject_type']) : $q),
            ])
            ->defaultSort('id', 'desc')
            ->actions([
                \Filament\Actions\ViewAction::make(),
                \Filament\Actions\DeleteAction::make()->visible(fn () => auth()->user()?->hasRole('super-admin')),
            ])
            ->bulkActions([
                \Filament\Actions\BulkAction::make('export_csv')
                    ->label('Export CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(function (Collection $records) {
                        $filename = 'activity-logs-' . now()->format('Ymd-His') . '.csv';

                        return response()->streamDownload(function () use ($records) {
                            $out = fopen('php://output', 'w');
                            fputcsv($out, ['When', 'User', 'Action', 'Subject', 'Subject ID', 'IP Address']);

                            foreach ($records as $log) {
                                fputcsv($out, [
                                    optional($log->created_at)?->toDateTimeString(),
                                    optional($log->user)?->name,
                                    $log->action,
                                    class_basename($log->subject_type),
                                    $log->subject_id,
                                    $log->ip_address,
                                ]);
                            }

                            fclose($out);
                        }, $filename, [
                            'Content-Type' => 'text/csv',
                        ]);
                    })
                    ->deselectRecordsAfterCompletion(),
                \Filament\Actions\DeleteBulkAction::make()->visible(fn () => auth()->user()?->hasRole('super-admin')),
            ]);

        return self::applyTablePreset($table, [
            'defaultPage' => 50,
            'empty' => [
                'icon' => 'heroicon-o-clipboard',
                'heading' => 'No activity yet',
                'description' => 'System activity will appear here as users and services perform actions.',
            ],
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivityLogs::route('/'),
            'view' => Pages\ViewActivityLog::route('/{record}'),
        ];
    }
}
