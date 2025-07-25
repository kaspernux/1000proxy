<?php

namespace App\Filament\Clusters\ServerManagement\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use App\Models\ServerTag;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Clusters\ServerManagement;
use Filament\Forms\Components\MarkdownEditor;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Clusters\ServerManagement\Resources\ServerTagResource\Pages;
use App\Filament\Clusters\ServerManagement\Resources\ServerTagResource\RelationManagers;

class ServerTagResource extends Resource
{
    protected static ?string $model = ServerTag::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $cluster = ServerManagement::class;

    protected static ?int $navigationSort = 8;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $navigationGroup = 'SERVER ORGANIZATION';

    public static function getLabel(): string
    {
        return 'Tags';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Group::make()->schema([
                    Section::make('🏷️ Tag Information')->schema([
                        TextInput::make('name')
                            ->label('Tag Name')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(1)
                            ->helperText('Enter a descriptive name for this tag'),

                        Select::make('server_id')
                            ->label('Associated Server')
                            ->relationship('server', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->columnSpan(1)
                            ->helperText('Select the server this tag belongs to'),
                    ])->columns(2),
                ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Tag Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->tooltip('Tag identifier'),

                BadgeColumn::make('server.name')
                    ->label('Server')
                    ->searchable()
                    ->sortable()
                    ->color('primary')
                    ->tooltip('Associated server'),

                TextColumn::make('server.status')
                    ->label('Server Status')
                    ->badge()
                    ->colors([
                        'success' => 'online',
                        'warning' => 'maintenance',
                        'danger' => 'offline',
                        'info' => 'limited',
                    ])
                    ->tooltip('Current server status'),

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
                SelectFilter::make('server_id')
                    ->relationship('server', 'name')
                    ->label('Server')
                    ->searchable()
                    ->preload()
                    ->multiple(),
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make()
                        ->tooltip('View tag details'),

                    EditAction::make()
                        ->tooltip('Edit tag'),

                    Action::make('view_server')
                        ->label('View Server')
                        ->icon('heroicon-o-server')
                        ->color('primary')
                        ->url(fn ($record) => route('filament.admin.clusters.server-management.resources.servers.view', $record->server))
                        ->tooltip('View associated server'),

                    DeleteAction::make()
                        ->tooltip('Delete tag'),
                ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->tooltip('Delete selected tags'),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100]);
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
            'index' => Pages\ListServerTags::route('/'),
            'create' => Pages\CreateServerTag::route('/create'),
            'view' => Pages\ViewServerTag::route('/{record}'),
            'edit' => Pages\EditServerTag::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'server.name'];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return static::getModel()::count() > 10 ? 'success' : 'warning';
    }
}
