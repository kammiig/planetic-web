<?php

namespace App\Filament\Resources\SeoMetas\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SeoMetaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Page')
                    ->columns(2)
                    ->schema([
                        TextInput::make('page_key')
                            ->label('Route key')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->helperText('The route name, e.g. "home", "hosting.index", "website-package".'),
                        TextInput::make('label')->label('Admin label')->helperText('A friendly name for this list only.'),
                        Toggle::make('noindex')
                            ->label('Discourage search engines (noindex)')
                            ->helperText('Adds a noindex meta tag. Leave off for normal pages.'),
                    ]),

                Section::make('Search result')
                    ->schema([
                        TextInput::make('meta_title')->label('Meta title')->maxLength(70)->helperText('Aim for under 60 characters.'),
                        Textarea::make('meta_description')->label('Meta description')->rows(2)->maxLength(180)->helperText('Aim for under 160 characters.'),
                        TextInput::make('canonical_url')->label('Canonical URL')->url()->helperText('Leave blank to use the page URL.'),
                    ]),

                Section::make('Social sharing (Open Graph)')
                    ->columns(2)
                    ->schema([
                        TextInput::make('og_title')->label('OG title'),
                        TextInput::make('og_image')->label('OG image URL')->url(),
                        Textarea::make('og_description')->label('OG description')->rows(2)->columnSpanFull(),
                    ]),

                Section::make('Twitter / X card')
                    ->columns(2)
                    ->schema([
                        Select::make('twitter_card')
                            ->options([
                                'summary' => 'Summary',
                                'summary_large_image' => 'Summary with large image',
                            ])
                            ->default('summary_large_image'),
                        TextInput::make('twitter_title')->label('Twitter title'),
                        Textarea::make('twitter_description')->label('Twitter description')->rows(2)->columnSpanFull(),
                    ]),

                Section::make('Structured data')
                    ->schema([
                        Textarea::make('schema_json')
                            ->label('Additional JSON-LD')
                            ->rows(5)
                            ->helperText('Optional extra schema.org JSON-LD for this page. Organisation, breadcrumb and FAQ schema are added automatically.'),
                    ]),
            ]);
    }
}
