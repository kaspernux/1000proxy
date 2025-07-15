<?php

namespace App\Filament\Customer\Clusters\MyOrders\Resources;

use App\Filament\Customer\Clusters\MyOrders;
use App\Filament\Customer\Clusters\MyOrders\Resources\DownloadableItemResource\Pages;
use App\Filament\Customer\Clusters\MyOrders\Resources\DownloadableItemResource\RelationManagers;
use App\Models\DownloadableItem;
use App\Models\Server;
use App\Models\OrderItem;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

use Illuminate\Support\Facades\Auth;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Tabs;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\IconEntry;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Notifications\Notification;
use Filament\Resources\Components\Tab;
use Filament\Support\Enums\FontWeight;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;


class DownloadableItemResource extends Resource
{
    protected static ?string $model = DownloadableItem::class;
    protected static ?string $navigationIcon = 'heroicon-o-folder-arrow-down';
    protected static ?string $cluster = MyOrders::class;
    protected static ?string $navigationLabel = 'Downloads';
    protected static ?string $pluralLabel = 'Downloads';
    protected static ?string $label = 'Download';
    protected static ?int $navigationSort = 4;

    // Security: Disable create, edit, delete operations
    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Placeholder::make('info')
                ->content('Downloadable items are automatically generated for your completed orders. You cannot create or edit them manually.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('File Name')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::SemiBold)
                    ->color('primary')
                    ->description(fn (DownloadableItem $record): string => $record->description ?? ''),

                TextColumn::make('orderItem.order.id')
                    ->label('Order #')
                    ->copyable()
                    ->sortable()
                    ->prefix('#')
                    ->color('info')
                    ->url(fn (DownloadableItem $record): string => 
                        route('filament.customer.resources.my-orders.orders.view', ['record' => $record->orderItem->order_id])
                    ),

                TextColumn::make('orderItem.serverPlan.name')
                    ->label('Related Plan')
                    ->badge()
                    ->color('primary')
                    ->searchable(),

                TextColumn::make('file_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn ($state) => match (strtolower($state)) {
                        'pdf' => 'danger',
                        'txt' => 'gray',
                        'config' => 'info',
                        'zip' => 'warning',
                        'json' => 'success',
                        default => 'gray',
                    })
                    ->icon(fn ($state) => match (strtolower($state)) {
                        'pdf' => 'heroicon-m-document-text',
                        'txt' => 'heroicon-m-document',
                        'config' => 'heroicon-m-cog',
                        'zip' => 'heroicon-m-archive-box',
                        'json' => 'heroicon-m-code-bracket',
                        default => 'heroicon-m-document',
                    })
                    ->sortable(),

                TextColumn::make('file_size')
                    ->label('Size')
                    ->formatStateUsing(function ($state): string {
                        if (!$state) return 'Unknown';
                        return number_format($state / 1024, 1) . ' KB';
                    })
                    ->color('gray')
                    ->sortable(),

                BadgeColumn::make('download_status')
                    ->label('Status')
                    ->colors([
                        'success' => 'available',
                        'warning' => 'generating',
                        'danger' => 'failed',
                        'gray' => 'pending',
                    ])
                    ->icons([
                        'heroicon-m-check-circle' => 'available',
                        'heroicon-m-arrow-path' => 'generating',
                        'heroicon-m-x-circle' => 'failed',
                        'heroicon-m-clock' => 'pending',
                    ])
                    ->sortable(),

                TextColumn::make('download_count')
                    ->label('Downloads')
                    ->badge()
                    ->color('info')
                    ->sortable(),

                TextColumn::make('expires_at')
                    ->label('Expires')
                    ->dateTime('M j, Y')
                    ->color(function ($record): string {
                        if (!$record->expires_at) return 'gray';
                        return $record->expires_at->isPast() ? 'danger' : 'success';
                    })
                    ->visible(fn (DownloadableItem $record): bool => $record->expires_at !== null),

