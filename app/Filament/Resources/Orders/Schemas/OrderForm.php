<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                TextInput::make('order_number')
                    ->required(),
                Select::make('status')
                    ->options(OrderStatus::class)
                    ->required(),
                Select::make('payment_status')
                    ->options(PaymentStatus::class)
                    ->required(),
                TextInput::make('currency')
                    ->required()
                    ->default('GBP'),
                TextInput::make('subtotal')
                    ->required()
                    ->numeric(),
                TextInput::make('discount_total')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('tax_total')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('total')
                    ->required()
                    ->numeric(),
                TextInput::make('stripe_checkout_session_id'),
                TextInput::make('stripe_payment_intent_id'),
                TextInput::make('stripe_subscription_id'),
                Textarea::make('admin_notes')
                    ->columnSpanFull(),
                DateTimePicker::make('paid_at'),
                DateTimePicker::make('cancelled_at'),
            ]);
    }
}
