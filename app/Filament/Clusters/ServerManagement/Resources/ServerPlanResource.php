<?php

namespace App\Filament\Clusters\ServerManagement\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Schemas\Schema;
use BackedEnum;
use App\Models\ServerPlan;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Infolists\Infolist;
use Filament\Schemas\Components\Section as InfolistSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\ImageColumn;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Filters\SelectFilter;
use App\Filament\Clusters\ServerManagement;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use App\Filament\Clusters\ServerManagement\Resources\ServerPlanResource\Pages;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Services\XUIService;

class ServerPlanResource extends Resource
{
    protected static ?string $model = ServerPlan::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-fire';

    protected static ?int $navigationSort = 5;

    protected static ?string $cluster = ServerManagement::class;

    public static function canAccess(): bool
    {
        $user = auth()->user();
        // Admin/manager can access; support_manager read-only enforced by policy; sales_support no.
        return (bool) ($user?->isAdmin() || $user?->isManager() || $user?->isSupportManager());
    }

    public static function form(Schema $schema): Schema
    {
        // A unified wizard for create & edit with reactive protocol/type selection
        return $schema
            ->schema([
                Wizard::make()->label('Plan Editor')
                    ->columnSpanFull()
                    ->extraAttributes(['class' => 'w-full'])
                    ->steps([
                        Step::make('Basics')
                            ->icon('heroicon-o-identification')
                            ->schema([
                                Group::make()->schema([
                                    TextInput::make('name')->required()->maxLength(255),
                                    TextInput::make('slug')->required()->disabled()->unique(ServerPlan::class, 'slug', ignoreRecord: true),
                                    Select::make('server_id')
                                        ->relationship('server', 'name')
                                        ->required()
                                        ->searchable()
                                        ->preload()
                                        ->reactive()
                                        ->helperText('Server this plan belongs to'),
                                    Select::make('type')
                                        ->label('Plan Type')
                                        ->options(fn () => method_exists(ServerPlan::class, 'types') ? ServerPlan::types() : [
                                            'single' => 'Single',
                                            'branded' => 'Branded',
                                            'reseller' => 'Reseller',
                                        ])
                                        ->required()
                                        ->reactive(),
                                    Select::make('protocol')
                                        ->label('Protocol')
                                        ->options(function (callable $get) {
                                            $serverId = $get('server_id');
                                            try {
                                                if ($serverId) {
                                                    $protocols = XUIService::getProtocols($serverId) ?? [];
                                                    if (is_array($protocols) && count($protocols) > 0) {
                                                        return array_combine($protocols, array_map(fn($p) => strtoupper($p), $protocols));
                                                    }
                                                }
                                            } catch (\Throwable $e) {
                                                // ignore and fall back
                                            }
                                            // Fallback to the canonical protocol list defined on ServerPlan
                                            try {
                                                if (method_exists(\App\Models\ServerPlan::class, 'protocols')) {
                                                    return \App\Models\ServerPlan::protocols();
                                                }
                                            } catch (\Throwable $_) {}
                                            return [
                                                'vless' => 'VLESS',
                                                'vmess' => 'VMess',
                                                'trojan' => 'Trojan',
                                                'shadowsocks' => 'Shadowsocks',
                                                'dokodemo-door' => 'Dokodemo-door',
                                                'socks' => 'Socks',
                                                'http' => 'HTTP',
                                                'wireguard' => 'WireGuard',
                                            ];
                                        })
                                        ->helperText('Protocol used by clients')
                                        ->required(),
                                ])->columns(2),
                            ])->columns(1),

                        Step::make('Pricing & Billing')
                            ->icon('heroicon-o-banknotes')
                            ->schema([
                                Group::make()->schema([
                                    TextInput::make('price')->required()->numeric()->prefix('$')->minValue(0)->step('0.01'),
                                    TextInput::make('original_price')->label('Original Price')->numeric()->prefix('$')->minValue(0)->step('0.01'),
                                    Select::make('billing_cycle')->label('Billing Cycle')->options([
                                        'monthly' => 'Monthly',
                                        'yearly' => 'Yearly',
                                        'lifetime' => 'Lifetime',
                                    ])->default('monthly'),
                                    TextInput::make('setup_fee')->label('Setup Fee')->numeric()->prefix('$')->default(0)->minValue(0)->step('0.01'),
                                    TextInput::make('days')->label('Validity Period (Days)')->required()->numeric()->default(30)->minValue(1),
                                ])->columns(2),
                            ])->columns(1),

                        Step::make('Resources')
                            ->icon('heroicon-o-cpu-chip')
                            ->schema([
                                Group::make()->schema([
                                    TextInput::make('volume')->label('Volume (GB)')->numeric()->nullable(),
                                    Toggle::make('unlimited_traffic')->label('Unlimited Traffic'),
                                    TextInput::make('data_limit_gb')->label('Data Limit (GB)')->numeric()->nullable(),
                                    TextInput::make('bandwidth_mbps')->label('Bandwidth (Mbps)')->numeric()->nullable(),
                                    TextInput::make('concurrent_connections')->label('Concurrent Connections')->numeric()->nullable(),
                                    TextInput::make('capacity')->label('Capacity')->numeric()->nullable(),
                                    Toggle::make('supports_ipv6')->label('IPv6 Support')->default(false),
                                ])->columns(2),
                            ])->columns(1),

                        Step::make('Advanced')
                            ->icon('heroicon-o-adjustments-horizontal')
                            ->schema([
                                Group::make()->schema([
                                    TextInput::make('max_clients')->numeric()->nullable(),
                                    TextInput::make('current_clients')->numeric()->nullable(),
                                    Toggle::make('auto_provision')->label('Auto Provision')->default(false),
                                    Select::make('preferred_inbound_id')->relationship('preferredInbound', 'remark')->nullable()->preload()->searchable(),
                                ])->columns(2),
                            ])->columns(1),

                        Step::make('Media & Content')
                            ->icon('heroicon-o-photo')
                            ->schema([
                                Group::make()->schema([
                                    FileUpload::make('product_image')->image()->directory('plans')->label('Product Image'),
                                    MarkdownEditor::make('description')->label('Plan Description')->columnSpanFull(),
                                ])->columns(1),
                            ])->columns(1),
                    ]),

                // Keep legacy groups visible on edit only (for backward compatibility)
                Group::make()->schema([
                    Section::make('Status & Visibility')->schema([
                        Toggle::make('is_active'),
                        Toggle::make('is_featured'),
                        Toggle::make('is_popular'),
                        Select::make('visibility')->options([
                            'public' => 'Public',
                            'hidden' => 'Hidden',
                        ])->nullable(),
                        Toggle::make('in_stock')->helperText('Plan is currently available'),
                    ])->columns(2),
                ])->hidden(fn ($context) => $context === 'create'),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->schema([
            Tabs::make('Plan Details')->tabs([
                Tabs\Tab::make('Overview')->schema([
                    InfolistSection::make('Summary')->schema([
                        TextEntry::make('name')->label('Plan'),
                        TextEntry::make('server.name')->label('Server')->badge(),
                        TextEntry::make('price')->label('Price')->money('USD'),
                        TextEntry::make('original_price')->label('Original')->money('USD')->placeholder('—'),
                        TextEntry::make('billing_cycle')->label('Billing')->badge(),
                        TextEntry::make('days')->label('Duration')->formatStateUsing(fn ($s) => $s . ' days'),
                    ])->columns(3),
                ]),
                Tabs\Tab::make('Capacity')->schema([
                    InfolistSection::make('Usage & Limits')->schema([
                        TextEntry::make('volume')->label('Data (GB)'),
                        TextEntry::make('unlimited_traffic')->label('Unlimited')->formatStateUsing(fn ($s) => $s ? 'Yes' : 'No'),
                        TextEntry::make('concurrent_connections')->label('Max Connections'),
                        TextEntry::make('bandwidth_mbps')->label('Bandwidth (Mbps)'),
                    ])->columns(4),
                ]),
                Tabs\Tab::make('Meta')->schema([
                    InfolistSection::make('Meta')->schema([
                        TextEntry::make('visibility')->badge(),
                        TextEntry::make('is_active')->label('Active')->formatStateUsing(fn ($s) => $s ? 'Yes' : 'No'),
                        TextEntry::make('is_featured')->label('Featured')->formatStateUsing(fn ($s) => $s ? 'Yes' : 'No'),
                        TextEntry::make('in_stock')->label('In Stock')->formatStateUsing(fn ($s) => $s ? 'Yes' : 'No'),
                        TextEntry::make('on_sale')->label('On Sale')->formatStateUsing(fn ($s) => $s ? 'Yes' : 'No'),
                        TextEntry::make('created_at')->dateTime()->label('Created'),
                        TextEntry::make('updated_at')->dateTime()->label('Updated'),
                    ])->columns(3),
                ]),
            ])
        ]);
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
                    ->getStateUsing(fn ($record) => $record->orderItems()->count())
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
                ActionGroup::make([
                    Action::make('clone')
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
                    EditAction::make(),
                    ViewAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    BulkAction::make('activate')
                        ->label('Activate')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(fn (Collection $records) => $records->each->update(['is_active' => true])),
                    BulkAction::make('deactivate')
                        ->label('Deactivate')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(fn (Collection $records) => $records->each->update(['is_active' => false])),
                    BulkAction::make('toggle_featured')
                        ->label('Toggle Featured')
                        ->icon('heroicon-o-star')
                        ->action(fn (Collection $records) => $records->each(fn ($record) => $record->update(['is_featured' => !$record->is_featured]))),
                ])
            ])
            // Removed defaultSort('sort_order') due to missing column

        ;
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
