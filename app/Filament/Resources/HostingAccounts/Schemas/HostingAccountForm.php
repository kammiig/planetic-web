<?php

namespace App\Filament\Resources\HostingAccounts\Schemas;

use App\Enums\HostingStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class HostingAccountForm
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
                Select::make('domain_id')
                    ->relationship('domain', 'id'),
                Select::make('hosting_package_id')
                    ->relationship('hostingPackage', 'name')
                    ->required(),
                TextInput::make('domain_name')
                    ->required(),
                TextInput::make('whm_username')
                    ->required(),
                TextInput::make('whm_account_id'),
                TextInput::make('server_hostname'),
                TextInput::make('server_ip'),
                TextInput::make('cpanel_url')
                    ->url(),
                Select::make('status')
                    ->options(HostingStatus::class)
                    ->required(),
                TextInput::make('disk_limit_mb')
                    ->numeric(),
                TextInput::make('bandwidth_limit_mb')
                    ->numeric(),
                DateTimePicker::make('created_on_whm_at'),
                DateTimePicker::make('suspended_at'),
                Textarea::make('suspension_reason')
                    ->columnSpanFull(),
                DatePicker::make('renewal_date'),
                DateTimePicker::make('last_synced_at'),
            ]);
    }
}
