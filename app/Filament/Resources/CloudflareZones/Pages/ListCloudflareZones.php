<?php

namespace App\Filament\Resources\CloudflareZones\Pages;

use App\Filament\Resources\CloudflareZones\CloudflareZoneResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCloudflareZones extends ListRecords
{
    protected static string $resource = CloudflareZoneResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
