<?php

namespace App\Filament\Clusters\ServerManagement\Resources\ServerBrandResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\Server;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Group;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use App\Services\XUIService;
use Filament\Notifications\Notification;

class ServersRelationManager extends RelationManager
{
    protected static string $relationship = 'servers';
    protected static ?string $title = 'Brand Servers';
    protected static ?string $modelLabel = 'Server';
    protected static ?string $pluralModelLabel = 'Servers';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Group::make([
                    Section::make('Server Basic Information')
                        ->schema([
                            Forms\Components\TextInput::make('name')
                                ->required()
                                ->maxLength(255)
                                ->helperText('Friendly name for server identification'),

                            Forms\Components\TextInput::make('country')
                                ->required()
                                ->maxLength(255)
                                ->helperText('Server location country'),

                            Forms\Components\Select::make('server_category_id')
                                ->relationship('category', 'name')
                                ->required()
                                ->searchable()
                                ->preload()
                                ->helperText('Server category'),

                            Forms\Components\Select::make('type')
                                ->options([
                                    'sanaei' => 'X-RAY (3X-UI Sanaei)',
                                    'alireza' => 'Alireza Panel',
                                    'marzban' => 'Marzban Panel',
                                    'Other' => 'Other Panel Type',
                                ])
                                ->required()
                                ->helperText('Panel type for API integration'),
                        ])
                        ->columns(2),

                    Section::make('Connection Settings')
                        ->schema([
                            Forms\Components\TextInput::make('host')
                                ->label('Panel Host (IP or Domain)')
                                ->required()
                                ->maxLength(255)
                                ->helperText('Panel access host'),

                            Forms\Components\TextInput::make('panel_port')
                                ->label('Panel Port')
                                ->required()
                                ->numeric()
                                ->default(2053)
                                ->helperText('3X-UI panel access port'),

                            Forms\Components\TextInput::make('ip')
                                ->label('Proxy Server IP')
                                ->required()
                                ->maxLength(255)
                                ->helperText('Server IP for proxy connections'),

                            Forms\Components\TextInput::make('port')
                                ->label('Proxy Port')
                                ->numeric()
                                ->helperText('Main proxy service port'),
                        ])
                        ->columns(2),
                ])->columnSpan(2),

