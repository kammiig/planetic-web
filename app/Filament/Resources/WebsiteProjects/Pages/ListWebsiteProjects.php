<?php

namespace App\Filament\Resources\WebsiteProjects\Pages;

use App\Filament\Resources\WebsiteProjects\WebsiteProjectResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWebsiteProjects extends ListRecords
{
    protected static string $resource = WebsiteProjectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
