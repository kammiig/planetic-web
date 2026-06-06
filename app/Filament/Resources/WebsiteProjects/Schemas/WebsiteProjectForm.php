<?php

namespace App\Filament\Resources\WebsiteProjects\Schemas;

use App\Enums\WebsiteProjectStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class WebsiteProjectForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                Select::make('order_id')
                    ->relationship('order', 'id')
                    ->required(),
                Select::make('domain_id')
                    ->relationship('domain', 'id'),
                Select::make('hosting_account_id')
                    ->relationship('hostingAccount', 'id'),
                Select::make('assigned_developer_id')
                    ->relationship('assignedDeveloper', 'name'),
                TextInput::make('project_number')
                    ->required(),
                Select::make('status')
                    ->options(WebsiteProjectStatus::class)
                    ->required(),
                TextInput::make('business_name'),
                Textarea::make('business_description')
                    ->columnSpanFull(),
                TextInput::make('industry'),
                Textarea::make('pages_required')
                    ->columnSpanFull(),
                TextInput::make('brand_colours'),
                Textarea::make('reference_websites')
                    ->columnSpanFull(),
                Textarea::make('special_requirements')
                    ->columnSpanFull(),
                Textarea::make('internal_notes')
                    ->columnSpanFull(),
                Toggle::make('content_received')
                    ->required(),
                Toggle::make('logo_received')
                    ->required(),
                DatePicker::make('target_launch_date'),
                DateTimePicker::make('launched_at'),
            ]);
    }
}
