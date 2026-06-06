<?php

namespace App\Filament\Resources\CloudflareZones\Pages;

use App\Filament\Resources\CloudflareZones\CloudflareZoneResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCloudflareZone extends EditRecord
{
    protected static string $resource = CloudflareZoneResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
