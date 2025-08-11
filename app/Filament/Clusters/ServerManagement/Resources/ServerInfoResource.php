<?php

namespace App\Filament\Clusters\ServerManagement\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use App\Models\ServerInfo;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\MarkdownEditor;
use Illuminate\Support\Facades\Redirect;
use App\Livewire\Traits\LivewireAlertV4;
use App\Services\XUIService;
use App\Filament\Clusters\ServerManagement;
use App\Filament\Clusters\ServerManagement\Resources\ServerInfoResource\Pages;


class ServerInfoResource extends Resource
{
    use LivewireAlertV4;

    protected static ?string $cluster = ServerManagement::class;

    protected static ?string $model = ServerInfo::class;

    protected static ?string $navigationIcon = 'heroicon-o-information-circle';

    protected static ?string $navigationLabel = 'Server Info';

    protected static ?string $pluralModelLabel = 'Server Info';

    protected static ?string $navigationGroup = 'SERVER SETTINGS';

    protected static ?int $navigationSort = 5;

    protected static ?string $recordTitleAttribute = 'title';

    public static function getLabel(): string
    {
        return 'About';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Group::make()->schema([
                    Section::make('🏷️ Server Information Details')->schema([
                        Select::make('server_id')
                            ->relationship('server', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpan(2)
                            ->helperText('Select the server this information belongs to'),

                        TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(1)
                            ->helperText('Information title or name'),

                        TextInput::make('tag')
                            ->maxLength(255)
                            ->columnSpan(1)
                            ->helperText('Optional tag for categorization'),

                        TextInput::make('ucount')
                            ->label('User Count')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->columnSpan(1)
                            ->helperText('Number of users/connections'),

                        Select::make('state')
                            ->required()
                            ->options([
                                'up' => '🟢 Up',
                                'down' => '🔴 Down',
                                'paused' => '⏸️ Paused',
                                'maintenance' => '🔧 Maintenance',
                            ])
                            ->columnSpan(1)
                            ->helperText('Current operational state'),

                        Toggle::make('active')
                            ->label('Active Status')
                            ->required()
                            ->default(true)
                            ->columnSpan(2)
                            ->helperText('Enable/disable this server information'),
                    ])->columns(2),

                    Section::make('📝 Detailed Description')->schema([
                        MarkdownEditor::make('remark')
                            ->label('Detailed Information')
                            ->columnSpanFull()
                            ->fileAttachmentsDirectory('ServerInfo')
                            ->helperText('Detailed description and remarks about this server information'),
                    ])
                ])->columnSpan(2),

                Group::make()->schema([
                    Section::make('⚙️ Status & Control')->schema([
                        Toggle::make('active')
                            ->label('Active')
                            ->required()
                            ->default(true),
                    ]),

                    Section::make('📊 Statistics')->schema([
                        Forms\Components\Placeholder::make('created_at')
                            ->label('Created At')
                            ->content(fn ($record): string => $record?->created_at?->diffForHumans() ?? '-'),

                        Forms\Components\Placeholder::make('updated_at')
                            ->label('Last Updated')
                            ->content(fn ($record): string => $record?->updated_at?->diffForHumans() ?? '-'),
                    ])->hidden(fn ($context) => $context === 'create'),
                ])->columnSpan(1)
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('server.name')
                    ->label('Server')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->tooltip(fn ($record) => "Server: {$record->server?->name}"),

                Tables\Columns\TextColumn::make('title')
                    ->label('Title')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('tag')
                    ->label('Tag')
                    ->searchable()
                    ->badge()
                    ->color('info')
                    ->placeholder('No tag'),

                Tables\Columns\TextColumn::make('ucount')
                    ->label('User Count')
                    ->numeric()
                    ->sortable()
                    ->alignCenter()
                    ->color(fn ($state) => $state > 100 ? 'warning' : ($state > 50 ? 'info' : 'success')),

                Tables\Columns\IconColumn::make('active')
                    ->label('Active')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->alignCenter(),

                Tables\Columns\TextColumn::make('state')
                    ->label('State')
                    ->sortable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'up' => 'success',
                        'down' => 'danger',
                        'paused' => 'warning',
                        'maintenance' => 'info',
                        default => 'gray',
                    })
                    ->icon(fn (string $state): string => match ($state) {
                        'up' => 'heroicon-o-check-circle',
                        'down' => 'heroicon-o-x-circle',
                        'paused' => 'heroicon-o-pause-circle',
                        'maintenance' => 'heroicon-o-wrench',
                        default => 'heroicon-o-question-mark-circle',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->since(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->since(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('active')
                    ->label('Status')
                    ->options([
                        '1' => 'Active',
                        '0' => 'Inactive',
                    ]),

                Tables\Filters\SelectFilter::make('state')
                    ->label('State')
                    ->options([
                        'up' => '🟢 Up',
                        'down' => '🔴 Down',
                        'paused' => '⏸️ Paused',
                        'maintenance' => '🔧 Maintenance',
                    ]),

                Tables\Filters\Filter::make('high_usage')
                    ->label('High Usage (>50 users)')
                    ->query(fn ($query) => $query->where('ucount', '>', 50)),

                Tables\Filters\SelectFilter::make('server')
                    ->relationship('server', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->color('info'),

                    Tables\Actions\EditAction::make()
                        ->color('warning'),

                    Tables\Actions\Action::make('toggle_state')
                        ->label('Toggle State')
                        ->icon('heroicon-o-arrow-path')
                        ->color('info')
                        ->action(function ($record) {
                            $newState = match ($record->state) {
                                'up' => 'paused',
                                'paused' => 'up',
                                'down' => 'up',
                                'maintenance' => 'up',
                                default => 'up',
                            };

                            $record->update(['state' => $newState]);

                            \Filament\Notifications\Notification::make()
                                ->title('State Updated')
                                ->body("Server info state changed to: {$newState}")
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\Action::make('view_server')
                        ->label('View Server')
                        ->icon('heroicon-o-server')
                        ->color('success')
                        ->url(fn ($record) => route('filament.admin.clusters.server-management.resources.servers.view', $record->server_id))
                        ->openUrlInNewTab(),

                    Tables\Actions\DeleteAction::make()
                        ->color('danger'),
                ])
                ->label('Actions')
                ->color('gray')
                ->icon('heroicon-o-ellipsis-vertical')
                ->size('sm'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),

                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate Selected')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each->update(['active' => true]);
                            \Filament\Notifications\Notification::make()
                                ->title('Server Infos Activated')
                                ->body(count($records) . ' server infos have been activated.')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate Selected')
                        ->icon('heroicon-o-x-mark')
                        ->color('danger')
                        ->action(function ($records) {
                            $records->each->update(['active' => false]);
                            \Filament\Notifications\Notification::make()
                                ->title('Server Infos Deactivated')
                                ->body(count($records) . ' server infos have been deactivated.')
                                ->warning()
                                ->send();
                        }),

                    Tables\Actions\BulkAction::make('set_state_up')
                        ->label('Set State to Up')
                        ->icon('heroicon-o-arrow-up')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each->update(['state' => 'up']);
                            \Filament\Notifications\Notification::make()
                                ->title('State Updated')
                                ->body(count($records) . ' server infos set to UP state.')
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServerInfos::route('/'),
            'create' => Pages\CreateServerInfo::route('/create'),
            'view' => Pages\ViewServerInfo::route('/{record}'),
            'edit' => Pages\EditServerInfo::route('/{record}/edit'),
        ];
    }
}