                Group::make([
                    Section::make('Status & Settings')
                        ->schema([
                            Forms\Components\Select::make('status')
                                ->options([
                                    'up' => 'Online',
                                    'down' => 'Offline',
                                    'paused' => 'Paused',
                                    'maintenance' => 'Maintenance',
                                ])
                                ->default('up')
                                ->required(),

                            Forms\Components\Toggle::make('is_active')
                                ->label('Active')
                                ->default(true),

                            Forms\Components\TextInput::make('max_clients')
                                ->label('Max Clients')
                                ->numeric()
                                ->default(100)
                                ->helperText('Maximum number of clients'),

                            Forms\Components\TextInput::make('total_clients')
                                ->label('Current Clients')
                                ->numeric()
                                ->default(0)
                                ->helperText('Current active clients'),
                        ]),
                ])->columnSpan(1),
            ])
            ->columns(3);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Server Name')
                    ->sortable()
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => 'up',
                        'danger' => 'down',
                        'warning' => 'paused',
                        'secondary' => 'maintenance',
                    ])
                    ->icons([
                        'heroicon-o-check-circle' => 'up',
                        'heroicon-o-x-circle' => 'down',
                        'heroicon-o-pause-circle' => 'paused',
                        'heroicon-o-wrench-screwdriver' => 'maintenance',
                    ]),

                Tables\Columns\TextColumn::make('country')
                    ->label('Location')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-map-pin'),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('Category')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Panel Type')
                    ->badge()
                    ->colors([
                        'primary' => 'sanaei',
                        'secondary' => 'alireza',
                        'warning' => 'marzban',
                        'gray' => 'Other',
                    ]),

                Tables\Columns\TextColumn::make('host')
                    ->label('Host')
                    ->searchable()
                    ->copyable()
                    ->limit(20)
                    ->tooltip('Click to copy'),

                Tables\Columns\TextColumn::make('panel_port')
                    ->label('Port')
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-circle'),

                Tables\Columns\TextColumn::make('total_clients')
                    ->label('Clients')
                    ->numeric()
                    ->sortable()
                    ->alignCenter()
                    ->color(fn ($record) => $record->total_clients > ($record->max_clients * 0.8) ? 'warning' : 'success'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Added')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'up' => 'Online',
                        'down' => 'Offline',
                        'paused' => 'Paused',
                        'maintenance' => 'Maintenance',
                    ])
                    ->multiple(),

                SelectFilter::make('server_category_id')
                    ->label('Category')
                    ->relationship('category', 'name')
                    ->multiple()
                    ->preload(),

                SelectFilter::make('type')
                    ->label('Panel Type')
                    ->options([
                        'sanaei' => 'X-RAY (3X-UI Sanaei)',
                        'alireza' => 'Alireza Panel',
                        'marzban' => 'Marzban Panel',
                        'Other' => 'Other Panel Type',
                    ])
                    ->multiple(),

                Tables\Filters\Filter::make('is_active')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->where('is_active', true)),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        // Auto-assign the brand from the parent record
                        $data['server_brand_id'] = $this->ownerRecord->id;
                        return $data;
                    }),

                Tables\Actions\Action::make('test_all_connections')
                    ->label('Test All Connections')
                    ->icon('heroicon-o-signal')
                    ->color('info')
                    ->action(function () {
                        $servers = $this->ownerRecord->servers;
                        $results = [];

                        foreach ($servers as $server) {
                            try {
                                $xuiService = new XUIService($server);
                                $success = $xuiService->login();

                                $results[] = [
                                    'server' => $server->name,
                                    'status' => $success ? 'success' : 'failed'
                                ];

                                $server->update([
                                    'status' => $success ? 'up' : 'down',
                                    'last_connected_at' => now(),
                                ]);
                            } catch (\Exception $e) {
                                $results[] = [
                                    'server' => $server->name,
                                    'status' => 'error',
                                    'error' => $e->getMessage()
                                ];

                                $server->update([
                                    'status' => 'down',
                                    'last_connected_at' => now(),
                                ]);
                            }
                        }

                        $successCount = collect($results)->where('status', 'success')->count();
                        $totalCount = count($results);

                        Notification::make()
                            ->title('Connection Test Completed')
                            ->body("Connected to {$successCount} out of {$totalCount} servers")
                            ->color($successCount === $totalCount ? 'success' : 'warning')
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Test All Server Connections')
                    ->modalDescription('This will test the connection to all servers under this brand. This may take a few moments.'),
            ])
            ->actions([
                Tables\Actions\Action::make('test_connection')
                    ->label('Test Connection')
                    ->icon('heroicon-o-signal')
                    ->color('info')
                    ->action(function ($record) {
                        try {
                            $xuiService = new XUIService($record);
                            $success = $xuiService->login();

                            $record->update([
                                'status' => $success ? 'up' : 'down',
                                'last_connected_at' => now(),
                            ]);

                            Notification::make()
                                ->title($success ? 'Connection Successful' : 'Connection Failed')
                                ->color($success ? 'success' : 'danger')
                                ->send();
                        } catch (\Exception $e) {
                            $record->update([
                                'status' => 'down',
                                'last_connected_at' => now(),
                            ]);

                            Notification::make()
                                ->title('Connection Error')
                                ->body($e->getMessage())
                                ->color('danger')
                                ->send();
                        }
                    }),

                Tables\Actions\Action::make('view_panel')
                    ->label('Open Panel')
                    ->icon('heroicon-o-link')
                    ->color('primary')
                    ->url(fn ($record) => $record->getFullPanelUrl())
                    ->openUrlInNewTab(),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(fn ($records) => $records->each(fn ($record) => $record->update(['is_active' => true])))
                        ->requiresConfirmation(),

                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate Selected')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(fn ($records) => $records->each(fn ($record) => $record->update(['is_active' => false])))
                        ->requiresConfirmation(),

                    Tables\Actions\BulkAction::make('test_connections')
                        ->label('Test Connections')
                        ->icon('heroicon-o-signal')
                        ->color('info')
                        ->action(function ($records) {
                            $results = [];

                            foreach ($records as $server) {
                                try {
                                    $xuiService = new XUIService($server);
                                    $success = $xuiService->login();

                                    $results[] = [
                                        'server' => $server->name,
                                        'status' => $success ? 'success' : 'failed'
                                    ];

                                    $server->update([
                                        'status' => $success ? 'up' : 'down',
                                        'last_connected_at' => now(),
                                    ]);
                                } catch (\Exception $e) {
                                    $results[] = [
                                        'server' => $server->name,
                                        'status' => 'error'
                                    ];

                                    $server->update([
                                        'status' => 'down',
                                        'last_connected_at' => now(),
                                    ]);
                                }
                            }

                            $successCount = collect($results)->where('status', 'success')->count();
                            $totalCount = count($results);

                            Notification::make()
                                ->title('Bulk Connection Test Completed')
                                ->body("Connected to {$successCount} out of {$totalCount} servers")
                                ->color($successCount === $totalCount ? 'success' : 'warning')
                                ->send();
                        })
                        ->requiresConfirmation(),

                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
