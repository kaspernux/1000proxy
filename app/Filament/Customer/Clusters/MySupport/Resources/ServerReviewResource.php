<?php

namespace App\Filament\Customer\Clusters\MySupport\Resources;

use App\Filament\Customer\Clusters\MySupport;
use App\Models\ServerReview;
use App\Models\Server;
use App\Models\OrderItem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
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
use App\Filament\Customer\Clusters\MySupport\Resources\ServerReviewResource\Pages;

class ServerReviewResource extends Resource
{
    protected static ?string $model = ServerReview::class;
    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-ellipsis';
    protected static ?string $cluster = MySupport::class;
    protected static ?string $navigationLabel = 'Server Reviews';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Submit a Review')
                    ->description('Share your honest feedback about a server you used.')
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
                            ->hint('Only servers you have purchased plans from will appear here.')
                            ->hintIcon('heroicon-o-server-stack'),

                        Textarea::make('comments')
                            ->label('Your Feedback')
                            ->rows(6)
                            ->maxLength(1000)
                            ->required()
                            ->placeholder('Describe your experience, connection quality, speed...')
                            ->hint('Be constructive and help others 🙂')
                            ->hintIcon('heroicon-o-pencil'),
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

                TextColumn::make('comments')
                    ->label('Comments')
                    ->limit(50)
                    ->wrap(),

                BadgeColumn::make('approved')
                    ->label('Approval Status')
                    ->colors([
                        'success' => fn ($state) => $state === true,
                        'danger' => fn ($state) => $state === false,
                    ])
                    ->icons([
                        'heroicon-o-check-circle' => fn ($state) => $state === true,
                        'heroicon-o-x-circle' => fn ($state) => $state === false,
                    ])
                    ->formatStateUsing(fn ($state) => $state ? 'Approved' : 'Pending')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Submitted On')
                    ->since()
                    ->sortable(),
            ])
            ->actions([
                ViewAction::make(),
            ])
            ->headerActions([
                Action::make('Submit New Feedback')
                    ->icon('heroicon-o-plus')
                    ->url(fn () => static::getUrl('create'))
                    ->color('primary')
                    ->outlined(),
            ])
            ->emptyStateHeading('No reviews yet!')
            ->emptyStateDescription('You have not reviewed any server yet. Start by submitting your feedback.')
            ->emptyStateActions([
                Action::make('Submit a Review')
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
            'index' => Pages\ListServerReviews::route('/'),
            'create' => Pages\CreateServerReview::route('/create'),
            'view' => Pages\ViewServerReview::route('/{record}'),
        ];
    }
}
