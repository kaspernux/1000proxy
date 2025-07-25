<?php

namespace App\Filament\Customer\Clusters\MySupport\Resources;

use App\Filament\Customer\Clusters\MySupport;
use App\Models\ServerRating;
use App\Models\Server;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use App\Filament\Customer\Clusters\MySupport\Resources\ServerRatingResource\Pages;

class ServerRatingResource extends Resource
{
    protected static ?string $model = ServerRating::class;
    protected static ?string $navigationIcon = 'heroicon-o-star';
    protected static ?string $cluster = MySupport::class;
    protected static ?string $navigationLabel = 'Server Ratings';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Rate a Server')
                    ->description('Choose a server you purchased and rate it.')
                    ->schema([
                        Select::make('server_id')
                            ->label('Select Server')
                            ->options(fn () => 
                                Server::whereIn('id', function ($query) {
                                    $query->select('server_id')
                                        ->from('server_plans')
                                        ->join('order_items', 'server_plans.id', '=', 'order_items.server_plan_id')
                                        ->join('orders', 'order_items.order_id', '=', 'orders.id')
                                        ->where('orders.customer_id', Auth::guard('customer')->id());
                                })->pluck('name', 'id')
                            )
                            ->searchable()
                            ->default(request()->query('server'))
                            ->required()
                            ->hint('Only servers you purchased are available.')
                            ->hintIcon('heroicon-o-server-stack'),

                        Select::make('rating')
                            ->label('Your Rating')
                            ->options([
                                5 => '⭐⭐⭐⭐⭐ Excellent',
                                4 => '⭐⭐⭐⭐ Good',
                                3 => '⭐⭐⭐ Average',
                                2 => '⭐⭐ Poor',
                                1 => '⭐ Terrible',
                            ])
                            ->required()
                            ->hint('Select the number of stars.')
                            ->hintIcon('heroicon-o-star'),
                    ])
                    ->columns(1)
                    ->collapsible(),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('server.name')
                    ->label('Server')
                    ->sortable()
                    ->searchable(),

                BadgeColumn::make('rating')
                    ->colors([
                        'success' => fn ($state) => $state >= 4,
                        'warning' => fn ($state) => $state == 3,
                        'danger' => fn ($state) => $state <= 2,
                    ])
                    ->label('Rating')
                    ->formatStateUsing(fn ($state) => str_repeat('⭐', $state))
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Rated On')
                    ->since()
                    ->sortable(),
            ])
            ->actions([
                ViewAction::make(),
            ])
            ->headerActions([
                Action::make('Submit New Rating')
                    ->icon('heroicon-o-plus')
                    ->url(fn () => static::getUrl('create'))
                    ->color('primary')
                    ->outlined(),
            ])
            ->emptyStateHeading('No ratings yet!')
            ->emptyStateDescription('You have not rated any servers yet. Start by leaving your opinion.')
            ->emptyStateActions([
                Action::make('Submit a Rating')
                    ->icon('heroicon-o-plus')
                    ->url(fn () => static::getUrl('create'))
                    ->button(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('customer_id', Auth::guard('customer')->id());
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServerRatings::route('/'),
            'create' => Pages\CreateServerRating::route('/create'),
            'view' => Pages\ViewServerRating::route('/{record}'),
        ];
    }
}
