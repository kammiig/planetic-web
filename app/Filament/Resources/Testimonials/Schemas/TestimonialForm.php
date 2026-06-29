<?php

namespace App\Filament\Resources\Testimonials\Schemas;

use App\Enums\ReviewSource;
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

                Select::make('source')
                    ->label('Review source')
                    ->options(ReviewSource::class)
                    ->default(ReviewSource::Manual->value)
                    ->required()
                    ->live()
                    ->helperText('Only pick Trustpilot or Google for reviews that genuinely came from there — the matching logo is shown publicly.'),
                TextInput::make('source_url')
                    ->label('Link to original review')
                    ->url()
                    ->placeholder('https://www.trustpilot.com/reviews/…')
                    ->helperText('Optional. Links the review badge to the original on Trustpilot/Google.')
                    ->visible(fn ($get) => $get('source') && $get('source') !== ReviewSource::Manual->value),
                Toggle::make('is_verified')
                    ->label('Verified review')
                    ->helperText('Enable only for a genuine verified review. Adds a “Verified …” label. Never fake verification.')
                    ->default(false),

                TextInput::make('avatar_url')->label('Avatar image URL')->url()->columnSpanFull(),
                Toggle::make('is_active')->label('Active')->default(true),
                TextInput::make('sort_order')->numeric()->default(0),
            ]);
    }
}
