<?php

namespace App\Filament\Clusters\ServerManagement\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\ServerClient;
use App\Models\ServerPlan;
use App\Models\Server;
use App\Models\ServerInbound;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Clusters\ServerManagement;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Clusters\ServerManagement\Resources\ServerClientResource\Pages;
use App\Filament\Clusters\ServerManagement\Resources\ServerClientResource\RelationManagers;
use App\Services\XUIService;
use Filament\Tables\Actions\Action;

class ServerClientResource extends Resource
{
    use LivewireAlert;

    protected static ?string $model = ServerClient::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'PROXY SETTINGS';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'server_id';

    public static function getLabel(): string
    {
        return 'Clients';
    }


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('email')
                    ->required(),
                Forms\Components\TextInput::make('password'),
                Forms\Components\TextInput::make('flow')
                    ->nullable(),
                Forms\Components\TextInput::make('limitIp')
                    ->numeric(),
                Forms\Components\TextInput::make('totalGb')
                    ->numeric(),
                Forms\Components\DatePicker::make('expiryTime'),
                Forms\Components\TextInput::make('tgId')
                    ->nullable(),
                Forms\Components\TextInput::make('subId')
                    ->nullable(),
                Forms\Components\Toggle::make('enable')
                    ->required()
                    ->default(true),
                Forms\Components\TextInput::make('reset')
                    ->nullable(),
                Forms\Components\TextInput::make('qr_code_sub')
                    ->nullable(),
                Forms\Components\TextInput::make('qr_code_sub_json')
                    ->nullable(),
                Forms\Components\TextInput::make('qr_code_client')
                    ->nullable(),
                Forms\Components\Select::make('server_inbound_id')
                    ->relationship('inbound', 'remark'),
                Forms\Components\Select::make('plan_id')
                    ->relationship('plan', 'name')
                    ->nullable(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('inbound.remark')
                    ->label('Inbound'),
                Tables\Columns\TextColumn::make('email')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('plan.name')
                    ->label('Plan')
                    ->getStateUsing(function ($record) {
                        return ($record->plan_id && $record->plan)
                            ? $record->plan->name
                            : 'Generated From XUI Panel';
                    })
                    ->badge()
                    ->color(fn ($record) => ($record->plan_id && $record->plan) ? 'success' : 'gray')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('flow')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('limitIp')
                    ->sortable(),
                Tables\Columns\TextColumn::make('totalGb')
                    ->sortable(),
                Tables\Columns\TextColumn::make('expiryTime')
                    ->sortable(),
                Tables\Columns\IconColumn::make('enable')
                    ->boolean(),
                Tables\Columns\TextColumn::make('qr_code_client')
                    ->label('QR Link')
                    ->url(fn ($record) => $record->qr_code_client)
                    ->openUrlInNewTab()
                    ->limit(30),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('inbound')
                    ->relationship('inbound', 'remark'),
                Tables\Filters\SelectFilter::make('plan')
                    ->relationship('plan', 'name'),
                Tables\Filters\TrashedFilter::make(), // 👈 filter trashed/active/all
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
                Action::make('Sync Clients from XUI')
                    ->icon('heroicon-o-arrow-path')
                    ->label('Sync Clients')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->action(function () {
                        $servers = Server::all();

                        foreach ($servers as $server) {
                            try {
                                $xui = new XUIService($server->id);
                                $remoteInbounds = $xui->getInbounds();

                                foreach ($remoteInbounds as $inbound) {
                                    $localInbound = ServerInbound::firstOrCreate([
                                        'server_id' => $server->id,
                                        'port' => $inbound->port,
                                    ]);

                                    $clients = json_decode($inbound->settings)->clients ?? [];

                                    foreach ($clients as $client) {
                                        ServerClient::updateOrCreate([
                                            'subId' => $client->subId ?? null,
                                        ], [
                                            'server_inbound_id' => $localInbound->id,
                                            'email' => $client->email ?? '',
                                            'password' => $client->id ?? '',
                                            'flow' => $client->flow ?? '',
                                            'limitIp' => $client->limitIp ?? 0,
                                            'totalGb' => isset($client->totalGB) ? floor($client->totalGB / 1073741824) : 0,
                                            'expiryTime' => isset($client->expiryTime) ? now()->createFromTimestampMs($client->expiryTime) : null,
                                            'enable' => $client->enable ?? true,
                                            'reset' => $client->reset ?? null,
                                            'tgId' => $client->tgId ?? null,
                                            'plan_id' => $source === 'order' ? $serverPlan->id : null,
                                        ]);
                                    }
                                }
                            } catch (\Exception $e) {
                                \Log::error("Client sync failed for server ID {$server->id}: " . $e->getMessage());
                            }
                        }

                        \Filament\Notifications\Notification::make()
                            ->title('Success')
                            ->body('Clients synced successfully.')
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
            'index' => Pages\ListServerClients::route('/'),
            'create' => Pages\CreateServerClient::route('/create'),
            'view' => Pages\ViewServerClient::route('/{record}'),
            'edit' => Pages\EditServerClient::route('/{record}/edit'),
        ];
    }
}