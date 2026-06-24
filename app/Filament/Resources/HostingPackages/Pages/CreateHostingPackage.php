<?php

namespace App\Filament\Resources\HostingPackages\Pages;

use App\Filament\Concerns\SyncsProductPrices;
use App\Filament\Resources\HostingPackages\HostingPackageResource;
use Filament\Resources\Pages\CreateRecord;

class CreateHostingPackage extends CreateRecord
{
    use SyncsProductPrices;

    protected static string $resource = HostingPackageResource::class;

    protected function afterCreate(): void
    {
        $this->syncPrices($this->record->loadMissing('product')->product);
    }
}
