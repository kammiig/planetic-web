<?php

namespace App\Filament\Resources\WebsiteProjects\Pages;

use App\Filament\Resources\WebsiteProjects\WebsiteProjectResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditWebsiteProject extends EditRecord
{
    protected static string $resource = WebsiteProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
