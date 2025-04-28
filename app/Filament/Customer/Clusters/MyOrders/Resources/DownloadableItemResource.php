<?php

namespace App\Filament\Customer\Clusters\MyOrders\Resources;

use App\Filament\Customer\Clusters\MyOrders;
use App\Filament\Customer\Clusters\MyOrders\Resources\DownloadableItemResource\Pages;
use App\Filament\Customer\Clusters\MyOrders\Resources\DownloadableItemResource\RelationManagers;
use App\Models\DownloadableItem;
use App\Models\Server;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;


use Illuminate\Support\Facades\Auth;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Tabs;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;


class DownloadableItemResource extends Resource
{
    protected static ?string $model             = DownloadableItem::class;
    protected static ?string $navigationIcon    = 'heroicon-o-folder-arrow-down';
    protected static ?string $navigationGroup   = 'My Orders';
    protected static ?string $navigationLabel   = 'Downloads';
    protected static ?int    $navigationSort    = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Placeholder::make('info')
                    ->content('Any files included with your purchased services will appear here.'),
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
                    Action::make('download')
                        ->label('Download')
                        ->icon('heroicon-o-cloud-arrow-down')
                        ->url(fn (DownloadableItem $record) => $record->file_url)
                        ->openUrlInNewTab(),
                ]),
            ])
            ->emptyStateHeading('No Downloads Available')
            ->emptyStateDescription('Files included with your purchased services will show up here.');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
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
                                    TextEntry::make('file_url')
                                        ->label('Download URL')
                                        ->url(fn (DownloadableItem $record) => $record->file_url)
                                        ->openUrlInNewTab()
                                        ->copyable(),
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
                        ->icon('heroicon-m-cloud-arrow-down')
                        ->schema([
                            Section::make()
                                ->schema([
                                    TextEntry::make('file_url')
                                        ->label('Download File')
                                        ->url(fn (DownloadableItem $record) => $record->file_url)
                                        ->openUrlInNewTab()
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
            'index' => Pages\ListDownloadableItems::route('/'),
            'view'  => Pages\ViewDownloadableItem::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $customerId = Auth::guard('customer')->id();

        return parent::getEloquentQuery()
            ->whereHas('server.inbounds.clients', function (Builder $q) use ($customerId) {
                $q->where('email', 'like', "%#ID {$customerId}");
            })
            ->orderByDesc('created_at');
    }
}

