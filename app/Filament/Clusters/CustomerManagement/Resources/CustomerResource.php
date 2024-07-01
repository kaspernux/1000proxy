<?php

namespace App\Filament\Clusters\CustomerManagement\Resources;
use App\Filament\Clusters\CustomerManagement\Resources\CustomerResource\RelationManagers\OrdersRelationManager;
use App\Filament\Clusters\CustomerManagement;
use App\Filament\Clusters\CustomerManagement\Resources\CustomerResource\Pages;
use App\Filament\Clusters\CustomerManagement\Resources\CustomerResource\RelationManagers;
use App\Models\Customer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Group;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Contracts\Support\Htmlable;
use App\Models\PaymentMethod;


class CustomerResource extends Resource
    {
    protected static ?string $model = Customer::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $cluster = CustomerManagement::class;

    protected static ?string $recordTitleAttribute = 'name';
    public static function getLabel(): string
    {
        return 'Users';
    }
    public static function form(Form $form): Form
        {
        return $form
            ->schema([
                Group::make([
                    Section::make('User details')
                        ->schema([
                            Forms\Components\TextInput::make('name')
                                ->required()
                                ->maxLength(255),
                            Forms\Components\TextInput::make('telegram_id')
                                ->tel()
                                ->maxLength(255),
                            Forms\Components\TextInput::make('email')
                                ->email()
                                ->required()
                                ->maxLength(255),
                        ]),
                    Section::make('Additional Information')
                        ->schema([
                            Forms\Components\TextInput::make('refcode')
                                ->required()
                                ->maxLength(50),
                            Forms\Components\TextInput::make('wallet')
                                ->required()
                                ->numeric()
                                ->default(0),
                            Forms\Components\DateTimePicker::make('date')
                                ->required()
                                ->maxWidth(255),
                            Forms\Components\TextInput::make('phone')
                                ->tel()
                                ->maxLength(15),
                            Forms\Components\TextInput::make('refered_by')
                                ->numeric(),
                        ])->columns(2),
                    Section::make('Agent Information')
                        ->schema([
                            Forms\Components\TextInput::make('is_agent')
                                ->required()
                                ->numeric()
                                ->default(0),
                            Forms\Components\TextInput::make('discount_percent')
                                ->maxLength(1000),
                            Forms\Components\TextInput::make('agent_date')
                                ->required()
                                ->numeric()
                                ->default(0),
                       ])->columns(2),
                ])->columnSpan(2),

                Group::make([
                    Section::make('User Picture')
                        ->schema([
                            Forms\Components\FileUpload::make('image')
                                ->image()
                                ->columnSpan(2),
                        ]),
                    Section::make('Status Information')
                        ->schema([
                            Forms\Components\TextInput::make('step')
                                ->required()
                                ->maxLength(1000)
                                ->default('none'),
                            Forms\Components\TextInput::make('freetrial')
                                ->maxLength(10),
                            Forms\Components\DateTimePicker::make('first_start')
                                ->maxWidth(255),
                            Forms\Components\Toggle::make('is_active')
                                ->required(),
                        ])->columns(2),
                    Section::make('Miscellaneous')
                        ->schema([
                            Forms\Components\Textarea::make('temp')
                                ->columnSpanFull(),
                            Forms\Components\TextInput::make('spam_info')
                                ->maxLength(500),
                        ]),
                ]),
            ])->columns(3);
        }

    public static function table(Table $table): Table
        {
        return $table
            ->columns([
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),
                Tables\Columns\ImageColumn::make('image')
                    ->label('Picture'),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->label('Email'),
                Tables\Columns\TextColumn::make('telegram_id')
                    ->searchable()
                    ->label('Telegram'),
                Tables\Columns\TextColumn::make('wallet')
                    ->label('Wallet')
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
            RelationManagers\OrdersRelationManager::class
        ];
        }


    public static function getPages(): array
        {
        return [
            'index' => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'view' => Pages\ViewCustomer::route('/{record}'),
            'edit' => Pages\EditCustomer::route('/{record}/edit'),
        ];
       }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'email'];
    }

    public static function getNavigationBadge(): ?string {
        return static::getModel()::count();
    }
    public static function getNavigationBadgeColor(): string|array|null {
        return static::getModel()::count() > 10 ? 'success':'danger';
    }
}
