<?php

namespace App\Filament\Resources\HostingPackages\Pages;

use App\Filament\Concerns\SyncsProductPrices;
use App\Filament\Concerns\SyncsProductVisibility;
use App\Filament\Resources\HostingPackages\HostingPackageResource;
use Filament\Resources\Pages\CreateRecord;

class CreateHostingPackage extends CreateRecord
{
    use SyncsProductPrices;
    use SyncsProductVisibility;

    protected static string $resource = HostingPackageResource::class;

    protected function afterCreate(): void
    {
        $product = $this->record->loadMissing('product')->product;

        $this->syncPrices($product);
        $this->syncVisibility($product);
    }
}
