<?php

namespace App\Filament\Clusters\CustomerManagement\Resources;

use App\Filament\Clusters\CustomerManagement;
use App\Filament\Clusters\CustomerManagement\Resources\SubscriptionResource\Pages;
use App\Filament\Clusters\CustomerManagement\Resources\SubscriptionResource\RelationManagers;
use App\Models\Subscription;
use BackedEnum;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SubscriptionResource extends Resource
    {
    protected static ?string $model = Subscription::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $cluster = CustomerManagement::class;

    public static function canAccess(): bool
    {
        $user = auth()->user();
        return (bool) ($user?->isAdmin() || $user?->isManager() || $user?->isSupportManager());
    }

    public static function form(Schema $schema): Schema
        {
        return $schema
            ->schema([
                Forms\Components\Group::make([
                    Forms\Components\Section::make('Subscription Details')
                        ->schema([
                            Forms\Components\Select::make('customer_id')
                                ->relationship('customer', 'name')
                                ->required()
                                ->columnSpan(2),
                            Forms\Components\TextInput::make('quantity')
                                ->required()
                                ->numeric()
                                ->columnSpan(1),
                            Forms\Components\TextInput::make('name')
                                ->required()
                                ->maxLength(255)
                                ->columnSpan(2),
                            Forms\Components\TextInput::make('stripe_id')
                                ->required()
                                ->maxLength(255),
                            Forms\Components\TextInput::make('stripe_plan')
                                ->required()
                                ->maxLength(255)
                                ->columnSpan(2),
                            Forms\Components\TextInput::make('stripe_status')
                                ->required()
                                ->maxLength(255),
                        ])->columns(3),
                ])->columnSpan(2),

                Forms\Components\Group::make([
                    Forms\Components\Section::make('Dates')
                        ->schema([
                            Forms\Components\DateTimePicker::make('trial_ends_at')
                                ->columnSpan(2),
                            Forms\Components\DateTimePicker::make('ends_at')
                                ->columnSpan(2),
                        ])->columns(2),
                ])->columnSpan(1),
            ])->columns(3);
        }

    public static function table(Table $table): Table
        {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('customer.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('stripe_id')
                    ->searchable(),
                Tables\Columns\TextColumn::make('stripe_status')
                    ->searchable(),
                Tables\Columns\TextColumn::make('stripe_plan')
                    ->searchable(),
                Tables\Columns\TextColumn::make('quantity')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('trial_ends_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('ends_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                \Filament\Actions\ActionGroup::make([
                    \Filament\Actions\EditAction::make(),
                    \Filament\Actions\ViewAction::make(),
                    \Filament\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
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
            'index' => Pages\ListSubscriptions::route('/'),
            'create' => Pages\CreateSubscription::route('/create'),
            'view' => Pages\ViewSubscription::route('/{record}'),
            'edit' => Pages\EditSubscription::route('/{record}/edit'),
        ];
        }
    }