<?php

namespace App\Filament\Clusters\ServerManagement\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use App\Models\ServerPlan;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Filament\Resources\Resource;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\ImageColumn;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Filters\SelectFilter;
use App\Filament\Clusters\ServerManagement;
use Filament\Forms\Components\MarkdownEditor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use App\Filament\Clusters\ServerManagement\Resources\ServerPlanResource\Pages;

class ServerPlanResource extends Resource
{
    protected static ?string $model = ServerPlan::class;

    protected static ?string $navigationIcon = 'heroicon-o-fire';

    protected static ?int $navigationSort = 5;

    protected static ?string $cluster = ServerManagement::class;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getLabel(): string
    {
        return 'Plans';
    }

    public static function form(Form $form): Form
    {
        return $form
        ->schema([
            Group::make()->schema([
                Section::make('🏷️ Plan Identity & Basic Info')->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (string $operation, $state, $set) {
                            if ($operation === 'create') {
                                $set('slug', Str::slug($state));
                            }
                        })
                        ->columnSpan(1)
                        ->helperText('Descriptive plan name (e.g., "Gaming Pro - 30 Days")'),

                    TextInput::make('slug')
                        ->required()
                        ->disabled()
                        ->unique(ServerPlan::class, 'slug', ignoreRecord: true)
                        ->columnSpan(1)
                        ->helperText('Auto-generated URL-friendly identifier'),

                    Select::make('server_id')
                        ->relationship('server', 'name')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->columnSpan(1)
                        ->helperText('Server this plan belongs to'),

                    Select::make('server_brand_id')
                        ->relationship('brand', 'name')
                        ->searchable()
                        ->preload()
                        ->columnSpan(1)
                        ->helperText('Associated brand for filtering'),

                    Select::make('server_category_id')
                        ->relationship('category', 'name')
                        ->searchable()
                        ->preload()
                        ->columnSpan(1)
                        ->helperText('Category for plan organization'),

                    Select::make('type')
                        ->options([
                            'basic' => 'Basic',
                            'standard' => 'Standard',
                            'premium' => 'Premium',
                            'enterprise' => 'Enterprise',
                            'trial' => 'Trial',
                            'custom' => 'Custom',
                        ])
                        ->default('standard')
                        ->required()
                        ->columnSpan(1)
                        ->helperText('Plan tier/type'),
                ])->columns(2),

                Section::make('💰 Pricing & Billing Configuration')->schema([
                    TextInput::make('price')
                        ->required()
                        ->numeric()
                        ->prefix('$')
                        ->minValue(0)
                        ->step('0.01')
                        ->columnSpan(1)
                        ->helperText('Plan price in USD'),

                    TextInput::make('setup_fee')
                        ->label('Setup Fee')
                        ->numeric()
                        ->prefix('$')
                        ->default(0)
                        ->minValue(0)
                        ->step('0.01')
                        ->columnSpan(1)
                        ->helperText('One-time setup fee (if any)'),

                    TextInput::make('days')
                        ->label('Validity Period (Days)')
                        ->required()
                        ->numeric()
                        ->default(30)
                        ->suffix('days')
                        ->minValue(1)
                        ->columnSpan(1)
                        ->helperText('How long the plan remains active'),

                    TextInput::make('trial_days')
                        ->label('Trial Days')
                        ->numeric()
                        ->default(0)
                        ->minValue(0)
                        ->columnSpan(1)
                        ->helperText('Free trial period (0 = no trial)'),

                    Toggle::make('renewable')
                        ->default(true)
                        ->columnSpan(1)
                        ->helperText('Can customers renew this plan?'),

                    Toggle::make('on_sale')
                        ->columnSpan(1)
                        ->helperText('Mark plan as on sale'),
                ])->columns(2),
            ])->columnSpan(2),

