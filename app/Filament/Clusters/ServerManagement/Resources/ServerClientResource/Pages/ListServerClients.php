<?php

namespace App\Filament\Clusters\ServerManagement\Resources\ServerClientResource\Pages;

use App\Filament\Clusters\ServerManagement\Resources\ServerClientResource;
use Filament\Resources\Pages\ListRecords;
use App\Models\ServerClient;
use Filament\Actions;


class ListServerClients extends ListRecords
{
    protected static string $resource = ServerClientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getTableQuery(): ?\Illuminate\Database\Eloquent\Builder
    {
        // Return a query builder instance for the ServerInbound model
        return ServerClient::query();
    }
}


