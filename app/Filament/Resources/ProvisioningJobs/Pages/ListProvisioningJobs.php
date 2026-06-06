<?php

namespace App\Filament\Resources\ProvisioningJobs\Pages;

use App\Filament\Resources\ProvisioningJobs\ProvisioningJobResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProvisioningJobs extends ListRecords
{
    protected static string $resource = ProvisioningJobResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
