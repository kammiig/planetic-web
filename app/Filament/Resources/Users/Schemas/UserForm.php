<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required(),
                DateTimePicker::make('email_verified_at'),
                TextInput::make('password')
                    ->password()
                    ->required(),
                TextInput::make('phone')
                    ->tel(),
                TextInput::make('company_name'),
                TextInput::make('billing_address_line_1'),
                TextInput::make('billing_address_line_2'),
                TextInput::make('billing_city'),
                TextInput::make('billing_state'),
                TextInput::make('billing_postcode'),
                TextInput::make('billing_country'),
                TextInput::make('stripe_customer_id'),
                Toggle::make('is_admin')
                    ->required(),
                TextInput::make('status')
                    ->required()
                    ->default('active'),
            ]);
    }
}
