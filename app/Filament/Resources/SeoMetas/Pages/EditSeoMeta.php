<?php

namespace App\Filament\Resources\SeoMetas\Pages;

use App\Filament\Resources\SeoMetas\SeoMetaResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSeoMeta extends EditRecord
{
    protected static string $resource = SeoMetaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
