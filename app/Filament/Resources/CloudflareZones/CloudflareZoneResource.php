<?php

namespace App\Filament\Resources\CloudflareZones;

use App\Filament\Resources\CloudflareZones\Pages\CreateCloudflareZone;
use App\Filament\Resources\CloudflareZones\Pages\EditCloudflareZone;
use App\Filament\Resources\CloudflareZones\Pages\ListCloudflareZones;
use App\Filament\Resources\CloudflareZones\Schemas\CloudflareZoneForm;
use App\Filament\Resources\CloudflareZones\Tables\CloudflareZonesTable;
use App\Models\CloudflareZone;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CloudflareZoneResource extends Resource
{
    protected static ?string $model = CloudflareZone::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Services';

    public static function form(Schema $schema): Schema
    {
        return CloudflareZoneForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CloudflareZonesTable::configure($table);
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
            'index' => ListCloudflareZones::route('/'),
            'create' => CreateCloudflareZone::route('/create'),
            'edit' => EditCloudflareZone::route('/{record}/edit'),
        ];
    }
}
