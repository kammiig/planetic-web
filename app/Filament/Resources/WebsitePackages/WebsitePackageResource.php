<?php

namespace App\Filament\Resources\WebsitePackages;

use App\Filament\Resources\WebsitePackages\Pages\CreateWebsitePackage;
use App\Filament\Resources\WebsitePackages\Pages\EditWebsitePackage;
use App\Filament\Resources\WebsitePackages\Pages\ListWebsitePackages;
use App\Filament\Resources\WebsitePackages\Schemas\WebsitePackageForm;
use App\Filament\Resources\WebsitePackages\Tables\WebsitePackagesTable;
use App\Models\WebsitePackage;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class WebsitePackageResource extends Resource
{
    protected static ?string $model = WebsitePackage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static string|\UnitEnum|null $navigationGroup = 'Catalogue';

    protected static ?string $navigationLabel = 'Website Packages';

    protected static ?string $modelLabel = 'website package';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return WebsitePackageForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WebsitePackagesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWebsitePackages::route('/'),
            'create' => CreateWebsitePackage::route('/create'),
            'edit' => EditWebsitePackage::route('/{record}/edit'),
        ];
    }
}
