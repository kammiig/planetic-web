<?php

namespace App\Filament\Resources\CloudflareZones\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CloudflareZoneForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                Select::make('domain_id')
                    ->relationship('domain', 'id')
                    ->required(),
                TextInput::make('zone_id')
                    ->required(),
                TextInput::make('zone_name')
                    ->required(),
                TextInput::make('status')
                    ->required(),
                Textarea::make('name_servers')
                    ->columnSpanFull(),
                TextInput::make('ssl_mode'),
                Toggle::make('always_use_https')
                    ->required(),
                DateTimePicker::make('created_on_cloudflare_at'),
                DateTimePicker::make('last_synced_at'),
            ]);
    }
}
