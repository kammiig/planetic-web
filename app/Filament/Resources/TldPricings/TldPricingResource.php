<?php

namespace App\Filament\Resources\TldPricings;

use App\Filament\Resources\TldPricings\Pages\CreateTldPricing;
use App\Filament\Resources\TldPricings\Pages\EditTldPricing;
use App\Filament\Resources\TldPricings\Pages\ListTldPricings;
use App\Filament\Resources\TldPricings\Schemas\TldPricingForm;
use App\Filament\Resources\TldPricings\Tables\TldPricingsTable;
use App\Models\TldPricing;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TldPricingResource extends Resource
{
    protected static ?string $model = TldPricing::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAlt;

    protected static string|\UnitEnum|null $navigationGroup = 'Catalogue';

    protected static ?string $navigationLabel = 'Domain Pricing';

    protected static ?string $modelLabel = 'TLD price';

    protected static ?string $pluralModelLabel = 'domain pricing';

    protected static ?string $recordTitleAttribute = 'tld';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return TldPricingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TldPricingsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTldPricings::route('/'),
            'create' => CreateTldPricing::route('/create'),
            'edit' => EditTldPricing::route('/{record}/edit'),
        ];
    }
}
