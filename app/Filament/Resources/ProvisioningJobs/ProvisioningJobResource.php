<?php

namespace App\Filament\Resources\ProvisioningJobs;

use App\Filament\Resources\ProvisioningJobs\Pages\CreateProvisioningJob;
use App\Filament\Resources\ProvisioningJobs\Pages\EditProvisioningJob;
use App\Filament\Resources\ProvisioningJobs\Pages\ListProvisioningJobs;
use App\Filament\Resources\ProvisioningJobs\Schemas\ProvisioningJobForm;
use App\Filament\Resources\ProvisioningJobs\Tables\ProvisioningJobsTable;
use App\Models\ProvisioningJob;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ProvisioningJobResource extends Resource
{
    protected static ?string $model = ProvisioningJob::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'System';

    public static function form(Schema $schema): Schema
    {
        return ProvisioningJobForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProvisioningJobsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProvisioningJobs::route('/'),
            'create' => CreateProvisioningJob::route('/create'),
            'edit' => EditProvisioningJob::route('/{record}/edit'),
        ];
    }
}
