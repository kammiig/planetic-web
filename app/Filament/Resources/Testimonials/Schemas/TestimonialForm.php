<?php

namespace App\Filament\Resources\Testimonials\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class TestimonialForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('author_name')->required(),
                TextInput::make('author_role')->label('Role / title'),
                TextInput::make('company'),
                Select::make('rating')
                    ->options([5 => '★★★★★ (5)', 4 => '★★★★ (4)', 3 => '★★★ (3)', 2 => '★★ (2)', 1 => '★ (1)'])
                    ->default(5)
                    ->required(),
                Textarea::make('body')->label('Quote')->required()->rows(4)->columnSpanFull(),
                TextInput::make('avatar_url')->label('Avatar image URL')->url()->columnSpanFull(),
                Toggle::make('is_active')->label('Active')->default(true),
                TextInput::make('sort_order')->numeric()->default(0),
            ]);
    }
}
