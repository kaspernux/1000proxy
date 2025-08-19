<?php
namespace App\Filament\Clusters\ServerManagement\Resources;

use App\Filament\Clusters\ServerManagement;
use App\Filament\Clusters\ServerManagement\Resources\ClientTrafficResource\Pages;
use App\Filament\Clusters\ServerManagement\Resources\ClientTrafficResource\RelationManagers;
use App\Models\ClientTraffic;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\DateTimePicker;
use Filament\Schemas\Components\Group;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkAction;
use Carbon\Carbon;
use BackedEnum;
use UnitEnum;
use Filament\Schemas\Schema;

class ClientTrafficResource extends Resource
{
    protected static ?string $model = ClientTraffic::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-chart-pie';

    protected static ?string $cluster = ServerManagement::class;

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return (bool) ($user?->isAdmin() || $user?->isManager() || $user?->isSupportManager());
    }

    protected static UnitEnum|string|null $navigationGroup = 'TRAFFIC MONITORING';

    protected static ?int $navigationSort = 9;

    protected static ?string $recordTitleAttribute = 'email';

    public static function getLabel(): string
    {
        return 'Client Traffic';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Group::make()->schema([
                    Section::make('🔗 Client & Server Association')->schema([
                        Select::make('server_inbound_id')
                            ->label('Server Inbound')
                            ->relationship('serverInbound', 'remark')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->columnSpan(1)
                            ->helperText('Select the server inbound for this traffic record'),

                        Select::make('customer_id')
                            ->label('Customer')
                            ->relationship('customer', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->columnSpan(1)
                            ->helperText('Associated customer'),

                        TextInput::make('email')
                            ->label('Client Email')
                            ->required()
                            ->email()
                            ->maxLength(255)
                            ->columnSpan(1)
                            ->helperText('Client email identifier'),

                        Toggle::make('enable')
                            ->label('Enabled')
                            ->required()
                            ->default(true)
                            ->columnSpan(1)
                            ->helperText('Enable/disable traffic tracking'),
                    ])->columns(2),
                ])->columnSpan(2),

                Group::make()->schema([
                    Section::make('📊 Traffic Statistics')->schema([
                        TextInput::make('up')
                            ->label('Upload (Bytes)')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->helperText('Total upload traffic in bytes'),

                        TextInput::make('down')
                            ->label('Download (Bytes)')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->helperText('Total download traffic in bytes'),

                        TextInput::make('total')
                            ->label('Total (Bytes)')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->helperText('Total combined traffic in bytes'),

                        TextInput::make('reset')
                            ->label('Reset Count')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->helperText('Number of times traffic was reset'),
                    ]),

                    Section::make('⏰ Expiry')->schema([
                        DateTimePicker::make('expiry_time')
                            ->label('Expiry Date & Time')
                            ->required()
                            ->helperText('When this traffic record expires'),
                    ]),
                ])->columnSpan(1),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        $table = $table
            ->columns([
                TextColumn::make('email')
                    ->label('Client Email')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->tooltip('Client identifier'),

                BadgeColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable()
                    ->color('primary')
                    ->tooltip('Associated customer'),

                BadgeColumn::make('serverInbound.remark')
                    ->label('Inbound')
                    ->searchable()
                    ->sortable()
                    ->color('info')
                    ->tooltip('Server inbound configuration'),

                IconColumn::make('enable')
                    ->label('Enabled')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                TextColumn::make('traffic_usage')
                    ->label('Traffic Used')
                    ->getStateUsing(function ($record) {
                        $totalGB = ($record->up + $record->down) / 1024 / 1024 / 1024;
                        return number_format($totalGB, 2) . ' GB';
                    })
                    ->badge()
                    ->color(function ($record) {
                        $totalGB = ($record->up + $record->down) / 1024 / 1024 / 1024;
                        if ($totalGB > 100) return 'danger';
                        if ($totalGB > 50) return 'warning';
                        if ($totalGB > 10) return 'info';
                        return 'success';
                    })
                    ->tooltip(function ($record) {
                        $upMB = number_format($record->up / 1024 / 1024, 2);
                        $downMB = number_format($record->down / 1024 / 1024, 2);
                        return "Upload: {$upMB} MB\nDownload: {$downMB} MB";
                    }),

                TextColumn::make('up')
                    ->label('Upload')
                    ->getStateUsing(fn ($record) => number_format($record->up / 1024 / 1024, 2) . ' MB')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('down')
                    ->label('Download')
                    ->getStateUsing(fn ($record) => number_format($record->down / 1024 / 1024, 2) . ' MB')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('total')
                    ->label('Total')
                    ->getStateUsing(fn ($record) => number_format($record->total / 1024 / 1024, 2) . ' MB')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('reset')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color('warning')
                    ->tooltip('Number of times traffic was reset'),

                TextColumn::make('expiry_time')
                    ->label('Expires')
                    ->dateTime()
                    ->sortable()
                    ->badge()
                    ->color(function ($record) {
                        if (!$record->expiry_time) return 'primary';

                        $expiry = Carbon::parse($record->expiry_time);
                        $now = Carbon::now();

                        if ($expiry->isPast()) return 'danger';
                        if ($expiry->diffInDays($now) <= 7) return 'warning';
                        if ($expiry->diffInDays($now) <= 30) return 'info';
                        return 'success';
                    })
                    ->tooltip(function ($record) {
                        if (!$record->expiry_time) return 'No expiry set';
                        return Carbon::parse($record->expiry_time)->diffForHumans();
                    }),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('server_inbound_id')
                    ->relationship('serverInbound', 'remark')
                    ->label('Inbound')
                    ->searchable()
                    ->preload()
                    ->multiple(),

                SelectFilter::make('customer_id')
                    ->relationship('customer', 'name')
                    ->label('Customer')
                    ->searchable()
                    ->preload()
                    ->multiple(),

                TernaryFilter::make('enable')
                    ->label('Enabled Status')
                    ->placeholder('All records')
                    ->trueLabel('Enabled only')
                    ->falseLabel('Disabled only'),

                Tables\Filters\Filter::make('expired')
                    ->toggle()
                    ->label('Expired')
                    ->query(fn (Builder $query): Builder => $query->where('expiry_time', '<', now())),

                Tables\Filters\Filter::make('high_usage')
                    ->toggle()
                    ->label('High Usage (>10GB)')
                    ->query(fn (Builder $query): Builder => $query->whereRaw('(up + down) > ?', [10 * 1024 * 1024 * 1024])),
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make()
                        ->tooltip('View traffic details'),

                    EditAction::make()
                        ->tooltip('Edit traffic record'),

                    Action::make('reset_traffic')
                        ->label('Reset Traffic')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Reset Traffic Statistics')
                        ->modalDescription('Are you sure you want to reset traffic statistics for this client?')
                        ->action(function ($record) {
                            $record->update([
                                'up' => 0,
                                'down' => 0,
                                'total' => 0,
                                'reset' => $record->reset + 1,
                            ]);

                            \Filament\Notifications\Notification::make()
                                ->title('Traffic reset successfully')
                                ->success()
                                ->send();
                        })
                        ->tooltip('Reset traffic statistics'),

                    DeleteAction::make()
                        ->tooltip('Delete traffic record'),
                ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->tooltip('Delete selected records'),

                    BulkAction::make('reset_selected_traffic')
                        ->label('Reset Traffic for Selected')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $count = 0;
                            foreach ($records as $record) {
                                $record->update([
                                    'up' => 0,
                                    'down' => 0,
                                    'total' => 0,
                                    'reset' => $record->reset + 1,
                                ]);
                                $count++;
                            }

                            \Filament\Notifications\Notification::make()
                                ->title('Bulk traffic reset completed')
                                ->body("Reset traffic for {$count} records.")
                                ->success()
                                ->send();
                        })
                        ->tooltip('Reset traffic for selected records'),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('60s'); // Auto-refresh every minute for real-time traffic monitoring

        return \App\Filament\Concerns\HasPerformanceOptimizations::applyTablePreset($table, [
            'defaultPage' => 50,
            'empty' => [
                'icon' => 'heroicon-o-chart-pie',
                'heading' => 'No traffic records',
                'description' => 'Adjust filters or time window.',
            ],
        ]);
    }

    public static function getRelations(): array
    {
        return [
            // Add relation managers if needed
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClientTraffic::route('/'),
            'create' => Pages\CreateClientTraffic::route('/create'),
            'view' => Pages\ViewClientTraffic::route('/{record}'),
            'edit' => Pages\EditClientTraffic::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['email', 'customer.name', 'serverInbound.remark'];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return static::getModel()::count() > 50 ? 'success' : 'warning';
    }
}
