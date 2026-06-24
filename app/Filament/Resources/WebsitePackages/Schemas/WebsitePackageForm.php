<?php

namespace App\Filament\Resources\WebsitePackages\Schemas;

use App\Enums\ProductType;
use App\Models\Product;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class WebsitePackageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Package')
                    ->columns(2)
                    ->schema([
                        Select::make('product_id')
                            ->label('Linked product')
                            ->relationship('product', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug((string) $state))),
                                TextInput::make('slug')->required(),
                                Textarea::make('description'),
                            ])
                            ->createOptionUsing(fn (array $data): int => Product::create([
                                'name' => $data['name'],
                                'slug' => $data['slug'] ?? Str::slug($data['name']),
                                'type' => ProductType::WebsitePackage->value,
                                'description' => $data['description'] ?? null,
                                'is_active' => true,
                            ])->id),
                        TextInput::make('name')->required(),
                        TextInput::make('tagline')->maxLength(255)->columnSpanFull(),
                        Textarea::make('description')->rows(3)->columnSpanFull(),
                        Toggle::make('is_active')->label('Active (visible on the site)')->default(true),
                        TextInput::make('sort_order')->numeric()->default(0),
                    ]),

                Section::make('Pricing (GBP)')
                    ->description('Stored on the linked product (one-time) and used at checkout.')
                    ->schema([
                        TextInput::make('price_one_time')->label('One-time price')->numeric()->prefix('£')->minValue(0)->dehydrated(false),
                    ]),

                Section::make('Inclusions')
                    ->columns(2)
                    ->schema([
                        Toggle::make('includes_free_domain')->label('Includes free first-year domain')->default(true),
                        Toggle::make('includes_hosting')->label('Includes free first-year hosting')->default(true),
                        Select::make('hosting_package_id')
                            ->label('Linked hosting plan')
                            ->relationship('hostingPackage', 'name')
                            ->searchable()
                            ->preload()
                            ->helperText('The hosting plan provisioned with this website package.')
                            ->columnSpanFull(),
                    ]),

                Section::make('Features')
                    ->schema([
                        TagsInput::make('features')
                            ->label('What is included')
                            ->helperText('These bullets appear on the website-package page.'),
                    ]),

                Section::make('Project intake questions')
                    ->description('Questions the customer answers when starting their project.')
                    ->schema([
                        Repeater::make('intake_questions')
                            ->hiddenLabel()
                            ->schema([
                                TextInput::make('label')->required()->columnSpan(2),
                                Select::make('type')
                                    ->options(['text' => 'Short text', 'textarea' => 'Long text'])
                                    ->default('text')
                                    ->required(),
                                Toggle::make('required')->default(false)->inline(false),
                            ])
                            ->columns(4)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['label'] ?? 'Question')
                            ->addActionLabel('Add question'),
                    ]),
            ]);
    }
}
