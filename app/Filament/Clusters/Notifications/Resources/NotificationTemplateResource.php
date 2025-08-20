<?php

namespace App\Filament\Clusters\Notifications\Resources;

use UnitEnum;
use BackedEnum;
use App\Models\NotificationTemplate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Auth;

class NotificationTemplateResource extends Resource
{
    protected static ?string $model = NotificationTemplate::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-bell';
    protected static ?string $navigationLabel = 'Notification Templates';
    protected static UnitEnum|string|null $navigationGroup = 'Notifications';

    public static function canViewAny(): bool
    {
        $user = Auth::user();
        return $user && in_array($user->role, ['admin','support_manager']);
    }

    public static function canCreate(): bool
    {
        $user = Auth::user();
        return $user && in_array($user->role, ['admin','support_manager']);
    }

    public static function canEdit($record): bool
    {
        $user = Auth::user();
        return $user && in_array($user->role, ['admin','support_manager']);
    }

    public static function canDelete($record): bool
    {
        $user = Auth::user();
        return $user && in_array($user->role, ['admin']);
    }

    public static function canDeleteAny(): bool
    {
        $user = Auth::user();
        return $user && in_array($user->role, ['admin']);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            \Filament\Schemas\Components\Grid::make(2)->schema([
                Forms\Components\TextInput::make('key')->required()->maxLength(100)->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('name')->required()->maxLength(150),
                Forms\Components\Select::make('channel')->options([
                    'telegram' => 'Telegram',
                    'email' => 'Email',
                    'sms' => 'SMS',
                    'system' => 'System',
                ])->required()->default('telegram'),
                Forms\Components\TextInput::make('locale')->label('Locale (2 letters)')->required()->maxLength(8)->default('en'),
                Forms\Components\TextInput::make('subject')->maxLength(200)->visible(fn ($get) => $get('channel') === 'email'),
                Forms\Components\Toggle::make('enabled')->default(true),
            ]),
            Forms\Components\Textarea::make('body')->label('Body')->rows(10)->columnSpanFull()->helperText('Telegram: simple HTML only. Email: Markdown/HTML allowed.'),
            Forms\Components\Textarea::make('notes')->label('Notes')->rows(3)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('key')->searchable()->sortable(),
                Tables\Columns\BadgeColumn::make('channel')->colors([
                    'primary' => 'telegram',
                    'success' => 'email',
                    'warning' => 'sms',
                    'gray' => 'system',
                ]),
                Tables\Columns\TextColumn::make('locale')->sortable(),
                Tables\Columns\IconColumn::make('enabled')->boolean(),
                Tables\Columns\TextColumn::make('updated_at')->dateTime()->since(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('channel')->options([
                    'telegram' => 'Telegram',
                    'email' => 'Email',
                    'sms' => 'SMS',
                    'system' => 'System',
                ]),
                Tables\Filters\TrashedFilter::make()->hidden(),
            ])
            ->actions([
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => NotificationTemplateResource\Pages\ListNotificationTemplates::route('/'),
            'create' => NotificationTemplateResource\Pages\CreateNotificationTemplate::route('/create'),
            'edit' => NotificationTemplateResource\Pages\EditNotificationTemplate::route('/{record}/edit'),
        ];
    }
}
