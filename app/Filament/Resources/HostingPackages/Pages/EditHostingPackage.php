<?php

namespace App\Filament\Resources\HostingPackages\Pages;

use App\Filament\Resources\HostingPackages\HostingPackageResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditHostingPackage extends EditRecord
{
    protected static string $resource = HostingPackageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
