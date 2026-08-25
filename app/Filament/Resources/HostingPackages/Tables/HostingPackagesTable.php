<?php

namespace App\Filament\Resources\HostingPackages\Tables;

use App\Filament\Support\ProductVisibility;
use App\Models\HostingPackage;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class HostingPackagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('name')
                    ->label('Plan')
                    ->description(fn ($record) => $record->tagline)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('monthly_price')
                    ->label('Monthly')
                    ->money('GBP')
                    ->state(fn ($record) => $record->product?->priceFor('monthly')?->amount),
                TextColumn::make('yearly_price')
                    ->label('Yearly')
                    ->money('GBP')
                    ->state(fn ($record) => $record->product?->priceFor('yearly')?->amount),
                TextColumn::make('whm_package_name')
                    ->label('WHM package')
                    ->badge()
                    ->color('gray')
                    ->searchable(),
                TextColumn::make('disk_limit_mb')
                    ->label('Disk')
                    ->state(fn ($record) => $record->diskLabel())
                    ->sortable(),
                ToggleColumn::make('is_popular')
                    ->label('Popular'),
                ToggleColumn::make('is_active')
                    ->label('Active'),
                // Hidden plans are active and buyable — they simply never
                // appear in the public pricing tables.
                IconColumn::make('product.is_hidden')
                    ->label('Hidden')
                    ->boolean()
                    ->trueIcon('heroicon-o-eye-slash')
                    ->falseIcon('heroicon-o-eye')
                    ->trueColor('warning')
                    ->falseColor('gray')
                    ->tooltip(fn (HostingPackage $record) => $record->product?->is_hidden
                        ? 'Sold by private link only — edit the plan to copy it.'
                        : 'Listed in the public pricing tables.'),
                TextColumn::make('direct_cart_link')
                    ->label('Private link')
                    ->state(fn (HostingPackage $record) => ProductVisibility::directCartLink($record->product))
                    ->placeholder('—')
                    ->copyable()
                    ->copyMessage('Add-to-cart link copied')
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
