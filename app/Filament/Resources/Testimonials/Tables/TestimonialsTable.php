<?php

namespace App\Filament\Resources\Testimonials\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class TestimonialsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->reorderable('sort_order')
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('author_name')->label('Author')->description(fn ($record) => trim(($record->author_role ? $record->author_role.', ' : '').$record->company, ', '))->searchable(),
                TextColumn::make('rating')->formatStateUsing(fn ($state) => str_repeat('★', (int) $state)),
                TextColumn::make('source')->label('Source')->badge(),
                IconColumn::make('is_verified')->label('Verified')->boolean(),
                TextColumn::make('body')->label('Quote')->limit(60)->wrap(),
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
