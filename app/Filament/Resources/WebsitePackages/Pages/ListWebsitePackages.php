<?php

namespace App\Filament\Resources\WebsitePackages\Pages;

use App\Filament\Resources\WebsitePackages\WebsitePackageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWebsitePackages extends ListRecords
{
    protected static string $resource = WebsitePackageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
