<?php

namespace App\Filament\Clusters\ServerManagement\Resources;

use App\Filament\Clusters\ServerManagement;
use App\Filament\Clusters\ServerManagement\Resources\InboundClientIPResource\Pages;
use App\Filament\Clusters\ServerManagement\Resources\InboundClientIPResource\RelationManagers;
use App\Models\InboundClientIP;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class InboundClientIPResource extends Resource
{
    protected static ?string $model = InboundClientIP::class;

    protected static ?string $navigationIcon = 'heroicon-o-rss';

    protected static ?string $cluster = ServerManagement::class;

    public static function getLabel(): string
    {
        return 'IPs clients';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make([
                    Forms\Components\Section::make('IP address')
                        ->schema([
                            Forms\Components\TextInput::make('client_email')
                                ->email()
                                ->required()
                                ->maxLength(255),
                            Forms\Components\TextInput::make('ips')
                                ->required()
                                ->maxLength(255),
                        ]),
                    ])->columnSpan(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('client_email')
                    ->label('Email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('ips')
                    ->label('IP Address')
                    ->searchable(),
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
            'index' => Pages\ListInboundClientIPS::route('/'),
            'create' => Pages\CreateInboundClientIP::route('/create'),
            'view' => Pages\ViewInboundClientIP::route('/{record}'),
            'edit' => Pages\EditInboundClientIP::route('/{record}/edit'),
        ];
    }
}