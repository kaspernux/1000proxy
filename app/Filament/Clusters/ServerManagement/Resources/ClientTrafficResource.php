<?php

namespace App\Filament\Clusters\ServerManagement\Resources;

use App\Filament\Clusters\ServerManagement;
use App\Filament\Clusters\ServerManagement\Resources\ClientTrafficResource\Pages;
use App\Filament\Clusters\ServerManagement\Resources\ClientTrafficResource\RelationManagers;
use App\Models\ClientTraffic;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

use Illuminate\Support\Str;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Group;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Filament\Forms\Components\FileUpload;

class ClientTrafficResource extends Resource
{
    protected static ?string $model = ClientTraffic::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-pie';

    protected static ?string $cluster = ServerManagement::class;

    public static function getLabel(): string
    {
        return 'User Traffic';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make([
                    Forms\Components\Section::make('Inbound Details')
                        ->schema([
                            Forms\Components\Select::make('server_inbound_id')
                                ->relationship('serverInbound', 'remark')
                                ->required()
                                ->searchable()
                                ->preload(),
                            Forms\Components\Select::make('customer_id')
                                ->relationship('customer', 'name')
                                ->required()
                                ->searchable()
                                ->preload(),
                            Forms\Components\DatePicker::make('expiryTime')
                                ->required(),
                            Forms\Components\Toggle::make('enable')
                                ->required(),
                        ])
                ])->columnSpan(1),

                Forms\Components\Group::make([
                    Forms\Components\Section::make('Traffic Info')
                        ->schema([
                            Forms\Components\TextInput::make('email')
                                ->required()
                                ->maxLength(255),
                            Forms\Components\TextInput::make('up')
                                ->required()
                                ->numeric(),
                            Forms\Components\TextInput::make('down')
                                ->required()
                                ->numeric(),
                            Forms\Components\TextInput::make('total')
                                ->required()
                                ->numeric(),
                            Forms\Components\TextInput::make('reset')
                                ->required()
                                ->numeric(),

                        ])->columns(2)
                ])->columnSpan(2),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Customer')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('server_inbound_id')
                    ->label('Inbound ID')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('enable')
                    ->label('Active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('up')
                    ->label('UP')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('down')
                    ->label('DOWN')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('expiryTime')
                    ->label('Expiration')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total')
                    ->label('TOTAL')
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
            'index' => Pages\ListClientTraffic::route('/'),
            'create' => Pages\CreateClientTraffic::route('/create'),
            'view' => Pages\ViewClientTraffic::route('/{record}'),
            'edit' => Pages\EditClientTraffic::route('/{record}/edit'),
        ];

   }
}
