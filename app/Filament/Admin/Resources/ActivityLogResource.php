<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ActivityLogResource\Pages;
use App\Models\ActivityLog;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class ActivityLogResource extends Resource
{
    protected static ?string $model = ActivityLog::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationGroup = 'System';
    protected static ?int $navigationSort = 99;
    protected static ?string $slug = 'activity-logs';
    protected static ?string $modelLabel = 'Activity Log';
    protected static ?string $pluralModelLabel = 'Activity Logs';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Textarea::make('properties')->disabled(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->since()->sortable()->label('When'),
                TextColumn::make('user.name')->label('User')->toggleable()->searchable(),
                TextColumn::make('action')->badge()->colors([
                    'success' => 'created',
                    'warning' => 'updated',
                    'danger' => 'deleted',
                ])->sortable(),
                TextColumn::make('subject_type')->label('Subject')->formatStateUsing(fn($state) => class_basename($state))->searchable(),
                TextColumn::make('subject_id')->label('ID')->sortable(),
                TextColumn::make('ip_address')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Filter::make('created_today')->label('Today')
                    ->query(fn(Builder $q) => $q->whereDate('created_at', now()->toDateString())),
                Filter::make('date_range')
                    ->form([
                        Forms\Components\DatePicker::make('from'),
                        Forms\Components\DatePicker::make('to'),
                    ])->query(function(Builder $q, array $data) {
                        if ($data['from']) { $q->whereDate('created_at', '>=', $data['from']); }
                        if ($data['to']) { $q->whereDate('created_at', '<=', $data['to']); }
                        return $q;
                    }),
                Filter::make('action')
                    ->form([
                        Forms\Components\Select::make('action')->options([
                            'created' => 'Created',
                            'updated' => 'Updated',
                            'deleted' => 'Deleted',
                        ])
                    ])->query(fn(Builder $q, array $data) => $data['action'] ? $q->where('action', $data['action']) : $q),
                Filter::make('subject_type')
                    ->form([
                        Forms\Components\Select::make('subject_type')
                            ->label('Subject')
                            ->options(collect([
                                'Order', 'Server', 'PaymentMethod', 'Customer', 'ServerPlan', 'ServerClient', 'Invoice'
                            ])->mapWithKeys(fn($c) => ["App\\Models\\$c" => $c]))
                    ])
                    ->query(fn(Builder $q, array $data) => $data['subject_type'] ? $q->where('subject_type', $data['subject_type']) : $q),
            ])
            ->defaultSort('id', 'desc')
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make()->visible(fn() => auth()->user()?->hasRole('super-admin')),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()->visible(fn() => auth()->user()?->hasRole('super-admin')),
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

namespace App\Filament\Admin\Resources\ActivityLogResource\Pages;

use App\Filament\Admin\Resources\ActivityLogResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Pages\ViewRecord;

class ListActivityLogs extends ListRecords
{
    protected static string $resource = ActivityLogResource::class;
    protected function getHeaderActions(): array
    {
        return [];
    }
}

class ViewActivityLog extends ViewRecord
{
    protected static string $resource = ActivityLogResource::class;
}
