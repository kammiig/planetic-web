<?php

namespace App\Filament\Resources\HostingPackages\Pages;

use App\Filament\Concerns\SyncsProductPrices;
use App\Filament\Resources\HostingPackages\HostingPackageResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditHostingPackage extends EditRecord
{
    use SyncsProductPrices;

    protected static string $resource = HostingPackageResource::class;

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
