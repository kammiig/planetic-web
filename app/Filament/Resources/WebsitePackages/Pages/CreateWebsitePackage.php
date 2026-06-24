<?php

namespace App\Filament\Resources\WebsitePackages\Pages;

use App\Filament\Concerns\SyncsProductPrices;
use App\Filament\Resources\WebsitePackages\WebsitePackageResource;
use Filament\Resources\Pages\CreateRecord;

class CreateWebsitePackage extends CreateRecord
{
    use SyncsProductPrices;

    protected static string $resource = WebsitePackageResource::class;

    protected function afterCreate(): void
    {
        $this->syncPrices($this->record->loadMissing('product')->product);
    }
}
