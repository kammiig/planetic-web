<?php

namespace App\Filament\Resources\DnsRecords\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class DnsRecordForm
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
                Select::make('cloudflare_zone_id')
                    ->relationship('cloudflareZone', 'id')
                    ->required(),
                TextInput::make('cloudflare_record_id'),
                TextInput::make('type')
                    ->required(),
                TextInput::make('name')
                    ->required(),
                Textarea::make('content')
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('ttl')
                    ->numeric(),
                Toggle::make('proxied')
                    ->required(),
                TextInput::make('priority')
                    ->numeric(),
                TextInput::make('status')
                    ->required()
                    ->default('active'),
                Textarea::make('metadata')
                    ->columnSpanFull(),
            ]);
    }
}
