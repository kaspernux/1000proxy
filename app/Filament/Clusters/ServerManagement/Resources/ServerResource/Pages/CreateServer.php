<?php

namespace App\Filament\Clusters\ServerManagement\Resources\ServerResource\Pages;

use App\Filament\Clusters\ServerManagement\Resources\ServerResource;
use Filament\Resources\Pages\CreateRecord;
use App\Models\Server;
use Illuminate\Support\Facades\DB;

class CreateServer extends CreateRecord
{
    protected static string $resource = ServerResource::class;

    protected function handleRecordCreation(array $data): Server
    {
        return Server::create($data);
    }
}