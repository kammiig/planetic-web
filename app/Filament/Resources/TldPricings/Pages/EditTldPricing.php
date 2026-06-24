<?php

namespace App\Filament\Resources\TldPricings\Pages;

use App\Filament\Resources\TldPricings\TldPricingResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTldPricing extends EditRecord
{
    protected static string $resource = TldPricingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
