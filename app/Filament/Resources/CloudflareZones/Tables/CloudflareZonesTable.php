<?php

namespace App\Filament\Resources\CloudflareZones\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CloudflareZonesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->searchable(),
                TextColumn::make('domain.id')
                    ->searchable(),
                TextColumn::make('zone_id')
                    ->searchable(),
                TextColumn::make('zone_name')
                    ->searchable(),
                TextColumn::make('status')
                    ->searchable(),
                TextColumn::make('ssl_mode')
                    ->searchable(),
                IconColumn::make('always_use_https')
                    ->boolean(),
                TextColumn::make('created_on_cloudflare_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('last_synced_at')
                    ->dateTime()
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
                //
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
