<?php

namespace App\Filament\Resources\WebsiteProjects\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class WebsiteProjectsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->searchable(),
                TextColumn::make('order.id')
                    ->searchable(),
                TextColumn::make('domain.id')
                    ->searchable(),
                TextColumn::make('hostingAccount.id')
                    ->searchable(),
                TextColumn::make('assignedDeveloper.name')
                    ->searchable(),
                TextColumn::make('project_number')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->searchable(),
                TextColumn::make('business_name')
                    ->searchable(),
                TextColumn::make('industry')
                    ->searchable(),
                TextColumn::make('brand_colours')
                    ->searchable(),
                IconColumn::make('content_received')
                    ->boolean(),
                IconColumn::make('logo_received')
                    ->boolean(),
                TextColumn::make('target_launch_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('launched_at')
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
