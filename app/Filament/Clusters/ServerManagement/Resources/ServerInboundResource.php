<?php

namespace App\Filament\Clusters\ServerManagement\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\ServerInbound;
use Filament\Resources\Resource;
use App\Services\XUIService;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Split;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Facades\Redirect;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\SelectFilter;
use App\Filament\Clusters\ServerManagement;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use App\Filament\Clusters\ServerManagement\Resources\ServerInboundResource\Pages;
use Filament\Tables\Actions\Action;
use App\Models\Server;
use Filament\Tables\Filters\Filter;

class ServerInboundResource extends Resource
{
    use LivewireAlert;

    protected static ?string $model = ServerInbound::class;

    protected static ?string $navigationIcon = 'heroicon-o-code-bracket';

    protected static ?string $navigationGroup = 'PROXY SETTINGS';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'server_id';

    public static function getLabel(): string
    {
        return 'Inbounds';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Group::make([
                    Section::make('Server Details')
                        ->schema([
                            Split::make([
                                Section::make([
                                    Select::make('server_id')
                                        ->relationship('server', 'name')
                                        ->required()
                                        ->searchable()
                                        ->preload()
                                        ->columnSpan(1),
                                    TextInput::make('remark')
                                        ->required()
                                        ->maxLength(255)
                                        ->columnSpanFull(),
                                ]),
                                Section::make([
                                    Toggle::make('enable')
                                        ->required()
                                        ->default(true),
                                    Forms\Components\Textarea::make('sniffing')
                                        ->label('Sniffing (JSON)')
                                        ->json(),
                                ])->grow(false),
                            ])->from('md'),
                        ]),
                    Section::make('Connection Details')
                        ->schema([
                            TextInput::make('listen')
                                ->maxLength(255),
                            TextInput::make('port')
                                ->required()
                                ->numeric(),
                        ])->columns(2),
                    Section::make('Protocol and Settings')
                        ->schema([
                            Select::make('protocol')
                                ->options([
                                    'vless' => 'VLESS',
                                    'vmess' => 'VMESS',
                                    'trojan' => 'TROJAN',
                                    // Add more protocol types as needed
                                ])
                                ->required()
                                ->maxWidth(255),
                            TextInput::make('userId')
                                        ->numeric()
                                        ->columnSpan(1),
                            Forms\Components\Textarea::make('settings')
                                ->label('Settings (JSON)')
                                ->json(),
                            Forms\Components\Textarea::make('streamSettings')
                                ->label('Stream Settings (JSON)')
                                ->json(),
                        ])->columns(2),
                ])->columnSpan(2),
                Group::make([
                    Section::make('Additional Info')
                        ->schema([
                            TextInput::make('up')
                                ->numeric()
                                ->default(0),
                            TextInput::make('down')
                                ->numeric()
                                ->default(0),
                            TextInput::make('total')
                                ->numeric()
                                ->default(0),
                            DatePicker::make('expiryTime'),
                        ])->columns(2),
                ])->columnSpan(1),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\IconColumn::make('enable')
                    ->label('Enabled')
                    ->boolean(),
                Tables\Columns\TextColumn::make('server.name')
                    ->label('Server')
                    ->searchable(),
                Tables\Columns\TextColumn::make('remark')
                    ->label('Remark')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('protocol')
                    ->colors([
                        'primary' => 'vmess',
                        'info' => 'vless',
                        'warning' => 'trojan',
                    ])
                    ->label('Protocol'),
                Tables\Columns\TextColumn::make('port')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total')
                    ->label('Total Clients')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('up')
                    ->sortable(),
                Tables\Columns\TextColumn::make('down')
                    ->sortable(),            
                Tables\Columns\TextColumn::make('expiryTime')
                    ->dateTime()
                    ->sortable(),
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
                SelectFilter::make('server')
                    ->label('Servers')
                    ->relationship('server', 'name'),
                SelectFilter::make('userId')
                    ->label('Proxy User'),
                SelectFilter::make('remark')
                    ->label('Remark'),
                SelectFilter::make('port')
                    ->label('Port'),
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
            ])
            ->headerActions([
                Action::make('Sync Inbounds from XUI')
                    ->icon('heroicon-o-arrow-path')
                    ->requiresConfirmation()
                    ->label('Sync Inbounds')
                    ->color('primary')
                    ->action(function () {
                        $servers = \App\Models\Server::all();

                        foreach ($servers as $server) {
                            try {
                                $xui = new \App\Services\XUIService($server->id);
                                $remoteInbounds = $xui->getInbounds(); // make sure this is public

                                foreach ($remoteInbounds as $inbound) {
                                    \App\Models\ServerInbound::fromRemoteInbound($inbound, $server->id);
                                }
                            } catch (\Exception $e) {
                                \Log::error("Inbound sync failed for server ID {$server->id}: " . $e->getMessage());
                            }
                        }

                        \Filament\Notifications\Notification::make()
                            ->title('Success')
                            ->body('Inbounds synced successfully.')
                            ->success()
                            ->send();
                    }),
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
            'index' => Pages\ListServerInbounds::route('/'),
            'create' => Pages\CreateServerInbound::route('/create'),
            'view' => Pages\ViewServerInbound::route('/{record}'),
            'edit' => Pages\EditServerInbound::route('/{record}/edit'),
        ];
    }
}