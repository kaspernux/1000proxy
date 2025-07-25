<?php

namespace App\Filament\Customer\Clusters\MyServices\Resources\ServerInfoResource\RelationManagers;

use App\Models\DownloadableItem;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;


class DownloadableItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'downloadableItems';

    protected static ?string $title = 'Downloadable Files';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('file_url')
                    ->label('Download URL')
                    ->url(fn (DownloadableItem $record) => $record->file_url)
                    ->copyable()
                    ->openUrlInNewTab()
                    ->limit(60),

                Tables\Columns\TextColumn::make('download_limit')
                    ->label('Max Downloads')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('expiration_time')
                    ->label('Expires At')
                    ->dateTime()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Uploaded')
                    ->since()
                    ->sortable(),
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('download')
                        ->label('Download')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->url(fn (DownloadableItem $record) => $record->file_url)
                        ->openUrlInNewTab(),
                ]),
            ])
            ->emptyStateHeading('No files available')
            ->emptyStateDescription('This server has no downloadable documents or configurations yet.');
    }
}
