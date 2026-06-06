<?php

namespace App\Filament\Resources\HostingPackages\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class HostingPackagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('product.name')
                    ->searchable(),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('whm_package_name')
                    ->searchable(),
                TextColumn::make('disk_limit_mb')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('bandwidth_limit_mb')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('email_accounts_limit')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('database_limit')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('domain_limit')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->boolean(),
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
