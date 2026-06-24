<?php

namespace App\Filament\Resources\WebsitePackages\Pages;

use App\Filament\Concerns\SyncsProductPrices;
use App\Filament\Resources\WebsitePackages\WebsitePackageResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditWebsitePackage extends EditRecord
{
    use SyncsProductPrices;

    protected static string $resource = WebsitePackageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return $this->fillPriceData($data, $this->record->product);
    }

    protected function afterSave(): void
    {
        $this->syncPrices($this->record->loadMissing('product')->product);
    }
}
