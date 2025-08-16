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

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Support legacy field names used in tests by mapping them to model attributes
        if (array_key_exists('location', $data) && !array_key_exists('country', $data)) {
            $data['country'] = $data['location'];
        }
        if (array_key_exists('ip_address', $data) && !array_key_exists('ip', $data)) {
            $data['ip'] = $data['ip_address'];
        }
        if (array_key_exists('panel_username', $data) && !array_key_exists('username', $data)) {
            $data['username'] = $data['panel_username'];
        }
        if (array_key_exists('panel_password', $data) && !array_key_exists('password', $data)) {
            $data['password'] = $data['panel_password'];
        }

        return $data;
    }
}