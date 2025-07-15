<?php

namespace App\Filament\Customer\Clusters\MyServices\Resources;

use App\Filament\Customer\Clusters\MyServices;
use App\Models\ServerPlan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Colors\Color;
use App\Filament\Customer\Clusters\MyServices\Resources\ServerPlanResource\Pages;

class ServerPlanResource extends Resource
{
    protected static ?string $model = ServerPlan::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-plus';

    protected static ?string $cluster = MyServices::class;

    protected static ?string $navigationLabel = 'Available Plans';

    protected static ?string $modelLabel = 'Server Plan';

    protected static ?string $pluralModelLabel = 'Server Plans';

    protected static ?int $navigationSort = 5;

    public static function canCreate(): bool
    {
        return false; // Customers can't create plans
    }

    public static function canEdit($record): bool
    {
        return false; // Customers can't edit plans
    }

    public static function canDelete($record): bool
    {
        return false; // Customers can't delete plans
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('is_active', true)
            ->where('in_stock', true)
            ->with(['server', 'serverBrand', 'serverCategory'])
            ->orderBy('price', 'asc');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Form fields would go here but customers can't edit plan data
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('product_image')
                    ->label('Image')
                    ->size(60)
                    ->square(),

                Tables\Columns\TextColumn::make('name')
                    ->label('Plan Name')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Medium),

                Tables\Columns\TextColumn::make('server.name')
                    ->label('Server')
                    ->searchable()
                    ->sortable()
                    ->color(Color::Blue),

                Tables\Columns\TextColumn::make('serverBrand.name')
                    ->label('Brand')
                    ->badge()
                    ->color(Color::Purple)
                    ->placeholder('No Brand'),

                Tables\Columns\TextColumn::make('serverCategory.name')
                    ->label('Category')
                    ->badge()
                    ->color(Color::Gray)
                    ->placeholder('No Category'),

                Tables\Columns\TextColumn::make('price')
                    ->label('Price')
                    ->money('USD')
                    ->sortable()
                    ->weight(FontWeight::Bold)
                    ->color(Color::Green),

                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'monthly' => 'success',
                        'yearly' => 'info',
                        'lifetime' => 'warning',
                        'trial' => 'gray',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('days')
                    ->label('Duration')
                    ->formatStateUsing(fn ($state) => $state ? "{$state} days" : 'Unlimited')
                    ->sortable(),

                Tables\Columns\TextColumn::make('data_limit_gb')
                    ->label('Data Limit')
                    ->formatStateUsing(fn ($state) => $state > 0 ? self::formatBytes($state * 1024 * 1024 * 1024) : 'Unlimited')
                    ->color(fn ($state) => $state > 0 ? Color::Amber : Color::Green),

                Tables\Columns\TextColumn::make('max_clients')
                    ->label('Max Clients')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('current_clients')
                    ->label('Current')
                    ->numeric()
                    ->color(function ($record) {
                        if (!$record->max_clients) return Color::Green;
                        $percentage = ($record->current_clients / $record->max_clients) * 100;
                        if ($percentage >= 90) return Color::Red;
                        if ($percentage >= 75) return Color::Orange;
                        return Color::Green;
                    }),

                Tables\Columns\IconColumn::make('is_featured')
                    ->label('Featured')
                    ->boolean()
                    ->trueIcon('heroicon-o-star')
                    ->falseIcon('heroicon-o-star')
                    ->trueColor('warning')
                    ->falseColor('gray'),

