<?php

namespace App\Filament\Clusters\CustomerManagement\Resources;
use Filament\Forms;
use Filament\Tables;
use App\Models\Customer;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Support\Htmlable;
use App\Models\PaymentMethod; // Ensure this line is present
use App\Filament\Clusters\CustomerManagement;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use App\Filament\Clusters\CustomerManagement\Resources\PaymentMethodResource;
use App\Filament\Clusters\CustomerManagement\Resources\PaymentMethodResource\Pages;
use App\Filament\Clusters\CustomerManagement\Resources\PaymentMethodResource\RelationManagers;
use App\Filament\Clusters\CustomerManagement\Resources\PaymentMethodResource\RelationManagers\OrdersRelationManager;


class PaymentMethodResource extends Resource
{
    protected static ?string $model = PaymentMethod::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $cluster = CustomerManagement::class;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Group::make([
                    Section::make('General Information')
                        ->schema([
                            FileUpload::make('image')
                                ->image()
                                ->avatar()
                                ->circleCropper()
                                ->directory('payment_methods')
                                ->columns(2),
                            Forms\Components\TextInput::make('name')
                                ->required()
                                ->maxLength(255),
                            Forms\Components\TextInput::make('notes')
                                ->required()
                                ->maxLength(255),
                        ]),
                ])->columnSpan(2),
                Group::make([
                    Section::make('Payment Method Type')
                        ->schema([
                            Forms\Components\Select::make('type')
                                ->options([
                                    'Nowpayments' => 'NowPayments',
                                    'PayPal' => 'PayPal',
                                    'Bitcoin' => 'Bitcoin',
                                    'Monero' => 'Monero',
                                    'Stripe' => 'Stripe',
                                    'Westwallet' => 'Westwallet',
                                    'Others' => 'Others',
                                    // Add more cryptocurrency types as needed
                                ])
                                ->required()
                                ->maxWidth(255),
                        ])->columns(2),
                    Section::make('Status')
                        ->schema([
                            Forms\Components\Toggle::make('is_active')
                                ->required(),
                        ])->columns(2),
                ])->columnSpan(2),
            ])->columns(4);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->label('Picture'),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('notes')
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
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
                // Add filters as needed
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
            // Define relationships as needed
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPaymentMethods::route('/'),
            'create' => Pages\CreatePaymentMethod::route('/create'),
            'view' => Pages\ViewPaymentMethod::route('/{record}'),
            'edit' => Pages\EditPaymentMethod::route('/{record}/edit'),
        ];
    }
}
