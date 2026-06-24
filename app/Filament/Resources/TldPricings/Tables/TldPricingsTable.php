<?php

namespace App\Filament\Resources\TldPricings\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class TldPricingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('tld')
                    ->label('TLD')
                    ->formatStateUsing(fn ($state) => '.'.ltrim((string) $state, '.'))
                    ->weight('bold')
                    ->searchable(),
                TextColumn::make('register_price')->label('Register')->money('GBP')->sortable(),
                TextColumn::make('renew_price')->label('Renew')->money('GBP')->toggleable(),
                TextColumn::make('cost_price')->label('Cost')->money('GBP')->toggleable()->placeholder('—'),
                TextColumn::make('markup')->label('Margin')->money('GBP')->toggleable()->placeholder('—'),
                IconColumn::make('free_eligible')->label('Free?')->boolean(),
                ToggleColumn::make('is_featured')->label('Featured'),
                ToggleColumn::make('is_active')->label('Active'),
                TextColumn::make('cost_synced_at')->label('Cost synced')->since()->placeholder('Never')->toggleable(isToggledHiddenByDefault: true),
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
