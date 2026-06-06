<?php

namespace App\Filament\Resources\Payments\Schemas;

use App\Enums\PaymentStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class PaymentForm
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
                Select::make('invoice_id')
                    ->relationship('invoice', 'id'),
                TextInput::make('provider')
                    ->required()
                    ->default('stripe'),
                TextInput::make('provider_payment_id'),
                TextInput::make('provider_customer_id'),
                TextInput::make('amount')
                    ->required()
                    ->numeric(),
                TextInput::make('currency')
                    ->required()
                    ->default('GBP'),
                Select::make('status')
                    ->options(PaymentStatus::class)
                    ->required(),
                Textarea::make('failure_reason')
                    ->columnSpanFull(),
                DateTimePicker::make('paid_at'),
            ]);
    }
}
