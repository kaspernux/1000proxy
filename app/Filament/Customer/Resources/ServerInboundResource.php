<?php

namespace App\Filament\Customer\Resources;

use App\Models\ServerInbound;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Customer\Resources\ServerInboundResource\Pages;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\Grid;
use Illuminate\Support\Facades\Auth;

class ServerInboundResource extends Resource
{
    protected static ?string $model = ServerInbound::class;

    protected static ?string $navigationIcon = 'heroicon-o-code-bracket';
    protected static ?string $navigationLabel = 'My Proxy Inbounds';
    protected static ?string $navigationGroup = 'Services';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('clients', function ($query) {
                $query->where('email', 'LIKE', '%#ID ' . Auth::guard('customer')->id());
            });
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('port')
                    ->label('Port')
                    ->sortable()
                    ->copyable()
                    ->color('primary'),

                BadgeColumn::make('protocol')
                    ->label('Protocol')
                    ->colors([
                        'info' => 'http',
                        'primary' => 'vmess',
                        'success' => 'vless',
                        'warning' => 'trojan',
                    ])
                    ->searchable(),

                TextColumn::make('remark')
                    ->label('Remark')
                    ->tooltip(fn ($record) => $record->remark)
                    ->limit(25)
                    ->color('gray')
                    ->sortable()
                    ->wrap(),

                IconColumn::make('enable')
                    ->boolean()
                    ->label('Enabled')
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->alignCenter(),

                TextColumn::make('expiry_time')
                    ->label('Expires At')
                    ->dateTime('M d, Y H:i')
                    ->sortable()
                    ->since(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('protocol')
                    ->options([
                        'vmess' => 'VMess',
                        'vless' => 'VLess',
                        'trojan' => 'Trojan',
                        'http' => 'HTTP',
                    ])
                    ->searchable()
                    ->label('Protocol Filter'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->color('primary')->label('Details'),
            ])
            ->defaultSort('expiry_time', 'desc')
            ->emptyStateHeading('No Server Inbounds Found')
            ->emptyStateDescription('Once you subscribe to a plan, your server inbounds will appear here.');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make('Inbound Overview')
                ->description('Details about your server inbound')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            TextEntry::make('port')->label('Port')->color('primary'),
                            TextEntry::make('protocol')->label('Protocol')->badge(),
                            TextEntry::make('remark')->label('Remark')->markdown(),
                            IconEntry::make('enable')->boolean()->label('Enabled'),
                            TextEntry::make('expiry_time')->label('Expires At')->dateTime('M d, Y H:i'),
                        ]),
                ])
                ->columns(1)
                ->collapsible(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServerInbounds::route('/'),
            'view' => Pages\ViewServerInbound::route('/{record}'),
        ];
    }
}
