<?php

namespace App\Filament\Clusters\ProxyShop\Resources;

use App\Filament\Clusters\ProxyShop;
use App\Filament\Clusters\ProxyShop\Resources\InvoiceResource\Pages;
use App\Models\Invoice;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DatePicker;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $cluster = ProxyShop::class;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Group::make([
                    Section::make('Invoice Details')
                        ->schema([
                            TextInput::make('hash_id')
                                ->required()
                                ->maxLength(255),

                            TextInput::make('type')
                                ->maxLength(255),

                            TextInput::make('volume')
                                ->numeric(),

                            TextInput::make('day')
                                ->numeric(),

                            Textarea::make('description')
                                ->columnSpanFull(),
                        ])->columns(2),

                    Section::make('Financial Details')
                        ->schema([
                            TextInput::make('price')
                                ->required()
                                ->numeric()
                                ->prefix('$'),

                            TextInput::make('tron_price')
                                ->numeric(),

                            DatePicker::make('request_date'),

                            Select::make('state')
                                ->required()
                                ->options([
                                    'new' => 'New',
                                    'processing' => 'Processing',
                                    'completed' => 'Completed',
                                    'failed' => 'Failed',
                                ])
                                ->default('new'),
                        ])->columns(2),
                ])->columnSpan(2),

                Group::make([
                    Section::make('Order Information')
                        ->schema([
                            Select::make('order_id')
                                ->relationship('order', 'id')
                                ->required(),

                            Select::make('customer_id')
                                ->relationship('customer', 'name')
                                ->required(),
                        ])->columns(2),

                    Section::make('Agent Details')
                        ->schema([
                            TextInput::make('agent_bought')
                                ->numeric(),

                            TextInput::make('agent_count')
                                ->numeric(),
                        ])->columns(2),
                ])->columnSpan(1),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order.id')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('customer.name')
                    ->sortable(),

                Tables\Columns\TextColumn::make('hash_id')
                    ->searchable(),

                Tables\Columns\TextColumn::make('type')
                    ->searchable(),

                Tables\Columns\TextColumn::make('volume')
                    ->numeric(),

                Tables\Columns\TextColumn::make('day')
                    ->numeric(),

                Tables\Columns\TextColumn::make('price')
                    ->money(),

                Tables\Columns\TextColumn::make('tron_price')
                    ->numeric(),

                Tables\Columns\TextColumn::make('request_date')
                    ->date()
                    ->sortable(),

                Tables\Columns\TextColumn::make('state')
                    ->enum(['new', 'processing', 'completed', 'failed']),

                Tables\Columns\TextColumn::make('agent_bought')
                    ->numeric(),

                Tables\Columns\TextColumn::make('agent_count')
                    ->numeric(),

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
            'index' => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'view' => Pages\ViewInvoice::route('/{record}'),
            'edit' => Pages\EditInvoice::route('/{record}/edit'),
        ];
    }
}
