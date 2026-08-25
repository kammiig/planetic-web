<?php

namespace App\Filament\Resources\WebsitePackages\Pages;

use App\Filament\Concerns\SyncsProductPrices;
use App\Filament\Concerns\SyncsProductVisibility;
use App\Filament\Resources\WebsitePackages\WebsitePackageResource;
use Filament\Resources\Pages\CreateRecord;

class CreateWebsitePackage extends CreateRecord
{
    use SyncsProductPrices;
    use SyncsProductVisibility;

    protected static string $resource = WebsitePackageResource::class;

    protected function afterCreate(): void
    {
        $product = $this->record->loadMissing('product')->product;

        $this->syncPrices($product);
        $this->syncVisibility($product);
    }
}
