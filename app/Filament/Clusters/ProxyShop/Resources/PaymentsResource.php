<?php

namespace App\Filament\Clusters\ProxyShop\Resources;

use App\Filament\Clusters\ProxyShop;
use App\Filament\Clusters\ProxyShop\Resources\PaymentsResource\Pages;
use App\Filament\Clusters\ProxyShop\Resources\PaymentsResource\RelationManagers;
use App\Models\Payments;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PaymentsResource extends Resource
    {
    protected static ?string $model = Payments::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $cluster = ProxyShop::class;

    public static function form(Form $form): Form
        {
        return $form
            ->schema([
                Group::make([
                    Section::make('Payment Details')
                        ->schema([
                            Forms\Components\TextInput::make('order_id')
                                ->required()
                                ->numeric()
                                ->columnSpan(6),
                            Forms\Components\TextInput::make('customer_id')
                                ->required()
                                ->numeric()
                                ->columnSpan(6),
                            Forms\Components\TextInput::make('payment_method_id')
                                ->required()
                                ->maxLength(255)
                                ->columnSpan(6),
                            Forms\Components\TextInput::make('type')
                                ->required()
                                ->maxLength(255)
                                ->columnSpan(6),
                            Forms\Components\TextInput::make('hash_id')
                                ->required()
                                ->maxLength(255)
                                ->columnSpan(12),
                            Forms\Components\MarkdownEditor::make('description')
                                ->required()
                                ->maxLength(255)
                                ->fileAttachmentsDirectory('Payments')
                                ->columnSpan(12),
                        ])->columns(12),


                    Section::make('Additional Information')
                        ->schema([
                            Forms\Components\DateTimePicker::make('request_date')
                                ->required()
                                ->columnSpan(6),
                            Forms\Components\TextInput::make('state')
                                ->required()
                                ->maxLength(255)
                                ->columnSpan(6),
                            Forms\Components\TextInput::make('agent_bought')
                                ->required()
                                ->numeric()
                                ->columnSpan(6),
                            Forms\Components\TextInput::make('agent_count')
                                ->required()
                                ->numeric()
                                ->columnSpan(6),
                        ])->columns(12),
                ])->columnSpan(2),

                Group::make([
                    Section::make('Server and Volume Details')
                        ->schema([
                            Forms\Components\TextInput::make('server_plan_id')
                                ->required()
                                ->numeric()
                                ->columnSpan(6),
                            Forms\Components\TextInput::make('volume')
                                ->required()
                                ->numeric()
                                ->columnSpan(6),
                            Forms\Components\DateTimePicker::make('day')
                                ->required()
                                ->columnSpan(6),
                            Forms\Components\TextInput::make('price')
                                ->required()
                                ->numeric()
                                ->prefix('$')
                                ->columnSpan(6),
                            Forms\Components\TextInput::make('tron_price')
                                ->required()
                                ->numeric()
                                ->columnSpan(6),
                        ])->columns(6),
                ])->columnSpan(1),


            ])->columns(3);
        }

    public static function table(Table $table): Table
        {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('customer_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('hash_id')
                    ->searchable(),
                Tables\Columns\TextColumn::make('description')
                    ->searchable(),
                Tables\Columns\TextColumn::make('payment_method_id')
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('server_plan_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('volume')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('day')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('price')
                    ->money()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tron_price')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('request_date')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('state')
                    ->searchable(),
                Tables\Columns\TextColumn::make('agent_bought')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('agent_count')
                    ->numeric()
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
            'index' => Pages\ListPayments::route('/'),
            'create' => Pages\CreatePayments::route('/create'),

            'view' => Pages\ViewPayments::route('/{record}'),
            'edit' => Pages\EditPayments::route('/{record}/edit'),
        ];
        }
    }