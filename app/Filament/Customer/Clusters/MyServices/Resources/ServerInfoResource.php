<?php

namespace App\Filament\Customer\Clusters\MyServices\Resources;

use App\Filament\Customer\Clusters\MyServices;
use App\Filament\Customer\Clusters\MyServices\Resources\ServerInfoResource\Pages;
use App\Filament\Customer\Clusters\MyServices\Resources\ServerInfoResource\RelationManagers;
use App\Models\ServerInfo;
use App\Models\DownloadableItem;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\Actions;
use Filament\Infolists\Components\Actions\Action;
use Filament\Infolists\Components\RepeatableEntry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Actions\ViewAction;

class ServerInfoResource extends Resource
{
    protected static ?string $model = ServerInfo::class;
    protected static ?string $navigationIcon = 'heroicon-o-information-circle';
    protected static ?string $navigationLabel = 'My Server Infos';
    protected static ?string $navigationGroup = 'Services';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('server.plans.orderItems.order', function ($q) {
                $q->where('customer_id', Auth::guard('customer')->id())
                  ->where('payment_status', 'paid');
            });
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Title')
                    ->searchable()
                    ->sortable()
                    ->color('primary'),

                TextColumn::make('remark')
                    ->label('Remark')
                    ->limit(30)
                    ->tooltip(fn (ServerInfo $record): string => $record->remark)
                    ->wrap()
                    ->color('gray'),

                TextColumn::make('tag')
                    ->label('Country')
                    ->formatStateUsing(function (?string $state): string {
                        if (preg_match('/^[A-Za-z]{2}$/', $state ?? '') !== 1) {
                            return '🌐';
                        }
                        $base  = 0x1F1E6; // Regional Indicator Symbol Letter A
                        $upper = strtoupper($state);
                        return
                            mb_chr($base + ord($upper[0]) - ord('A'), 'UTF-8')
                          . mb_chr($base + ord($upper[1]) - ord('A'), 'UTF-8');
                    })
                    ->tooltip(fn (?string $state): ?string =>
                        preg_match('/^[A-Za-z]{2}$/', $state ?? '') === 1
                            ? strtoupper($state)
                            : null
                    )
                    ->color('info')
                    ->sortable(),

                IconColumn::make('active')
                    ->boolean()
                    ->label('Active')
                    ->alignCenter()
                    ->sortable(),

                IconColumn::make('state')
                    ->boolean()
                    ->label('Server State')
                    ->alignCenter()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('state')->options([
                    'up'     => 'Online',
                    'down'   => 'Offline',
                    'paused' => 'Paused',
                ]),
            ])
            ->actions([
                ViewAction::make(),
            ])
            ->bulkActions([])
            ->headerActions([])
            ->emptyStateHeading('No Server Info Found')
            ->emptyStateDescription('You’ll see your server info here after you purchase.');
    }

    public static function infolist(Infolist $list): Infolist
    {
        return $list
            ->schema([
                Section::make('Server Information')
                    ->description('Details about the server assigned to your account.')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('title')
                            ->label('Title')
                            ->color('primary'),

                        TextEntry::make('remark')
                            ->label('Remark')
                            ->color('gray'),

                        TextEntry::make('tag')
                            ->label('Country')
                            ->state(fn (ServerInfo $record): string => (function (?string $code): string {
                                $code = strtoupper($code ?? '');
                                if (preg_match('/^[A-Z]{2}$/', $code) !== 1) {
                                    return '🌐';
                                }
                                $base = 0x1F1E6;
                                return
                                    mb_chr($base + ord($code[0]) - ord('A'), 'UTF-8')
                                  . mb_chr($base + ord($code[1]) - ord('A'), 'UTF-8');
                            })($record->tag))
                            ->color('info'),

                        IconEntry::make('active')
                            ->label('Active')
                            ->boolean(),

                        IconEntry::make('state')
                            ->label('Server State')
                            ->boolean(),
                    ])
                    ->columnSpanFull()
                    ->collapsible(),

                Actions::make([
                    Action::make('view_downloads')
                        ->label('View All Downloads')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->url(fn (ServerInfo $rec) => route(
                            'filament.customer.resources.downloadable-items.index',
                            ['server_id' => $rec->server_id]
                        ))
                        ->button()
                        ->color('primary'),
                ])->columnSpanFull(),

                Section::make('Available Downloads')
                    ->description('Files linked to this server (docs, configs, etc.)')
                    ->schema([
                        RepeatableEntry::make('downloads')
                            ->label(false)
                            ->schema([
                                TextEntry::make('file_url')
                                    ->label('File')
                                    ->formatStateUsing(fn (?string $state) => $state ?? 'No file URL')
                                    ->url(fn (?string $state) => filled($state) ? $state : null)
                                    ->openUrlInNewTab()
                                    ->copyable(),

                                TextEntry::make('expiration_time')
                                    ->label('Expires')
                                    ->dateTime(),
                            ])
                            ->default(fn (ServerInfo $record) =>
                                $record
                                    ->downloadableItems()
                                    ->latest()
                                    ->take(5)
                                    ->get()
                                    ->map(fn (DownloadableItem $d) => $d->only(['file_url', 'expiration_time']))
                                    ->toArray()
                            )
                            ->visible(fn (ServerInfo $record) =>
                                $record->downloadableItems()->exists()
                            ),

                    ])
                    ->visible(fn (ServerInfo $record) =>
                        (bool) $record->downloadableItems()->exists()
                    )
                    ->columnSpanFull(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\DownloadableItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServerInfos::route('/'),
            'view'  => Pages\ViewServerInfo::route('/{record}'),
        ];
    }
}
