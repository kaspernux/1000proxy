<?php

namespace App\Filament\Clusters\ServerManagement\Resources;

use UnitEnum;
use BackedEnum;
use Filament\Schemas\Schema;

use App\Filament\Clusters\ServerManagement;
use App\Filament\Clusters\ServerManagement\Resources\ServerReviewResource\Pages;
use App\Filament\Clusters\ServerManagement\Resources\ServerReviewResource\RelationManagers;
use App\Models\ServerReview;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Group;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\ToggleButtons;


class ServerReviewResource extends Resource
{
    protected static ?string $model = ServerReview::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-megaphone';

    protected static ?int $navigationSort = 11;

    protected static ?string $cluster = ServerManagement::class;

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return (bool) ($user?->isAdmin() || $user?->isManager() || $user?->isSupportManager());
    }

    protected static ?string $recordTitleAttribute = 'comments';

    protected static string | UnitEnum | null $navigationGroup = 'CUSTOMER FEEDBACK';

    public static function getLabel(): string
    {
        return 'Server Reviews';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Group::make()->schema([
                    Section::make('💬 Review Content')->schema([
                        MarkdownEditor::make('comments')
                            ->label('Review Comments')
                            ->columnSpanFull()
                            ->fileAttachmentsDirectory('server-reviews')
                            ->required()
                            ->helperText('Customer review and feedback about the server'),
                    ]),
                ])->columnSpan(2),

                Group::make()->schema([
                    Section::make('🔗 Review Details')->schema([
                        Select::make('server_id')
                            ->label('Server')
                            ->relationship('server', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->helperText('Select the server being reviewed'),

                        Select::make('customer_id')
                            ->label('Customer')
                            ->relationship('customer', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->helperText('Customer who wrote this review'),
                    ])->columns(1),

                    Section::make('✅ Approval Status')->schema([
                        Toggle::make('approved')
                            ->label('Approved')
                            ->default(false)
                            ->helperText('Approve this review for public display'),
                    ]),
                ])->columnSpan(1),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        $table = $table
            ->columns([
                BadgeColumn::make('server.name')
                    ->label('Server')
                    ->searchable()
                    ->sortable()
                    ->color('primary')
                    ->tooltip('Server being reviewed'),

                BadgeColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable()
                    ->color('info')
                    ->tooltip('Customer who wrote the review'),

                TextColumn::make('comments')
                    ->label('Review')
                    ->limit(100)
                    ->tooltip(fn ($record) => $record->comments)
                    ->wrap()
                    ->searchable(),

                IconColumn::make('approved')
                    ->label('Approved')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->tooltip('Review approval status'),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('server_id')
                    ->relationship('server', 'name')
                    ->label('Server')
                    ->searchable()
                    ->preload()
                    ->multiple(),

                SelectFilter::make('customer_id')
                    ->relationship('customer', 'name')
                    ->label('Customer')
                    ->searchable()
                    ->preload()
                    ->multiple(),

                TernaryFilter::make('approved')
                    ->label('Approval Status')
                    ->placeholder('All reviews')
                    ->trueLabel('Approved only')
                    ->falseLabel('Pending approval'),
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make()
                        ->tooltip('View review details'),

                    EditAction::make()
                        ->tooltip('Edit review'),

                    Action::make('approve')
                        ->label('Approve')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($record) {
                            $record->approve();

                            \Filament\Notifications\Notification::make()
                                ->title('Review approved')
                                ->body('The review has been approved for public display.')
                                ->success()
                                ->send();
                        })
                        ->visible(fn ($record) => !$record->approved)
                        ->tooltip('Approve this review'),

                    Action::make('disapprove')
                        ->label('Disapprove')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(function ($record) {
                            $record->update(['approved' => false]);

                            \Filament\Notifications\Notification::make()
                                ->title('Review disapproved')
                                ->body('The review has been hidden from public display.')
                                ->warning()
                                ->send();
                        })
                        ->visible(fn ($record) => $record->approved)
                        ->tooltip('Hide this review from public'),

                    DeleteAction::make()
                        ->tooltip('Delete review'),
                ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->tooltip('Delete selected reviews'),

                    \Filament\Actions\BulkAction::make('approve_selected')
                        ->label('Approve Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($records) {
                            $count = 0;
                            foreach ($records as $record) {
                                if (!$record->approved) {
                                    $record->approve();
                                    $count++;
                                }
                            }

                            \Filament\Notifications\Notification::make()
                                ->title('Bulk approval completed')
                                ->body("Approved {$count} reviews.")
                                ->success()
                                ->send();
                        })
                        ->tooltip('Approve selected reviews'),

                    \Filament\Actions\BulkAction::make('disapprove_selected')
                        ->label('Disapprove Selected')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->action(function ($records) {
                            $count = 0;
                            foreach ($records as $record) {
                                if ($record->approved) {
                                    $record->update(['approved' => false]);
                                    $count++;
                                }
                            }

                            \Filament\Notifications\Notification::make()
                                ->title('Bulk disapproval completed')
                                ->body("Disapproved {$count} reviews.")
                                ->warning()
                                ->send();
                        })
                        ->tooltip('Hide selected reviews from public'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');

        return \App\Filament\Concerns\HasPerformanceOptimizations::applyTablePreset($table, [
            'defaultPage' => 25,
            'empty' => [
                'icon' => 'heroicon-o-megaphone',
                'heading' => 'No reviews found',
                'description' => 'Try a different search or filters.',
            ],
        ]);
    }

    public static function getRelations(): array
    {
        return [
            // Add relation managers if needed
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListServerReviews::route('/'),
            'create' => Pages\CreateServerReview::route('/create'),
            'view' => Pages\ViewServerReview::route('/{record}'),
            'edit' => Pages\EditServerReview::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['comments', 'server.name', 'customer.name'];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('approved', false)->count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        $pendingCount = static::getModel()::where('approved', false)->count();
        return $pendingCount > 0 ? 'warning' : 'success';
    }
}
