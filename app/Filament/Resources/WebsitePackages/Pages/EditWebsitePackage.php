<?php

namespace App\Filament\Resources\WebsitePackages\Pages;

use App\Filament\Concerns\SyncsProductPrices;
use App\Filament\Concerns\SyncsProductVisibility;
use App\Filament\Resources\WebsitePackages\WebsitePackageResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditWebsitePackage extends EditRecord
{
    use SyncsProductPrices;
    use SyncsProductVisibility;

    protected static string $resource = WebsitePackageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return $this->fillVisibilityData(
            $this->fillPriceData($data, $this->record->product),
            $this->record->product,
        );
    }

    protected function afterSave(): void
    {
        $product = $this->record->loadMissing('product')->product;

        $this->syncPrices($product);
        $this->syncVisibility($product);
    }
}
