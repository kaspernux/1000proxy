<?php

namespace App\Filament\Clusters\CustomerManagement\Resources;
use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Get;
use Filament\Forms\Set;
use App\Models\Customer;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Filament\Resources\Resource;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Support\Htmlable;
use App\Filament\Clusters\CustomerManagement;
use Filament\Forms\Components\MarkdownEditor;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\PaymentMethod; // Ensure this line is present
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
                            TextInput::make('name')
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (string $operation, $state, Set $set) {
                                        if ($operation === 'create') {
                                            $set('slug', Str::slug($state));
                                        }
                                    }),
                            TextInput::make('slug')
                                ->required()
                                ->disabled()
                                ->unique(PaymentMethod::class, 'slug', ignoreRecord: true),
                        ])->columns(2),
                    Section::make('Media')
                        ->schema([
                            Forms\Components\Select::make('type')
                                ->options([
                                    'wallet' => 'Wallet',
                                    'nowpayments' => 'NowPayments',
                                    'stripe' => 'Stripe',
                                    'paypal' => 'PayPal',
                                    'bitcoin' => 'Bitcoin',
                                    'monero' => 'Monero',
                                    'giftcard' => 'Giftcard',
                                    'mir' => 'MIR',
                                    'visa' => 'VISA/MSC',
                                    'Others' => 'Others',
                                    // Add more cryptocurrency types as needed
                                ])
                                ->required()
                                ->maxWidth(255),
                            Forms\Components\TextInput::make('notes')
                                ->required()
                                ->maxLength(255),
                            Forms\Components\Toggle::make('is_active')
                                    ->label('Status')
                                    ->required()
                                    ->columnSpanFull(),
                        ])->columns(2),
                ])->columnSpan(2),
                Group::make([
                    Section::make('Logo')
                        ->schema([
                            FileUpload::make('image')
                                ->image()
                                ->directory('payment_methods'),
                        ]),
                ])->columnSpan(1),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->label('Picture'),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('slug')
                    ->label('Slug')
                    ->sortable()
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
