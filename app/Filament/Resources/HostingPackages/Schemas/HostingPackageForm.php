<?php

namespace App\Filament\Resources\HostingPackages\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class HostingPackageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('product_id')
                    ->relationship('product', 'name')
                    ->required(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('whm_package_name')
                    ->required(),
                TextInput::make('disk_limit_mb')
                    ->numeric(),
                TextInput::make('bandwidth_limit_mb')
                    ->numeric(),
                TextInput::make('email_accounts_limit')
                    ->email()
                    ->numeric(),
                TextInput::make('database_limit')
                    ->numeric(),
                TextInput::make('domain_limit')
                    ->numeric(),
                Toggle::make('is_active')
                    ->required(),
            ]);
    }
}
