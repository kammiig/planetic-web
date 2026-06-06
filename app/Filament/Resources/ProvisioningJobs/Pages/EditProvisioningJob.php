<?php

namespace App\Filament\Resources\ProvisioningJobs\Pages;

use App\Filament\Resources\ProvisioningJobs\ProvisioningJobResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditProvisioningJob extends EditRecord
{
    protected static string $resource = ProvisioningJobResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