                TextColumn::make('created_at')
                    ->label('Available Since')
                    ->since()
                    ->sortable()
                    ->color('gray'),
            ])
            ->filters([
                SelectFilter::make('file_type')
                    ->label('File Type')
                    ->options([
                        'pdf' => 'PDF',
                        'txt' => 'Text',
                        'config' => 'Configuration',
                        'zip' => 'Archive',
                        'json' => 'JSON',
                    ])
                    ->indicator('File Type'),

                SelectFilter::make('download_status')
                    ->label('Status')
                    ->options([
                        'available' => 'Available',
                        'generating' => 'Generating',
                        'failed' => 'Failed',
                        'pending' => 'Pending',
                    ])
                    ->indicator('Status'),

                Filter::make('expires_at')
                    ->form([
                        DatePicker::make('expires_before')->label('Expires Before'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['expires_before'],
                            fn (Builder $query, $date): Builder => $query->whereDate('expires_at', '<=', $date),
                        );
                    })
                    ->indicator('Expiration Date'),
            ])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('View Details')
                        ->icon('heroicon-o-eye')
                        ->color('primary'),

                    Action::make('download')
                        ->label('Download')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('success')
                        ->visible(fn (DownloadableItem $record): bool => 
                            $record->download_status === 'available' &&
                            ($record->expires_at === null || !$record->expires_at->isPast())
                        )
                        ->action(function (DownloadableItem $record) {
                            try {
                                if (!Storage::exists($record->file_path)) {
                                    Notification::make()
                                        ->title('File Not Found')
                                        ->body('The file is not available for download.')
                                        ->danger()
                                        ->send();
                                    return;
                                }

                                // Increment download count
                                $record->increment('download_count');

                                return Storage::download($record->file_path, $record->name);
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Download Failed')
                                    ->body('Could not download the file. Please try again later.')
                                    ->danger()
                                    ->send();
                            }
                        }),

                    Action::make('view_order')
                        ->label('View Order')
                        ->icon('heroicon-o-shopping-bag')
                        ->color('info')
                        ->url(fn (DownloadableItem $record): string => 
                            route('filament.customer.resources.my-orders.orders.view', ['record' => $record->orderItem->order_id])
                        ),
                ])
                ->label('Actions')
                ->icon('heroicon-m-ellipsis-vertical')
                ->size('sm')
                ->color('gray')
                ->button()
            ])
            ->bulkActions([
                // No bulk actions for security
            ])
            ->emptyStateHeading('No Downloads Available')
            ->emptyStateDescription('Files and configurations from your completed orders will appear here.')
            ->emptyStateIcon('heroicon-o-folder-arrow-down')
            ->defaultSort('created_at', 'desc')
            ->poll('30s')
            ->striped();
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Tabs::make('Download Details')
                    ->persistTab()
                    ->columnSpanFull()
                    ->tabs([
                        Tabs\Tab::make('Overview')
                            ->icon('heroicon-m-folder-arrow-down')
                            ->schema([
                                Grid::make(3)
                                    ->schema([
                                        Section::make('File Information')
                                            ->description('Basic file details and metadata')
                                            ->icon('heroicon-m-document')
                                            ->schema([
                                                TextEntry::make('name')
                                                    ->label('File Name')
                                                    ->weight(FontWeight::Bold)
                                                    ->color('primary'),

                                                TextEntry::make('file_type')
                                                    ->label('File Type')
                                                    ->badge()
                                                    ->color(fn ($state) => match (strtolower($state)) {
                                                        'pdf' => 'danger',
                                                        'txt' => 'gray',
                                                        'config' => 'info',
                                                        'zip' => 'warning',
                                                        'json' => 'success',
                                                        default => 'gray',
                                                    }),

                                                TextEntry::make('file_size')
                                                    ->label('File Size')
                                                    ->formatStateUsing(function ($state): string {
                                                        if (!$state) return 'Unknown';
                                                        if ($state < 1024) return $state . ' B';
                                                        if ($state < 1048576) return number_format($state / 1024, 1) . ' KB';
                                                        return number_format($state / 1048576, 1) . ' MB';
                                                    })
                                                    ->color('info'),

                                                TextEntry::make('description')
                                                    ->label('Description')
                                                    ->markdown()
                                                    ->placeholder('No description available'),
                                            ]),

                                        Section::make('Download Status')
                                            ->description('Availability and download information')
                                            ->icon('heroicon-m-arrow-down-tray')
                                            ->schema([
                                                TextEntry::make('download_status')
                                                    ->label('Status')
                                                    ->badge()
                                                    ->color(fn ($state) => match ($state) {
                                                        'available' => 'success',
                                                        'generating' => 'warning',
                                                        'failed' => 'danger',
                                                        'pending' => 'gray',
                                                        default => 'gray',
                                                    }),

                                                TextEntry::make('download_count')
                                                    ->label('Download Count')
                                                    ->badge()
                                                    ->color('info'),

                                                TextEntry::make('max_downloads')
                                                    ->label('Download Limit')
                                                    ->formatStateUsing(fn ($state): string => $state ? (string) $state : 'Unlimited')
                                                    ->color('warning')
                                                    ->visible(fn (DownloadableItem $record): bool => $record->max_downloads !== null),

                                                TextEntry::make('expires_at')
                                                    ->label('Expires At')
                                                    ->dateTime('F j, Y \a\t g:i A')
                                                    ->color(function ($record): string {
                                                        if (!$record->expires_at) return 'gray';
                                                        return $record->expires_at->isPast() ? 'danger' : 'success';
                                                    })
                                                    ->visible(fn (DownloadableItem $record): bool => $record->expires_at !== null),
                                            ]),

                                        Section::make('Related Order')
                                            ->description('Associated order and item details')
                                            ->icon('heroicon-m-shopping-bag')
                                            ->schema([
                                                TextEntry::make('orderItem.order.id')
                                                    ->label('Order Number')
                                                    ->formatStateUsing(fn ($state): string => "#{$state}")
                                                    ->copyable()
                                                    ->color('primary')
                                                    ->url(fn (DownloadableItem $record): string => 
                                                        route('filament.customer.resources.my-orders.orders.view', ['record' => $record->orderItem->order_id])
                                                    ),

                                                TextEntry::make('orderItem.serverPlan.name')
                                                    ->label('Plan Name')
                                                    ->badge()
                                                    ->color('primary'),

                                                TextEntry::make('orderItem.order.order_status')
                                                    ->label('Order Status')
                                                    ->badge()
                                                    ->color(fn ($state) => match ($state) {
                                                        'new' => 'gray',
                                                        'processing' => 'warning',
                                                        'completed' => 'success',
                                                        'dispute' => 'danger',
                                                        default => 'gray',
                                                    }),

                                                TextEntry::make('orderItem.order.created_at')
                                                    ->label('Order Date')
                                                    ->since()
                                                    ->color('gray'),
                                            ]),
                                    ]),
                            ]),

                        Tabs\Tab::make('File Details')
                            ->icon('heroicon-m-document-text')
                            ->schema([
                                Section::make('Technical Information')
                                    ->description('File path and technical details')
                                    ->icon('heroicon-m-cog')
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextEntry::make('file_path')
                                                    ->label('File Path')
                                                    ->copyable()
                                                    ->color('gray'),

                                                TextEntry::make('mime_type')
                                                    ->label('MIME Type')
                                                    ->badge()
                                                    ->color('info'),

                                                TextEntry::make('created_at')
                                                    ->label('Created At')
                                                    ->dateTime('F j, Y \a\t g:i A')
                                                    ->since()
                                                    ->color('gray'),

                                                TextEntry::make('updated_at')
                                                    ->label('Last Updated')
                                                    ->since()
                                                    ->color('gray'),
                                            ]),
                                    ]),
                            ]),
                    ])
                    ->contained(true),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDownloadableItems::route('/'),
            'view'  => Pages\ViewDownloadableItem::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['orderItem.order.customer', 'orderItem.serverPlan'])
            ->whereHas('orderItem.order', function ($query) {
                $query->where('customer_id', Auth::guard('customer')->id());
            });
    }

    public static function getTabs(): array
    {
        $customerId = Auth::guard('customer')->id();

        return [
            'all' => Tab::make('All Downloads')
                ->icon('heroicon-m-folder-arrow-down')
                ->badge(
                    DownloadableItem::whereHas('orderItem.order', function ($query) use ($customerId) {
                        $query->where('customer_id', $customerId);
                    })->count()
                ),

            'available' => Tab::make('Available')
                ->icon('heroicon-m-check-circle')
                ->modifyQueryUsing(fn (Builder $query) => 
                    $query->where('download_status', 'available')
                          ->where(function ($q) {
                              $q->whereNull('expires_at')
                                ->orWhere('expires_at', '>', now());
                          })
                )
                ->badge(
                    DownloadableItem::whereHas('orderItem.order', function ($query) use ($customerId) {
                        $query->where('customer_id', $customerId);
                    })
                    ->where('download_status', 'available')
                    ->where(function ($q) {
                        $q->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                    })->count()
                )
                ->badgeColor('success'),

            'expired' => Tab::make('Expired')
                ->icon('heroicon-m-x-circle')
                ->modifyQueryUsing(fn (Builder $query) => 
                    $query->whereNotNull('expires_at')
                          ->where('expires_at', '<=', now())
                )
                ->badge(
                    DownloadableItem::whereHas('orderItem.order', function ($query) use ($customerId) {
                        $query->where('customer_id', $customerId);
                    })
                    ->whereNotNull('expires_at')
                    ->where('expires_at', '<=', now())->count()
                )
                ->badgeColor('danger'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $customerId = Auth::guard('customer')->id();
        $availableCount = DownloadableItem::whereHas('orderItem.order', function ($query) use ($customerId) {
            $query->where('customer_id', $customerId);
        })
        ->where('download_status', 'available')
        ->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        })->count();
        
        return $availableCount > 0 ? (string) $availableCount : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }
}