            Group::make()->schema([
                Section::make('📊 Resources & Performance')->schema([
                    TextInput::make('volume')
                        ->label('Data Volume (GB)')
                        ->required()
                        ->numeric()
                        ->default(500)
                        ->suffix('GB')
                        ->minValue(1)
                        ->helperText('Total data allowance in GB'),

                    TextInput::make('data_limit_gb')
                        ->label('Hard Data Limit (GB)')
                        ->numeric()
                        ->suffix('GB')
                        ->helperText('Absolute data limit before service stops'),

                    TextInput::make('bandwidth_mbps')
                        ->label('Bandwidth (Mbps)')
                        ->numeric()
                        ->suffix('Mbps')
                        ->helperText('Maximum bandwidth allocation'),

                    TextInput::make('concurrent_connections')
                        ->label('Max Concurrent Connections')
                        ->numeric()
                        ->default(1)
                        ->minValue(1)
                        ->helperText('Maximum simultaneous connections'),

                    TextInput::make('capacity')
                        ->label('User Capacity')
                        ->default(1)
                        ->required()
                        ->numeric()
                        ->minValue(1)
                        ->helperText('Maximum users for this plan'),

                    Toggle::make('supports_ipv6')
                        ->label('IPv6 Support')
                        ->default(false)
                        ->helperText('IPv6 connectivity available'),
                ]),

                Section::make('⚙️ Advanced Configuration')->schema([
                    Select::make('preferred_inbound_id')
                        ->relationship('preferredInbound', 'remark')
                        ->searchable()
                        ->preload()
                        ->helperText('Default inbound for auto-provisioning'),

                    Select::make('protocol')
                        ->options([
                            'vmess' => 'VMess',
                            'vless' => 'VLESS',
                            'trojan' => 'Trojan',
                            'shadowsocks' => 'Shadowsocks',
                            'wireguard' => 'WireGuard',
                        ])
                        ->helperText('Primary protocol type'),

                    TextInput::make('max_clients')
                        ->label('Max Clients')
                        ->numeric()
                        ->default(100)
                        ->minValue(1)
                        ->helperText('Maximum clients that can be created'),

                    TextInput::make('current_clients')
                        ->label('Current Clients')
                        ->numeric()
                        ->default(0)
                        ->disabled()
                        ->helperText('Currently active clients (read-only)'),

                    Toggle::make('auto_provision')
                        ->label('Auto Provisioning')
                        ->default(false)
                        ->helperText('Automatically provision clients upon purchase'),
                ]),
            ])->columnSpan(1),

            Group::make()->schema([
                Section::make('🌍 Location & Filtering')->schema([
                    TextInput::make('country_code')
                        ->label('Country Code')
                        ->maxLength(2)
                        ->placeholder('US')
                        ->helperText('ISO 2-letter country code'),

                    TextInput::make('region')
                        ->label('Region/State')
                        ->maxLength(255)
                        ->helperText('State, province, or region'),

                    TextInput::make('popularity_score')
                        ->label('Popularity Score')
                        ->numeric()
                        ->default(0)
                        ->helperText('Used for sorting popular plans'),

                    Select::make('server_status')
                        ->options([
                            'online' => 'Online',
                            'offline' => 'Offline',
                            'maintenance' => 'Maintenance',
                            'limited' => 'Limited',
                        ])
                        ->default('online')
                        ->helperText('Current server status'),
                ])->columns(2),

                Section::make('🎛️ Status Controls')->schema([
                    Toggle::make('is_active')
                        ->label('Active')
                        ->default(true)
                        ->helperText('Plan is available for purchase'),

                    Toggle::make('is_featured')
                        ->label('Featured')
                        ->default(false)
                        ->helperText('Highlight this plan'),

                    Toggle::make('in_stock')
                        ->label('In Stock')
                        ->default(true)
                        ->helperText('Plan is currently available'),
                ])->columns(3),
            ])->columnSpanFull(),

