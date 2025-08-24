<?php

namespace App\Filament\Clusters\ProxyShop\Resources;

use App\Models\TelegramTemplate;
use Filament\Forms;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;
use BackedEnum;
use UnitEnum;
use App\Filament\Clusters\ProxyShop\Resources\TelegramTemplateResource\Pages;

class TelegramTemplateResource extends Resource
{
    protected static ?string $model = TelegramTemplate::class;
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-document-text';
    protected static UnitEnum|string|null $navigationGroup = 'Telegram';
    protected static ?string $navigationLabel = 'Templates';
    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            TextInput::make('title')->required()->maxLength(120),
            Textarea::make('content')->rows(10)->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('id')->sortable(),
            TextColumn::make('title')->searchable()->wrap(),
            TextColumn::make('created_at')->dateTime()->since(),
        ])->actions([
            \Filament\Actions\EditAction::make(),
            \Filament\Actions\DeleteAction::make(),
        ])->bulkActions([
            \Filament\Actions\DeleteBulkAction::make(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTelegramTemplates::route('/'),
            'create' => Pages\CreateTelegramTemplate::route('/create'),
            'edit' => Pages\EditTelegramTemplate::route('/{record}/edit'),
        ];
    }
}
