<?php

namespace App\Filament\Resources\Invoices\Schemas;

use App\Enums\InvoiceStatus;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class InvoiceForm
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
                Select::make('subscription_id')
                    ->relationship('subscription', 'id'),
                TextInput::make('invoice_number')
                    ->required(),
                TextInput::make('stripe_invoice_id'),
                TextInput::make('currency')
                    ->required()
                    ->default('GBP'),
                TextInput::make('subtotal')
                    ->required()
                    ->numeric(),
                TextInput::make('tax_total')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('total')
                    ->required()
                    ->numeric(),
                TextInput::make('amount_paid')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('amount_due')
                    ->required()
                    ->numeric()
                    ->default(0),
                Select::make('status')
                    ->options(InvoiceStatus::class)
                    ->required(),
                DatePicker::make('due_date'),
                DateTimePicker::make('paid_at'),
            ]);
    }
}
