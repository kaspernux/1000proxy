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
use App\Filament\Clusters\ServerManagement\Resources\ServerClientResource\Pages;
use App\Filament\Clusters\ServerManagement\Resources\ServerClientResource\RelationManagers;
use App\Services\XUIService;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\Storage;
use Filament\Forms\Components\View as ViewComponent;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Image;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Tabs;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;

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


    /* public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('email')->required(),
            TextInput::make('password'),
            TextInput::make('flow')->nullable(),
            TextInput::make('limitIp')->numeric(),
            TextInput::make('totalGb')->numeric(),
            Forms\Components\DatePicker::make('expiryTime'),
            TextInput::make('tgId')->nullable(),
            TextInput::make('subId')->nullable(),
            Toggle::make('enable')->required()->default(true),
            TextInput::make('reset')->nullable(),

            Select::make('server_inbound_id')->relationship('inbound', 'remark'),
            Select::make('plan_id')->relationship('plan', 'name')->nullable(),
        ]);
    } */

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('inbound.remark')->label('Inbound'),
            Tables\Columns\TextColumn::make('email')->sortable()->searchable(),
            TextColumn::make('plan.name')
                ->label('Plan')
                ->getStateUsing(fn ($record) => $record->plan_id && $record->plan ? $record->plan->name : 'Generated From XUI Panel')
                ->badge()
                ->color(fn ($record) => $record->plan_id && $record->plan ? 'success' : 'gray')
                ->sortable()
                ->searchable(),
            Tables\Columns\TextColumn::make('flow')->sortable()->searchable(),
            Tables\Columns\TextColumn::make('limitIp')->sortable(),
            Tables\Columns\TextColumn::make('totalGb')->sortable(),
            Tables\Columns\TextColumn::make('expiryTime')->sortable(),
            Tables\Columns\IconColumn::make('enable')->boolean(),

            // ✅ QR Codes with image rendering
            ImageColumn::make('qr_code_client')
                ->label('Client QR')
                ->disk('public')
                ->tooltip('Click to download Client QR')
                ->url(fn ($record) => Storage::disk('public')->url($record->qr_code_client))
                ->openUrlInNewTab()
                ->height(60),

            ImageColumn::make('qr_code_sub')
                ->label('Sub QR')
                ->disk('public')
                ->tooltip('Click to download Sub QR')
                ->url(fn ($record) => Storage::disk('public')->url($record->qr_code_sub))
                ->openUrlInNewTab()
                ->height(60),

            ImageColumn::make('qr_code_sub_json')
                ->label('JSON QR')
                ->disk('public')
                ->tooltip('Click to download JSON QR')
                ->url(fn ($record) => Storage::disk('public')->url($record->qr_code_sub_json))
                ->openUrlInNewTab()
                ->height(60),
        ])
        ->filters([
            Tables\Filters\SelectFilter::make('inbound')->relationship('inbound', 'remark'),
            Tables\Filters\SelectFilter::make('plan')->relationship('plan', 'name'),
            Tables\Filters\TrashedFilter::make(),
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
                                    ServerClient::fromRemoteClient((array) $client, $localInbound->id);
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
        return [];
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

    // ✅ Infolist for view page
    public static function infolist(Infolist $infolist): Infolist
{
    return $infolist->schema([
        Tabs::make('Client Details')
            ->persistTab()
            ->tabs([
                Tabs\Tab::make('Profile')
                    ->icon('heroicon-m-user')
                    ->schema([
                        Section::make('🔐 Client Information')
                            ->description('Details about this proxy client’s identity and usage limits.')
                            ->columns([
                                'sm' => 1,
                                'md' => 2,
                                'xl' => 3,
                            ])
                            ->schema([
                                TextEntry::make('email')->label('Client Email')->color('primary'),
                                TextEntry::make('password')->label('UUID / Password')->color('primary'),
                                TextEntry::make('subId')->label('Subscription ID')->color('primary'),
                                TextEntry::make('flow')->label('Flow')->color('primary'),
                                TextEntry::make('limitIp')->label('IP Limit')->color('primary'),
                                TextEntry::make('totalGb')->label('Total GB')->color('primary'),
                                TextEntry::make('expiryTime')->label('Expires At')->dateTime()->color('primary'),
                                TextEntry::make('tgId')->label('Telegram ID')->default('—')->color('primary'),
                                IconEntry::make('enable')->label('Enabled')->boolean(),
                                TextEntry::make('reset')->label('Reset Count')->default(0)->color('primary'),
                            ]),
                    ]),

                Tabs\Tab::make('Server')
                    ->icon('heroicon-m-server')
                    ->schema([
                        Section::make('📡 Server Configuration')
                            ->description('Details about the proxy server and plan used.')
                            ->columns(2)
                            ->schema([
                                TextEntry::make('inbound.remark')->label('Inbound Remark')->color('primary'),
                                TextEntry::make('plan.name')->label('Plan Name')->default('N/A')->color('primary'),
                            ]),
                    ]),

                Tabs\Tab::make('QR Codes')
                    ->icon('heroicon-m-qr-code')
                    ->schema([
                        Section::make('📲 Client QR Codes')
                            ->description('Scan or download QR codes to quickly configure supported proxy clients.')
                            ->columns([
                                'default' => 1,
                                'sm' => 2,
                                'lg' => 3,
                            ])
                            ->schema([
                                ImageEntry::make('qr_code_client')
                                    ->label('Client QR')
                                    ->disk('public')
                                    ->tooltip('Click to open full-size')
                                    ->openUrlInNewTab()
                                    ->visible(fn ($record) => filled($record->qr_code_client)),

                                ImageEntry::make('qr_code_sub')
                                    ->label('Subscription QR')
                                    ->disk('public')
                                    ->tooltip('Click to open full-size')
                                    ->openUrlInNewTab()
                                    ->visible(fn ($record) => filled($record->qr_code_sub)),

                                ImageEntry::make('qr_code_sub_json')
                                    ->label('JSON Subscription QR')
                                    ->disk('public')
                                    ->tooltip('Click to open full-size')
                                    ->openUrlInNewTab()
                                    ->visible(fn ($record) => filled($record->qr_code_sub_json)),
                            ]),
                    ]),
            ])
            ->contained(true)
            ->columnSpanFull(),
    ]);
}

}
