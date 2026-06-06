<?php

namespace App\Filament\Resources\Subscriptions\Schemas;

use App\Enums\SubscriptionStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class SubscriptionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                Select::make('product_id')
                    ->relationship('product', 'name'),
                Select::make('domain_id')
                    ->relationship('domain', 'id'),
                Select::make('hosting_account_id')
                    ->relationship('hostingAccount', 'id'),
                TextInput::make('stripe_subscription_id'),
                Select::make('status')
                    ->options(SubscriptionStatus::class)
                    ->required(),
                TextInput::make('billing_cycle')
                    ->required(),
                TextInput::make('currency')
                    ->required()
                    ->default('GBP'),
                TextInput::make('amount')
                    ->required()
                    ->numeric(),
                DatePicker::make('current_period_start'),
                DatePicker::make('current_period_end'),
                DatePicker::make('next_renewal_date'),
                Toggle::make('cancel_at_period_end')
                    ->required(),
                DateTimePicker::make('cancelled_at'),
            ]);
    }
}
