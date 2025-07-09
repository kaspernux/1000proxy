<?php

namespace App\Filament\Clusters\ServerManagement\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Server;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\ImageColumn;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Filters\SelectFilter;
use App\Filament\Clusters\ServerManagement;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Resources\Pages\CreateRecord;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use App\Http\Controllers\XUIService;
use Illuminate\Support\Facades\Redirect;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use App\Filament\Clusters\ServerManagement\Resources\ServerResource\Pages;


class ServerResource extends Resource
{
    use LivewireAlert;

    protected static ?string $model = Server::class;

    protected static ?string $navigationIcon = 'heroicon-o-server';

    protected static ?string $navigationGroup = 'PROXY SETTINGS';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getLabel(): string
    {
        return 'Servers';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Group::make()->schema([
                    Section::make('Server Info')->schema([
                        TextInput::make('name')
                            ->maxLength(255)
                            ->columns(2),
                        TextInput::make('country')
                            ->maxLength(255)
                            ->columns(2),
                        Forms\Components\Select::make('server_category_id')
                            ->relationship('category', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->columns(2),
                        Forms\Components\Select::make('server_brand_id')
                            ->relationship('brand', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->columns(2),
                    ])->columns(2),

                    Forms\Components\Section::make('Settings and Security')
                        ->schema([
                            Forms\Components\Select::make('type')
                                ->options([
                                    'sanaei' => 'X-RAY',
                                    'alireza' => 'Alireza',
                                    'marzban' => 'Marzban',
                                    'Other' => 'Others',])
                                ->required()
                                ->maxWidth(255),
                            Forms\Components\TextInput::make('host')
                                ->label('Host (IP or Domain)')
                                ->required()
                                ->maxLength(255)
                                ->helperText('Example: 192.168.1.100 or example.com'),
                            Forms\Components\TextInput::make('panel_port')
                                ->label('Panel Port')
                                ->required()
                                ->numeric()
                                ->default(2053)
                                ->helperText('3X-UI panel port (default: 2053)'),
                            Forms\Components\TextInput::make('web_base_path')
                                ->label('Web Base Path')
                                ->maxLength(255)
                                ->helperText('Optional: Leave empty if panel is at root, e.g., /admin'),
                            Forms\Components\TextInput::make('ip')
                                ->label('Server IP')
                                ->required()
                                ->maxLength(255)
                                ->helperText('Actual server IP for proxy connections'),
                        Forms\Components\TextInput::make('port')
                                ->maxLength(255),
                            Forms\Components\TextInput::make('reality')
                                ->maxLength(255),
                            Forms\Components\TextInput::make('sni')
                                ->label('SNI')
                                ->maxLength(255),
                            Forms\Components\TextInput::make('header_type')
                                ->maxLength(255),
                            Forms\Components\TextInput::make('security')
                                ->maxLength(255),
                            Forms\Components\TextInput::make('request_header')
                                ->maxLength(255),
                            Forms\Components\TextInput::make('response_header')
                                ->maxLength(255),
                            Forms\Components\TextInput::make('tlsSettings')
                                ->maxLength(255)
                                ->columnSpanFull(),
                        ])->columns(2),

                    Section::make('Description')->schema([
                        MarkdownEditor::make('description')
                            ->fileAttachmentsDirectory('servers')
                            ->label(''),
                    ]),
                ])->columnSpan(2),
                Group::make()->schema([
                    Section::make('Brand and Status')->schema([
                        FileUpload::make('flag')
                            ->image()
                            ->directory('servers'),
                        Forms\Components\Select::make('status')
                            ->options([
                                'up' => 'Up',
                                'down' => 'Down',
                                'paused' => 'Paused',
                            ]),
                    ]),
                Forms\Components\Section::make('Authentication')
                        ->schema([
                            Forms\Components\TextInput::make('username')
                                ->required()
                                ->maxLength(255),
                            Forms\Components\TextInput::make('password')
                                ->password()
                                ->dehydrated(fn ($state) => filled($state))
                                ->required(fn ($livewire): bool => $livewire instanceof CreateRecord)
                                ->maxLength(255),
                        ]),
                ])->columnSpan(1),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
             ->columns([
                ImageColumn::make('flag')
                    ->label('Flag')
                    ->url(fn ($record) => $record->flag),
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('username')
                    ->searchable(),
                Tables\Columns\TextColumn::make('host')
                    ->label('Host')
                    ->searchable(),
                Tables\Columns\TextColumn::make('panel_port')
                    ->label('Panel Port')
                    ->sortable(),
                Tables\Columns\TextColumn::make('full_panel_url')
                    ->label('Panel URL')
                    ->getStateUsing(fn($record) => $record->getFullPanelUrl())
                    ->url(fn($record) => $record->getFullPanelUrl())
                    ->openUrlInNewTab()
                    ->copyable(),
                Tables\Columns\TextColumn::make('ip')
                    ->searchable(),
                Tables\Columns\TextColumn::make('port')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->searchable(),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Category')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('brand.name')
                    ->label('Brand')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('country')
                    ->label('Country')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('sni')
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('reality')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('server_category_id')
                    ->label('Categories')
                    ->relationship('category', 'name'),
                SelectFilter::make('country')
                    ->label('Countries'),
                SelectFilter::make('panel_url')
                    ->label('Panel URL'),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListServers::route('/'),
            'create' => Pages\CreateServer::route('/create'),
            'view' => Pages\ViewServer::route('/{record}'),
            'edit' => Pages\EditServer::route('/{record}/edit'),
        ];
    }
}
