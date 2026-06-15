<?php

namespace App\Filament\Resources\WebsiteProjects;

use App\Filament\Resources\WebsiteProjects\Pages\CreateWebsiteProject;
use App\Filament\Resources\WebsiteProjects\Pages\EditWebsiteProject;
use App\Filament\Resources\WebsiteProjects\Pages\ListWebsiteProjects;
use App\Filament\Resources\WebsiteProjects\Schemas\WebsiteProjectForm;
use App\Filament\Resources\WebsiteProjects\Tables\WebsiteProjectsTable;
use App\Models\WebsiteProject;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class WebsiteProjectResource extends Resource
{
    protected static ?string $model = WebsiteProject::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Projects & Support';

    public static function form(Schema $schema): Schema
    {
        return WebsiteProjectForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WebsiteProjectsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Resources\WebsiteProjects\RelationManagers\MessagesRelationManager::class,
            \App\Filament\Resources\WebsiteProjects\RelationManagers\MeetingsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWebsiteProjects::route('/'),
            'create' => CreateWebsiteProject::route('/create'),
            'edit' => EditWebsiteProject::route('/{record}/edit'),
        ];
    }
}
