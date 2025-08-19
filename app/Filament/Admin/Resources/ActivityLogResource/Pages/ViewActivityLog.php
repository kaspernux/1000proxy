<?php

namespace App\Filament\Admin\Resources\ActivityLogResource\Pages;

use App\Filament\Admin\Resources\ActivityLogResource;
use Filament\Resources\Pages\ViewRecord;

class ViewActivityLog extends ViewRecord
{
    protected static string $resource = ActivityLogResource::class;

    public function mount(int|string $record): void
    {
        if (!auth()->user()?->isAdmin()) {
            abort(403);
        }
        parent::mount($record);
    }
}
