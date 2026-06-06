<?php

namespace App\Filament\Resources\Domains\Schemas;

use App\Enums\DomainStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class DomainForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                Select::make('order_id')
                    ->relationship('order', 'id'),
                TextInput::make('domain_name')
                    ->required(),
                TextInput::make('sld')
                    ->required(),
                TextInput::make('tld')
                    ->required(),
                TextInput::make('registrar'),
                TextInput::make('registrar_domain_id'),
                TextInput::make('registrar_order_id'),
                Select::make('status')
                    ->options(DomainStatus::class)
                    ->required(),
                DatePicker::make('registration_date'),
                DatePicker::make('expiry_date'),
                Toggle::make('auto_renew')
                    ->required(),
                Toggle::make('whois_privacy')
                    ->required(),
                Toggle::make('registrar_lock')
                    ->required(),
                Select::make('cloudflare_zone_id')
                    ->relationship('cloudflareZone', 'id'),
                Textarea::make('nameservers')
                    ->columnSpanFull(),
                DateTimePicker::make('last_synced_at'),
            ]);
    }
}
