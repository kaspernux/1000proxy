<?php

namespace App\Filament\Clusters\ProxyShop\Resources;

use App\Filament\Clusters\ProxyShop;
use App\Filament\Clusters\ProxyShop\Resources\DownloadableItemResource\Pages;
use App\Filament\Clusters\ProxyShop\Resources\DownloadableItemResource\RelationManagers;
use App\Models\DownloadableItem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Schemas\Components\Section;

use Filament\Schemas\Components\Group;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\ToggleButtons;
use Filament\Support\RawJs;
use Illuminate\Support\Str;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\SelectColumn;
use GuzzleHttp\Client;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Fieldset;
use Filament\Forms\Components\Split;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Infolists\Infolist;
use Filament\Schemas\Components\Tabs;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\LinkEntry;
use Filament\Actions\CreateAction;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use BackedEnum;



class DownloadableItemResource extends Resource
{
    protected static ?string $model           = DownloadableItem::class;
    protected static ?string $cluster         = ProxyShop::class;
    
    public static function canAccess(): bool
    {
        $user = auth()->user();
        return (bool) ($user?->isAdmin() || $user?->isManager());
    }
    protected static ?string $navigationLabel = 'Downloadable Files';
    protected static BackedEnum|string|null $navigationIcon  = 'heroicon-o-folder-arrow-down';

    public static function form(Schema $schema): Schema
    {
        return $schema
            // Make 1 column on small screens, 2 on large
            ->columns([
                'sm' => 1,
                'lg' => 2,
            ])
            ->schema([
                Grid::make(2)
                    ->schema([
                        // ── LEFT COLUMN ──
                        Fieldset::make('General Information')
                            ->label('Select which server this file belongs to, and give it a clear label.')
                            ->schema([
                                Select::make('server_id')
                                    ->label('Server')
                                    ->relationship('server', 'name')
                                    ->searchable()
                                    ->required()
                                    ->helperText('Pick the server to which this file applies.')
                                    ->hint('Must match an existing server record.')
                                    ->hintIcon('heroicon-s-question-mark-circle'),

                                TextInput::make('name')
                                    ->label('File Label')
                                    ->required()
                                    ->maxLength(255)
                                    ->helperText('A friendly name, e.g. “Configuration Pack #1”.')
                                    ->placeholder('Enter a descriptive label…'),
                            ])
                            ->columns(1)
                            ->columnSpan(1),

                        // ── RIGHT COLUMN ──
                        Fieldset::make('Upload & Availability')
                            ->label('Upload your file and control how/when customers can download it.')
                            ->schema([
                                FileUpload::make('file_url')
                                    ->label('File Upload')
                                    ->disk('public')
                                    ->directory('downloadables')
                                    ->preserveFilenames()
                                    ->required()
                                    ->helperText('Max file size: 10MB. Allowed types: zip, pdf, txt.')
                                    ->acceptedFileTypes(['application/zip', 'application/pdf', 'text/plain'])
                                    ->maxSize(10240 /* KB */),

                                Grid::make(['default' => 1, 'sm' => 2])
                                    ->schema([
                                        TextInput::make('download_limit')
                                            ->label('Max Downloads')
                                            ->numeric()
                                            ->minValue(0)
                                            ->helperText('0 = unlimited'),
                                        DatePicker::make('expiration_time')
                                            ->label('Expiration Date')
                                            ->helperText('After this date, the file will no longer be downloadable.')
                                            ->displayFormat('Y-m-d')
                                            ->minDate(now()),
                                    ]),

                                ToggleButtons::make('is_active')
                                    ->label('Active')
                                    ->options([
                                        1 => 'Enabled',
                                        0 => 'Disabled',
                                    ])
                                    ->default(1)
                                    ->helperText('Disable to hide this file from customers.'),
                            ])
                            ->columns(1)
                            ->columnSpan(1),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('file_url')
                    ->label('File URL')
                    ->url(fn (DownloadableItem $record) => $record->file_url)
                    ->openUrlInNewTab()
                    ->copyable(),
                TextColumn::make('download_limit')
                    ->label('Max Downloads')
                    ->sortable(),
                TextColumn::make('expiration_time')
                    ->label('Expires At')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Added On')
                    ->since()
                    ->sortable(),
            ])
            ->actions([
                 ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                    DeleteAction::make(),
                    Action::make('download')
                        ->label('Download')
                        ->icon('heroicon-o-cloud-arrow-down')
                        ->url(fn (DownloadableItem $record) => $record->file_url)
                        ->openUrlInNewTab(),
                ]),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Tabs::make('File Details')->tabs([
                    Tabs\Tab::make('Overview')
                        ->icon('heroicon-o-document-text')
                        ->schema([
                            Section::make('File Information')
                                ->columns(2)
                                ->schema([
                                    TextEntry::make('id')
                                        ->label('ID'),
                                    TextEntry::make('server.name')
                                        ->label('Server'),
                                    TextEntry::make('name')
                                        ->label('Label'),
                                    TextEntry::make('download_limit')
                                        ->label('Max Downloads'),
                                    TextEntry::make('expiration_time')
                                        ->label('Expires At')
                                        ->dateTime(),
                                    TextEntry::make('created_at')
                                        ->label('Added On')
                                        ->since(),
                                ]),
                        ]),

                    Tabs\Tab::make('Download')
                        ->icon('heroicon-o-cloud-arrow-down')
                        ->schema([
                            Section::make()
                                ->schema([
                                    TextEntry::make('file_url')
                                        ->label('Download File')
                                        ->url(fn (DownloadableItem $record) => asset("storage/{$record->file_url}"))
                                        ->openUrlInNewTab()
                                        ->copyable()
                                        // optionally add a hint with an icon:
                                        ->hint('Click to download')
                                        ->hintIcon('heroicon-o-cloud-arrow-down'),
                                ]),
                        ]),
                ])->columnSpanFull(),
            ]);
    }


    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListDownloadableItems::route('/'),
            'create' => Pages\CreateDownloadableItem::route('/create'),
            'view'   => Pages\ViewDownloadableItem::route('/{record}'),
            'edit'   => Pages\EditDownloadableItem::route('/{record}/edit'),
        ];
    }
}
