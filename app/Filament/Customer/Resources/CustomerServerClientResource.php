<?php

namespace App\Filament\Customer\Resources;

use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\ServerClient;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Tabs;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use App\Filament\Customer\Resources\CustomerServerClientResource\Pages;

class CustomerServerClientResource extends Resource
{
    protected static ?string $model = ServerClient::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'My Proxy Clients';
    protected static ?string $navigationGroup = 'Configurations';
    protected static ?string $recordTitleAttribute = 'email';

    public static function getEloquentQuery(): Builder
    {
        $customerId = auth('customer')->id();

        return parent::getEloquentQuery()
            ->where('email', 'LIKE', "%#ID {$customerId}");
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\IconColumn::make('enable')->boolean(),
            TextColumn::make('plan.name')
                ->label('Plan')
                ->badge()
                ->color(fn ($record) => $record->plan_id && $record->plan ? 'success' : 'gray')
                ->getStateUsing(fn ($record) => $record->plan_id && $record->plan ? $record->plan->name : 'Generated From XUI Panel')
                ->sortable()
                ->searchable(),
            ImageColumn::make('qr_code_client')
                ->label('Client QR')
                ->disk('public')
                ->tooltip('Click to download')
                ->url(fn ($record) => Storage::disk('public')->url($record->qr_code_client))
                ->openUrlInNewTab()
                ->height(60),

            ImageColumn::make('qr_code_sub')
                ->label('Sub QR')
                ->disk('public')
                ->tooltip('Click to download')
                ->url(fn ($record) => Storage::disk('public')->url($record->qr_code_sub))
                ->openUrlInNewTab()
                ->height(60),

            ImageColumn::make('qr_code_sub_json')
                ->label('JSON QR')
                ->disk('public')
                ->tooltip('Click to download')
                ->url(fn ($record) => Storage::disk('public')->url($record->qr_code_sub_json))
                ->openUrlInNewTab()
                ->height(60),
            TextColumn::make('total_gb_bytes')->sortable()->formatStateUsing(fn ($state) => $state ? round($state / 1073741824, 2) . ' GB' : '0 GB'),
            TextColumn::make('limit_ip')->sortable(),
            TextColumn::make('expiry_time')->sortable(),
        ])
        ->actions([
            Tables\Actions\ViewAction::make(), // ✅ View only
        ])
        ->filters([
            Tables\Filters\SelectFilter::make('plan')->relationship('plan', 'name'),
        ])
        ->bulkActions([]) // No bulk actions
        ->headerActions([]); // No "sync clients" button
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomerServerClients::route('/'),
            'view' => Pages\ViewCustomerServerClient::route('/{record}'),
        ];
    }

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
                                ->description('Details about your proxy client account.')
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
                                    TextEntry::make('limit_ip')->label('IP Limit')->color('primary'),
                                    TextEntry::make('total_gb_bytes')->label('Total GB')->color('primary')->formatStateUsing(fn ($state) => $state ? round($state / 1073741824, 2) . ' GB' : '0 GB'),
                                    TextEntry::make('expiry_time')->label('Expires At')->dateTime()->color('primary'),
                                    TextEntry::make('telegram_chat_id')->label('Telegram ID')->default('—')->color('primary'),
                                    IconEntry::make('enable')->label('Enabled')->boolean(),
                                    TextEntry::make('reset')->label('Reset Count')->default(0)->color('primary'),
                                ]),
                        ]),

                    Tabs\Tab::make('Server')
                        ->icon('heroicon-m-server')
                        ->schema([
                            Section::make('📡 Server Details')
                                ->description('Server and plan details associated with this client.')
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
                                ->description('Scan or download your proxy configuration QR codes.')
                                ->columns([
                                    'default' => 1,
                                    'sm' => 2,
                                    'lg' => 3,
                                ])
                                ->schema([
                                    ImageEntry::make('qr_code_client')
                                        ->label('Client QR')
                                        ->disk('public')
                                        ->tooltip('Click to view full size')
                                        ->openUrlInNewTab()
                                        ->visible(fn ($record) => filled($record->qr_code_client)),

                                    ImageEntry::make('qr_code_sub')
                                        ->label('Subscription QR')
                                        ->disk('public')
                                        ->tooltip('Click to view full size')
                                        ->openUrlInNewTab()
                                        ->visible(fn ($record) => filled($record->qr_code_sub)),

                                    ImageEntry::make('qr_code_sub_json')
                                        ->label('JSON Subscription QR')
                                        ->disk('public')
                                        ->tooltip('Click to view full size')
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
