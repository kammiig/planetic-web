<?php

namespace App\Filament\Resources\CloudflareZones\Pages;

use App\Filament\Resources\CloudflareZones\CloudflareZoneResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCloudflareZone extends CreateRecord
{
    protected static string $resource = CloudflareZoneResource::class;
}
