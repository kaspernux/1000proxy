<?php

namespace App\Filament\Admin\Widgets;

use App\Models\FailedJob;
use Filament\Tables;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentFailedJobs extends BaseWidget
{
    protected static ?int $sort = 11;
    protected static ?string $heading = 'Recent Failed Jobs';

    protected function getTableQuery(): Builder
    {
        // Filter by telegram queue when stored in payload; otherwise show last 10 globally
        $q = FailedJob::query();
        // Attempt to match payload containing queue name
        $q->where(function($w){
            $w->where('payload', 'like', '%"queue":"telegram%')
              ->orWhere('exception', 'like', '%telegram%');
        });
        return $q->latest('failed_at')->limit(10);
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('id')->label('#')->sortable(),
            Tables\Columns\TextColumn::make('connection')->label('Conn')->sortable()->toggleable(),
            Tables\Columns\TextColumn::make('queue')->label('Queue')->sortable(),
            Tables\Columns\TextColumn::make('exception')->label('Error')->limit(80)->wrap(),
            Tables\Columns\TextColumn::make('failed_at')->label('Failed')->since(),
        ];
    }
}
