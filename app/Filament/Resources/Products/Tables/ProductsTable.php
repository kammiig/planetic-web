<?php

namespace App\Filament\Resources\Products\Tables;

use App\Filament\Support\ProductVisibility;
use App\Models\Product;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('slug')
                    ->searchable(),
                TextColumn::make('type')
                    ->badge()
                    ->searchable(),
                IconColumn::make('is_active')
                    ->boolean(),
                IconColumn::make('is_hidden')
                    ->label('Hidden')
                    ->boolean()
                    ->trueIcon('heroicon-o-eye-slash')
                    ->falseIcon('heroicon-o-eye')
                    ->trueColor('warning')
                    ->falseColor('gray')
                    ->tooltip(fn (Product $record) => $record->is_hidden
                        ? 'Withheld from the public pricing tables — sell it with the direct link.'
                        : 'Listed in the public pricing tables.'),
                // The private sales link for this plan. Copy it and send it to
                // the customer who should get it; it works whether or not the
                // product is hidden.
                TextColumn::make('direct_link')
                    ->label('Add-to-cart link')
                    ->state(fn (Product $record) => ProductVisibility::directCartLink($record))
                    ->placeholder('—')
                    ->copyable()
                    ->copyMessage('Add-to-cart link copied')
                    ->limit(46)
                    ->toggleable(),
                TextColumn::make('sort_order')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_hidden')
                    ->label('Visibility')
                    ->placeholder('All products')
                    ->trueLabel('Hidden from pricing tables')
                    ->falseLabel('Listed publicly'),
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
