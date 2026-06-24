<?php

namespace App\Filament\Resources\HostingPackages;

use App\Filament\Resources\HostingPackages\Pages\CreateHostingPackage;
use App\Filament\Resources\HostingPackages\Pages\EditHostingPackage;
use App\Filament\Resources\HostingPackages\Pages\ListHostingPackages;
use App\Filament\Resources\HostingPackages\Schemas\HostingPackageForm;
use App\Filament\Resources\HostingPackages\Tables\HostingPackagesTable;
use App\Models\HostingPackage;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class HostingPackageResource extends Resource
{
    protected static ?string $model = HostingPackage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedServerStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Catalogue';

    protected static ?string $navigationLabel = 'Hosting Plans';

    protected static ?string $modelLabel = 'hosting plan';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return HostingPackageForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return HostingPackagesTable::configure($table);
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
            'index' => ListHostingPackages::route('/'),
            'create' => CreateHostingPackage::route('/create'),
            'edit' => EditHostingPackage::route('/{record}/edit'),
        ];
    }
}
