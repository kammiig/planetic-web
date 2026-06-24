<?php

namespace App\Filament\Resources\SeoMetas\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SeoMetasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('page_key')->label('Route')->badge()->color('gray')->searchable(),
                TextColumn::make('label')->label('Page')->searchable()->placeholder('—'),
                TextColumn::make('meta_title')->label('Title')->limit(50)->searchable(),
                IconColumn::make('noindex')->label('No-index')->boolean(),
                TextColumn::make('updated_at')->since()->toggleable(),
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
