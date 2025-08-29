<?php

namespace App\Filament\Clusters\ServerManagement\Resources;

use App\Filament\Clusters\ServerManagement;

use App\Filament\Clusters\ServerManagement\Resources\FeatureAdResource\Pages;
use App\Models\FeatureAd;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Forms;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\Action;
use BackedEnum;
use UnitEnum;

class FeatureAdResource extends Resource
{
    protected static ?string $model = FeatureAd::class;
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-flag';
    protected static ?string $navigationLabel = 'Feature Ads';
    protected static ?string $pluralModelLabel = 'Feature Ads';
    protected static ?string $cluster = ServerManagement::class;
    protected static string | UnitEnum | null $navigationGroup = 'SERVER SETTINGS';
    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            \Filament\Schemas\Components\Grid::make(2)
                ->schema([
                    \Filament\Schemas\Components\Section::make('Feature Ad Details')
                        ->icon('heroicon-o-flag')
                        ->schema([
                            TextInput::make('title')
                                ->required()
                                ->maxLength(255)
                                ->helperText('Required. If you see validation errors, check all required fields.'),
                            TextInput::make('subtitle')
                                ->maxLength(255)
                                ->helperText('Optional subtitle.'),
                            Textarea::make('body')
                                ->rows(5)
                                ->helperText('Main ad content.'),
                        ]),
                    \Filament\Schemas\Components\Section::make('Settings')
                        ->icon('heroicon-o-cog-8-tooth')
                        ->schema([
                            Select::make('server_id')
                                ->relationship('server', 'name')
                                ->searchable()
                                ->preload()
                                ->helperText('Attach to a server (optional). If no servers are available, you can create the ad without linking.')
                                ->nullable(),
                            Forms\Components\Toggle::make('is_active')
                                ->default(true)
                                ->helperText('Enable or disable this ad.'),
                            Forms\Components\DateTimePicker::make('starts_at')
                                ->helperText('Optional start date.'),
                            Forms\Components\DateTimePicker::make('ends_at')
                                ->helperText('Optional end date.'),
                        ]),
                ])
                ->columnSpanFull(),
        ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->headerActions([
                \Filament\Actions\CreateAction::make()
                    ->label('Add New Feature')
                    ->icon('heroicon-o-plus')
                    ->color('success'),
            ])
            ->columns([
                TextColumn::make('id')->label('ID')->sortable(),
                TextColumn::make('title')->searchable()->limit(50),
                TextColumn::make('server.name')->label('Server')->sortable()->toggleable(),
                TextColumn::make('metadata->xui_total_online')
                    ->label('Online')
                    ->sortable()
                    ->alignCenter()
                    ->getStateUsing(fn ($record) => is_array($record->metadata) ? ($record->metadata['xui_total_online'] ?? 0) : ($record->metadata->xui_total_online ?? 0)),
                IconColumn::make('is_active')->boolean()->label('Active'),
                TextColumn::make('starts_at')->since()->label('Starts'),
                TextColumn::make('ends_at')->since()->label('Ends'),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
                Action::make('fetch_xui')
                    ->label('Fetch X-UI Info (background)')
                    ->icon('heroicon-o-arrow-path')
                    ->requiresConfirmation()
                    ->action(function (FeatureAd $record) {
                        \App\Jobs\FetchFeatureAdXuiInfo::dispatch($record->id);
                        \Filament\Notifications\Notification::make()->title('Fetch queued')->body('X-UI fetch job dispatched')->success()->send();
                    })
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        return $schema->schema([
            \Filament\Schemas\Components\Tabs::make('Feature Ad')
                ->tabs([
                    \Filament\Schemas\Components\Tabs\Tab::make('Overview')
                        ->icon('heroicon-m-eye')
                        ->schema([
                            \Filament\Schemas\Components\Section::make('FeatureAd Details')
                                ->columns(2)
                                ->columnSpanFull()
                                ->schema([
                                    \Filament\Infolists\Components\TextEntry::make('title')->label('Title'),
                                    \Filament\Infolists\Components\TextEntry::make('subtitle')->label('Subtitle'),
                                    \Filament\Infolists\Components\TextEntry::make('body')->label('Body'),
                                    \Filament\Infolists\Components\IconEntry::make('is_active')->label('Active')->boolean(),
                                    \Filament\Infolists\Components\TextEntry::make('starts_at')->label('Starts At')->since(),
                                    \Filament\Infolists\Components\TextEntry::make('ends_at')->label('Ends At')->since(),
                                ]),
                        ]),
                    \Filament\Schemas\Components\Tabs\Tab::make('Server')
                        ->icon('heroicon-m-server')
                        ->schema([
                            \Filament\Schemas\Components\Section::make('Server Details')
                                ->columns(2)
                                ->columnSpanFull()
                                ->schema([
                                    \Filament\Infolists\Components\TextEntry::make('server.name')->label('Name'),
                                    \Filament\Infolists\Components\TextEntry::make('server.host')->label('Host'),
                                    \Filament\Infolists\Components\TextEntry::make('server.panel_port')->label('Panel Port'),
                                    \Filament\Infolists\Components\TextEntry::make('server.ip')->label('IP'),
                                    \Filament\Infolists\Components\TextEntry::make('server.country')->label('Country'),
                                    \Filament\Infolists\Components\TextEntry::make('server.status')->label('Status'),
                                    \Filament\Infolists\Components\TextEntry::make('server.health_status')->label('Health')->badge(),
                                    \Filament\Infolists\Components\TextEntry::make('server.total_online_clients')->label('Online Clients'),
                                    \Filament\Infolists\Components\TextEntry::make('server.active_inbounds')->label('Active Inbounds'),
                                    \Filament\Infolists\Components\TextEntry::make('server.last_global_sync_at')->label('Last Sync')->since(),
                                    \Filament\Infolists\Components\TextEntry::make('server.panel_url')
                                        ->label('Panel URL')
                                        ->url(fn($record) => $record->server?->getPanelAccessUrl())
                                        ->openUrlInNewTab(),
                                ]),
                        ]),
                    \Filament\Schemas\Components\Tabs\Tab::make('Server Info')
                        ->icon('heroicon-m-information-circle')
                        ->schema([
                            \Filament\Schemas\Components\Section::make('ServerInfo Details')
                                ->columns(2)
                                ->columnSpanFull()
                                ->schema([
                                    \Filament\Infolists\Components\TextEntry::make('server.info.title')->label('Info Title'),
                                    \Filament\Infolists\Components\TextEntry::make('server.info.tag')->label('Tag'),
                                    \Filament\Infolists\Components\TextEntry::make('server.info.ucount')->label('User Count'),
                                    \Filament\Infolists\Components\TextEntry::make('server.info.state')->label('State')->badge(),
                                    \Filament\Infolists\Components\IconEntry::make('server.info.active')->label('Active')->boolean(),
                                    \Filament\Infolists\Components\TextEntry::make('server.info.remark')->label('Remark')->markdown(),
                                ]),
                        ]),
                    \Filament\Schemas\Components\Tabs\Tab::make('X-UI Data')
                        ->icon('heroicon-m-computer-desktop')
                        ->schema([
                            \Filament\Schemas\Components\Section::make('X-UI Metadata')
                                ->columns(2)
                                ->columnSpanFull()
                                ->schema([
                                    \Filament\Infolists\Components\TextEntry::make('metadata.xui_total_online')->label('Total Online'),
                                    \Filament\Infolists\Components\TextEntry::make('metadata.last_fetched_at')->label('Last Fetched')->since(),
                                    \Filament\Infolists\Components\TextEntry::make('metadata.xui_health')->label('Health Summary')->formatStateUsing(fn($state) => is_array($state) ? json_encode($state) : (string)$state),
                                ]),
                        ]),
                ])
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFeatureAds::route('/'),
            'create' => Pages\CreateFeatureAd::route('/create'),
            'view' => Pages\ViewFeatureAd::route('/{record}'),
            'edit' => Pages\EditFeatureAd::route('/{record}/edit'),
        ];
    }
}
