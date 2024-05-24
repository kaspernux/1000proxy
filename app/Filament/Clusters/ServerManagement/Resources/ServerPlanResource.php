<?php

namespace App\Filament\Clusters\ServerManagement\Resources;

use App\Filament\Clusters\ServerManagement;
use App\Filament\Clusters\ServerManagement\Resources\ServerPlanResource\Pages;
use App\Filament\Clusters\ServerManagement\Resources\ServerPlanResource\RelationManagers;
use App\Models\ServerPlan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ServerPlanResource extends Resource
{
    protected static ?string $model = ServerPlan::class;

    protected static ?string $navigationIcon = 'heroicon-o-inbox-stack';

    protected static ?string $cluster = ServerManagement::class;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('server_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('server_inbound_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('category_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('fileid')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('acount')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('limitip')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('protocol')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('days')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('volume')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('type')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('price')
                    ->required()
                    ->numeric()
                    ->prefix('$'),
                Forms\Components\Textarea::make('descr')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('pic')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Toggle::make('active')
                    ->required(),
                Forms\Components\TextInput::make('step')
                    ->required()
                    ->numeric(),
                Forms\Components\DateTimePicker::make('date'),
                Forms\Components\TextInput::make('rahgozar')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('dest')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('serverNames')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('spiderX')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('flow')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('custom_path')
                    ->maxLength(255),
                Forms\Components\TextInput::make('custom_port')
                    ->numeric(),
                Forms\Components\TextInput::make('custom_sni')
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('server_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('server_inbound_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('category_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('fileid')
                    ->searchable(),
                Tables\Columns\TextColumn::make('acount')
                    ->searchable(),
                Tables\Columns\TextColumn::make('limitip')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('title')
                    ->searchable(),
                Tables\Columns\TextColumn::make('protocol')
                    ->searchable(),
                Tables\Columns\TextColumn::make('days')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('volume')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('price')
                    ->money()
                    ->sortable(),
                Tables\Columns\TextColumn::make('pic')
                    ->searchable(),
                Tables\Columns\IconColumn::make('active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('step')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('date')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('rahgozar')
                    ->searchable(),
                Tables\Columns\TextColumn::make('dest')
                    ->searchable(),
                Tables\Columns\TextColumn::make('serverNames')
                    ->searchable(),
                Tables\Columns\TextColumn::make('spiderX')
                    ->searchable(),
                Tables\Columns\TextColumn::make('flow')
                    ->searchable(),
                Tables\Columns\TextColumn::make('custom_path')
                    ->searchable(),
                Tables\Columns\TextColumn::make('custom_port')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('custom_sni')
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
            'index' => Pages\ListServerPlans::route('/'),
            'create' => Pages\CreateServerPlan::route('/create'),
            'view' => Pages\ViewServerPlan::route('/{record}'),
            'edit' => Pages\EditServerPlan::route('/{record}/edit'),
        ];
    }
}
