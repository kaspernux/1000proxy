<?php
namespace App\Filament\Customer\Clusters\MyServices\Resources\ServerInfoResource\Pages;

use App\Filament\Customer\Clusters\MyServices\Resources\ServerInfoResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateServerInfo extends CreateRecord
{
    protected static string $resource = ServerInfoResource::class;
}