            Group::make()->schema([
                Section::make('📝 Content & Media')->schema([
                    FileUpload::make('product_image')
                        ->label('Plan Image')
                        ->image()
                        ->disk('public')
                        ->directory('plan-images')
                        ->columnSpanFull()
                        ->helperText('Upload an image for this plan'),

                    MarkdownEditor::make('description')
                        ->label('Plan Description')
                        ->columnSpanFull()
                        ->helperText('Detailed description of the plan features'),
                ]),
            ])->columnSpanFull(),
        ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Plan Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('server.name')
                    ->label('Server')
                    ->sortable()
                    ->searchable()
                    ->badge(),
                TextColumn::make('price')
                    ->label('Price')
                    ->money('USD')
                    ->sortable()
                    ->color('success'),
                TextColumn::make('original_price')
                    ->label('Original Price')
                    ->money('USD')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('billing_cycle')
                    ->label('Billing')
                    ->badge()
                    ->colors([
                        'primary' => 'monthly',
                        'success' => 'annually',
                        'warning' => 'weekly',
                        'info' => 'daily',
                    ]),
                TextColumn::make('volume')
                    ->label('Data')
                    ->formatStateUsing(fn ($state, $record) => $record->unlimited_traffic ? 'Unlimited' : $state . ' GB')
                    ->sortable()
                    ->color(fn ($record) => $record->unlimited_traffic ? 'success' : 'primary'),
                TextColumn::make('days')
                    ->label('Duration')
                    ->formatStateUsing(fn ($state) => $state . ' days')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('capacity')
                    ->label('Max Connections')
                    ->numeric()
                    ->sortable()
                    ->alignCenter(),
                TextColumn::make('supported_protocols')
                    ->label('Protocols')
                    ->formatStateUsing(fn ($state) => is_array($state) ? implode(', ', array_map('strtoupper', $state)) : '—')
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                IconColumn::make('is_featured')
                    ->label('Featured')
                    ->boolean()
                    ->trueIcon('heroicon-o-star')
                    ->falseIcon('heroicon-o-star')
                    ->trueColor('warning')
                    ->falseColor('gray'),
                IconColumn::make('is_popular')
                    ->label('Popular')
                    ->boolean()
                    ->trueIcon('heroicon-o-fire')
                    ->falseIcon('heroicon-o-fire')
                    ->trueColor('danger')
                    ->falseColor('gray'),
                TextColumn::make('visibility')
                    ->badge()
                    ->colors([
                        'success' => 'public',
                        'warning' => 'private',
                        'danger' => 'hidden',
                    ]),
                TextColumn::make('total_sales')
                    ->label('Sales')
                    ->getStateUsing(fn ($record) => $record->orders()->count())
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('server_id')
                    ->label('Server')
                    ->relationship('server', 'name')
                    ->multiple()
                    ->preload(),
                SelectFilter::make('billing_cycle')
                    ->options([
                        'hourly' => 'Hourly',
                        'daily' => 'Daily',
                        'weekly' => 'Weekly',
                        'monthly' => 'Monthly',
                        'quarterly' => '3 Months',
                        'biannually' => '6 Months',
                        'annually' => 'Yearly',
                        'lifetime' => 'Lifetime',
                    ])
                    ->multiple(),
                SelectFilter::make('visibility')
                    ->options([
                        'public' => 'Public',
                        'private' => 'Private',
                        'hidden' => 'Hidden',
                    ])
                    ->multiple(),
                Tables\Filters\Filter::make('is_active')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->where('is_active', true)),
                Tables\Filters\Filter::make('is_featured')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->where('is_featured', true)),
                Tables\Filters\Filter::make('unlimited_traffic')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->where('unlimited_traffic', true)),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('clone')
                        ->label('Clone Plan')
                        ->icon('heroicon-o-document-duplicate')
                        ->action(function (ServerPlan $record) {
                            $newPlan = $record->replicate();
                            $newPlan->name = $record->name . ' (Copy)';
                            $newPlan->slug = Str::slug($newPlan->name);
                            $newPlan->is_active = false;
                            $newPlan->save();

                            redirect()->route('filament.admin.clusters.server-management.resources.server-plans.edit', $newPlan);
                        }),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(fn (Collection $records) => $records->each->update(['is_active' => true])),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(fn (Collection $records) => $records->each->update(['is_active' => false])),
                    Tables\Actions\BulkAction::make('toggle_featured')
                        ->label('Toggle Featured')
                        ->icon('heroicon-o-star')
                        ->action(fn (Collection $records) => $records->each(fn ($record) => $record->update(['is_featured' => !$record->is_featured]))),
                ])
            ])
            ->defaultSort('sort_order');
    }

    public static function getRelations(): array
    {
        return [
            // Define relations here if any
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServerPlans::route('/'),
            'create' => Pages\CreateServerPlan::route('/create'),
            'view' => Pages\ViewServerPlan::route('/{record}'),
            'edit' => Pages\EditServerPlan::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'server.name'];
    }

    public static function getNavigationBadge(): ?string {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): string|array|null {
        return static::getModel()::count() > 10 ? 'success' : 'danger';
    }
}
