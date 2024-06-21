<?php

namespace App\Filament\Clusters\ProxyShop\Resources;

use App\Filament\Clusters\ProxyShop;
use App\Filament\Clusters\ProxyShop\Resources\InvoiceResource\Pages;
use App\Filament\Clusters\ProxyShop\Resources\InvoiceResource\RelationManagers;
use App\Models\Invoice;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\ToggleButtons;
use Filament\Support\RawJs;
use Illuminate\Support\Str;
use Filament\Tables\Actions;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Forms\Components\SelectColumn;
use GuzzleHttp\Client;
use Filament\Forms\Components\Toggle;

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
                    Section::make('Financial Details')
                        ->schema([
                            TextInput::make('amount')
                                ->required()
                                ->numeric()
                                ->prefix('$'),

                            TextInput::make('tron_price')
                                ->numeric(),
                        ])->columns(2),

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

                            MarkdownEditor::make('description')
                                ->columnSpanFull()
                                ->fileAttachmentsDirectory('Invoices'),
                        ])->columns(2)
                ])->columnSpan(2),

                Group::make([
                    Section::make('Order Information')
                        ->schema([
                            DatePicker::make('request_date')
                            ->label('Date'),
                            Select::make('order_id')
                                ->relationship('order', 'id')
                                ->required(),

                            Select::make('customer_id')
                                ->relationship('customer', 'name')
                                ->required(),
                        ])->columns(1),

                    Section::make('Agent Details')
                        ->schema([
                            Toggle::make('agent_bought')
                                ->required()
                                ->default(false),

                            Toggle::make('agent_count')
                                ->required()
                                ->default(false)
                        ])->columns(2),

                        Section::make('Agent Details')
                        ->schema([
                            ToggleButtons::make('state')
                                ->required()
                                ->options([
                                    'new' => 'New',
                                    'failed' => 'Failed',
                                    'processing' => 'Processing',
                                    'completed' => 'Completed',
                                ])
                                ->colors([
                                    'new' => 'info',
                                    'processing' => 'warning',
                                    'completed' => 'success',
                                    'failed' => 'danger',
                                ])
                                ->icons([
                                    'new' => 'heroicon-o-sparkles',
                                    'processing' => 'heroicon-o-arrow-path',
                                    'completed' => 'heroicon-o-check-badge',
                                    'failed' => 'heroicon-o-eye',
                                ])
                                ->columns(2)
                                ->default('new'),
                            ])
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
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('hash_id')
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('volume')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('day')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tron_price')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('request_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('state'),
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
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
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