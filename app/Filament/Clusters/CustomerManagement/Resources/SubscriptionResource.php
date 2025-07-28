<?php

namespace App\Filament\Clusters\CustomerManagement\Resources;

use App\Filament\Clusters\CustomerManagement;
use App\Filament\Clusters\CustomerManagement\Resources\SubscriptionResource\Pages;
use App\Filament\Clusters\CustomerManagement\Resources\SubscriptionResource\RelationManagers;
use App\Models\Subscription;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SubscriptionResource extends Resource
    {
    protected static ?string $model = Subscription::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $cluster = CustomerManagement::class;

    public static function form(Form $form): Form
        {
        return $form
            ->schema([
                Forms\Components\Group::make([
                    Forms\Components\Section::make('Subscription Details')
                        ->schema([
                            Forms\Components\Select::make('user_id')
                                ->relationship('user', 'name')
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
                Tables\Columns\TextColumn::make('user.name')
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