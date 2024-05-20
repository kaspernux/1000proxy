<?php

namespace App\Filament\Clusters\SiteManagement\Resources\UserResource\Pages;

use App\Filament\Clusters\SiteManagement\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;
}
