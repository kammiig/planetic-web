<?php

namespace App\Filament\Resources\HostingPackages\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
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
