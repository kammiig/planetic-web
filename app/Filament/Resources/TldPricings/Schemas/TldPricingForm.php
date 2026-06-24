<?php

namespace App\Filament\Resources\TldPricings\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TldPricingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('TLD')
                    ->columns(2)
                    ->schema([
                        TextInput::make('tld')
                            ->label('Extension')
                            ->required()
                            ->prefix('.')
                            ->unique(ignoreRecord: true)
                            ->formatStateUsing(fn ($state) => ltrim((string) $state, '.'))
                            ->dehydrateStateUsing(fn ($state) => strtolower(ltrim((string) $state, '.')))
                            ->helperText('Without the leading dot, e.g. "com" or "co.uk".'),
                        TextInput::make('sort_order')->numeric()->default(0)->helperText('Lower numbers appear first.'),
                    ]),

                Section::make('Customer pricing (GBP)')
                    ->description('What customers pay. Domain search and checkout use the registration price.')
                    ->columns(3)
                    ->schema([
                        TextInput::make('register_price')->label('Register / year')->numeric()->prefix('£')->required()->minValue(0),
                        TextInput::make('renew_price')->label('Renewal / year')->numeric()->prefix('£')->minValue(0),
                        TextInput::make('transfer_price')->label('Transfer')->numeric()->prefix('£')->minValue(0),
                    ]),

                Section::make('Internal reference (admin only)')
                    ->description('Never shown to customers. Cost can be synced from Porkbun.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('cost_price')->label('Cost / year')->numeric()->prefix('£')->minValue(0),
                        TextInput::make('markup')->label('Margin')->numeric()->prefix('£'),
                    ]),

                Section::make('Options')
                    ->columns(3)
                    ->schema([
                        Toggle::make('free_eligible')->label('Eligible as free first-year domain')->default(true),
                        Toggle::make('is_featured')->label('Featured / popular')->default(false),
                        Toggle::make('is_active')->label('Active (shown on the site)')->default(true),
                    ]),
            ]);
    }
}
