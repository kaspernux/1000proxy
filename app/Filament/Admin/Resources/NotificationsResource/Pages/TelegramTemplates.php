<?php

namespace App\Filament\Admin\Resources\NotificationsResource\Pages;

use App\Filament\Admin\Resources\NotificationsResource;
use App\Models\TelegramTemplate;
use Filament\Resources\Pages\Page;
use Filament\Tables; 
use Filament\Tables\Table;
use Filament\Forms; 
use Illuminate\Support\Facades\Auth;
use BackedEnum;

class TelegramTemplates extends Page implements Tables\Contracts\HasTable, \Filament\Forms\Contracts\HasForms
{
    use Tables\Concerns\InteractsWithTable;
    use \Filament\Forms\Concerns\InteractsWithForms;

    protected static string $resource = NotificationsResource::class;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $title = 'Telegram Templates';

    protected string $view = 'filament.admin.pages.blank';

    public function table(Table $table): Table
    {
        return $table
            ->query(TelegramTemplate::query())
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('title')->searchable()->wrap(),
                Tables\Columns\TextColumn::make('created_at')->since(),
            ])
            ->headerActions([
                \Filament\Actions\Action::make('create')
                    ->label('New Template')
                    ->icon('heroicon-o-plus')
                    ->form([
                        Forms\Components\TextInput::make('title')->required()->maxLength(150),
                        Forms\Components\Textarea::make('content')->required()->rows(8),
                    ])
                    ->action(function (array $data) {
                        TelegramTemplate::create([
                            'title' => $data['title'],
                            'content' => $data['content'],
                            'created_by' => Auth::id(),
                        ]);
                    }),
            ])
            ->actions([
                \Filament\Actions\Action::make('edit')
                    ->label('Edit')
                    ->icon('heroicon-o-pencil-square')
                    ->form([
                        Forms\Components\TextInput::make('title')->required()->maxLength(150),
                        Forms\Components\Textarea::make('content')->required()->rows(8),
                    ])
                    ->action(function (TelegramTemplate $record, array $data) {
                        $record->update($data);
                    }),
                \Filament\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                \Filament\Actions\DeleteBulkAction::make(),
            ]);
    }
}
