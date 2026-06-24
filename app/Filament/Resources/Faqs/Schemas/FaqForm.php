<?php

namespace App\Filament\Resources\Faqs\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class FaqForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('page')
                    ->options([
                        'home' => 'Homepage',
                        'hosting' => 'Hosting page',
                        'website-package' => 'Website package page',
                        'domains' => 'Domains page',
                    ])
                    ->default('home')
                    ->required()
                    ->helperText('Which page this FAQ appears on (also powers FAQ schema).'),
                TextInput::make('question')->required()->columnSpanFull(),
                Textarea::make('answer')->required()->rows(3)->columnSpanFull(),
                Toggle::make('is_active')->label('Active')->default(true),
                TextInput::make('sort_order')->numeric()->default(0),
            ]);
    }
}
