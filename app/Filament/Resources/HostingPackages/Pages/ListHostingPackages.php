<?php

namespace App\Filament\Resources\HostingPackages\Pages;

use App\Filament\Resources\HostingPackages\HostingPackageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListHostingPackages extends ListRecords
{
    protected static string $resource = HostingPackageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
