<?php

namespace App\Filament\Clusters\SiteManagement\Resources\GroupResource\Pages;

use App\Filament\Clusters\SiteManagement\Resources\GroupResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateGroup extends CreateRecord
{
    protected static string $resource = GroupResource::class;
}
