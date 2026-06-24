<?php

namespace App\Filament\Resources\WebsitePackages\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class WebsitePackagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('name')
                    ->label('Package')
                    ->description(fn ($record) => $record->tagline)
                    ->searchable(),
                TextColumn::make('price')
                    ->label('Price')
                    ->money('GBP')
                    ->state(fn ($record) => $record->product?->priceFor('one_time')?->amount),
                IconColumn::make('includes_free_domain')->label('Free domain')->boolean(),
                IconColumn::make('includes_hosting')->label('Hosting')->boolean(),
                TextColumn::make('hostingPackage.name')->label('Hosting plan')->placeholder('—'),
                ToggleColumn::make('is_active')->label('Active'),
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