                Tables\Columns\IconColumn::make('on_sale')
                    ->label('On Sale')
                    ->boolean()
                    ->trueIcon('heroicon-o-tag')
                    ->falseIcon('heroicon-o-tag')
                    ->trueColor('success')
                    ->falseColor('gray'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('server_id')
                    ->label('Server')
                    ->relationship('server', 'name')
                    ->preload(),

                Tables\Filters\SelectFilter::make('server_brand_id')
                    ->label('Brand')
                    ->relationship('serverBrand', 'name')
                    ->preload(),

                Tables\Filters\SelectFilter::make('server_category_id')
                    ->label('Category')
                    ->relationship('serverCategory', 'name')
                    ->preload(),

                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->options([
                        'monthly' => 'Monthly',
                        'yearly' => 'Yearly',
                        'lifetime' => 'Lifetime',
                        'trial' => 'Trial',
                    ]),

                Tables\Filters\SelectFilter::make('protocol')
                    ->label('Protocol')
                    ->options([
                        'vmess' => 'VMess',
                        'vless' => 'VLESS',
                        'trojan' => 'Trojan',
                        'shadowsocks' => 'Shadowsocks',
                    ]),

                Tables\Filters\Filter::make('featured_only')
                    ->label('Featured Plans')
                    ->query(fn (Builder $query): Builder => $query->where('is_featured', true)),

                Tables\Filters\Filter::make('on_sale_only')
                    ->label('On Sale')
                    ->query(fn (Builder $query): Builder => $query->where('on_sale', true)),

                Tables\Filters\Filter::make('available')
                    ->label('Available')
                    ->query(fn (Builder $query): Builder => $query->where('in_stock', true))
                    ->default(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([])
            ->defaultSort('price', 'asc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Grid::make(3)
                    ->schema([
                        Infolists\Components\Card::make([
                            Infolists\Components\ImageEntry::make('product_image')
                                ->label('Plan Image')
                                ->size(150),

                            Infolists\Components\TextEntry::make('name')
                                ->label('Plan Name')
                                ->weight(FontWeight::Bold)
                                ->size('lg'),

                            Infolists\Components\TextEntry::make('slug')
                                ->label('Slug')
                                ->fontFamily('mono')
                                ->copyable(),
                        ])->columnSpan(1),

                        Infolists\Components\Card::make([
                            Infolists\Components\TextEntry::make('price')
                                ->label('Price')
                                ->money('USD')
                                ->weight(FontWeight::Bold)
                                ->size('lg')
                                ->color(Color::Green),

                            Infolists\Components\TextEntry::make('type')
                                ->label('Billing Type')
                                ->badge()
                                ->color(fn (string $state): string => match ($state) {
                                    'monthly' => 'success',
                                    'yearly' => 'info',
                                    'lifetime' => 'warning',
                                    'trial' => 'gray',
                                    default => 'gray',
                                }),

                            Infolists\Components\TextEntry::make('days')
                                ->label('Duration')
                                ->formatStateUsing(fn ($state) => $state ? "{$state} days" : 'Unlimited'),
                        ])->columnSpan(1),

                        Infolists\Components\Card::make([
                            Infolists\Components\IconEntry::make('is_featured')
                                ->label('Featured Plan')
                                ->boolean()
                                ->trueIcon('heroicon-o-star')
                                ->falseIcon('heroicon-o-star')
                                ->trueColor('warning')
                                ->falseColor('gray'),

                            Infolists\Components\IconEntry::make('on_sale')
                                ->label('On Sale')
                                ->boolean()
                                ->trueIcon('heroicon-o-tag')
                                ->falseIcon('heroicon-o-tag')
                                ->trueColor('success')
                                ->falseColor('gray'),

                            Infolists\Components\IconEntry::make('renewable')
                                ->label('Renewable')
                                ->boolean()
                                ->trueIcon('heroicon-o-arrow-path')
                                ->falseIcon('heroicon-o-arrow-path')
                                ->trueColor('success')
                                ->falseColor('gray'),
                        ])->columnSpan(1),
                    ]),

                Infolists\Components\Section::make('Plan Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('description')
                            ->label('Description')
                            ->markdown()
                            ->columnSpanFull(),
                    ]),

                Infolists\Components\Section::make('Server Information')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('server.name')
                                    ->label('Server')
                                    ->color(Color::Blue),

                                Infolists\Components\TextEntry::make('server.location')
                                    ->label('Location'),

                                Infolists\Components\TextEntry::make('server.ip')
                                    ->label('Server IP')
                                    ->copyable(),
                            ]),
                    ]),

                Infolists\Components\Section::make('Technical Specifications')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('data_limit_gb')
                                    ->label('Data Limit')
                                    ->formatStateUsing(fn ($state) => $state > 0 ? self::formatBytes($state * 1024 * 1024 * 1024) : 'Unlimited')
                                    ->color(fn ($state) => $state > 0 ? Color::Amber : Color::Green),

                                Infolists\Components\TextEntry::make('bandwidth_mbps')
                                    ->label('Bandwidth')
                                    ->formatStateUsing(fn ($state) => $state ? "{$state} Mbps" : 'Unlimited')
                                    ->placeholder('Not specified'),

                                Infolists\Components\TextEntry::make('concurrent_connections')
                                    ->label('Max Connections')
                                    ->formatStateUsing(fn ($state) => $state ?: 'Unlimited')
                                    ->placeholder('Unlimited'),
                            ]),
                    ]),

                Infolists\Components\Section::make('Capacity & Availability')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('max_clients')
                                    ->label('Maximum Clients')
                                    ->formatStateUsing(fn ($state) => $state ?: 'Unlimited'),

                                Infolists\Components\TextEntry::make('current_clients')
                                    ->label('Current Clients')
                                    ->color(function ($record) {
                                        if (!$record->max_clients) return Color::Green;
                                        $percentage = ($record->current_clients / $record->max_clients) * 100;
                                        if ($percentage >= 90) return Color::Red;
                                        if ($percentage >= 75) return Color::Orange;
                                        return Color::Green;
                                    }),

                                Infolists\Components\TextEntry::make('availability_percentage')
                                    ->label('Availability')
                                    ->formatStateUsing(function ($record) {
                                        if (!$record->max_clients) return '100%';
                                        $available = $record->max_clients - $record->current_clients;
                                        $percentage = ($available / $record->max_clients) * 100;
                                        return number_format($percentage, 1) . '%';
                                    })
                                    ->color(function ($record) {
                                        if (!$record->max_clients) return Color::Green;
                                        $available = $record->max_clients - $record->current_clients;
                                        $percentage = ($available / $record->max_clients) * 100;
                                        if ($percentage <= 10) return Color::Red;
                                        if ($percentage <= 25) return Color::Orange;
                                        return Color::Green;
                                    }),
                            ]),
                    ]),

                Infolists\Components\Section::make('Brand & Category')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('serverBrand.name')
                                    ->label('Brand')
                                    ->badge()
                                    ->color(Color::Purple)
                                    ->placeholder('No Brand'),

                                Infolists\Components\TextEntry::make('serverCategory.name')
                                    ->label('Category')
                                    ->badge()
                                    ->color(Color::Gray)
                                    ->placeholder('No Category'),
                            ]),
                    ]),

                Infolists\Components\Section::make('Actions')
                    ->schema([
                        Infolists\Components\Actions::make([
                            Infolists\Components\Actions\Action::make('order_plan')
                                ->label('Order This Plan')
                                ->icon('heroicon-o-shopping-cart')
                                ->color('success')
                                ->url(fn ($record) => route('customer.order.create', ['plan' => $record->id]))
                                ->visible(fn ($record) => $record->in_stock),
                        ])
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServerPlans::route('/'),
            'view' => Pages\ViewServerPlan::route('/{record}'),
        ];
    }

    protected static function formatBytes($bytes): string
    {
        if ($bytes == 0) return '0 B';

        $k = 1024;
        $dm = 2;
        $sizes = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];

        $i = floor(log($bytes) / log($k));

        return round($bytes / pow($k, $i), $dm) . ' ' . $sizes[$i];
    }
}
