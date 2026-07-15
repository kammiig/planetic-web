<?php

namespace App\Filament\Resources\Posts\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PostForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                TextInput::make('slug')
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->helperText('The URL, e.g. how-much-does-a-website-cost-uk. Leave blank to generate from the title. Avoid changing it after publishing.')
                    ->columnSpanFull(),
                Textarea::make('excerpt')
                    ->rows(2)
                    ->helperText('Short summary shown on the blog page. Falls back to the first lines of the article.')
                    ->columnSpanFull(),
                MarkdownEditor::make('body')
                    ->required()
                    ->helperText('The article, written in Markdown. Use ## for section headings.')
                    ->columnSpanFull(),
                TextInput::make('meta_title')
                    ->label('Meta title (SEO)')
                    ->maxLength(70)
                    ->helperText('Title shown in Google. Falls back to the post title.'),
                Textarea::make('meta_description')
                    ->label('Meta description (SEO)')
                    ->rows(2)
                    ->maxLength(170)
                    ->helperText('Description shown in Google. Falls back to the excerpt.'),
                Toggle::make('is_published')
                    ->label('Published')
                    ->default(false)
                    ->helperText('Only published posts appear on the site and in the sitemap.'),
                DateTimePicker::make('published_at')
                    ->label('Publish date')
                    ->helperText('Shown on the article and used for ordering.'),
            ]);
    }
}
