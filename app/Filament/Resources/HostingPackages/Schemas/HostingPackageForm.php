<?php

namespace App\Filament\Resources\HostingPackages\Schemas;

use App\Enums\ProductType;
use App\Models\Product;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class HostingPackageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Plan')
                    ->description('How the plan appears to customers.')
                    ->columns(2)
                    ->schema([
                        Select::make('product_id')
                            ->label('Linked product')
                            ->relationship('product', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->helperText('The catalogue product this plan sells. Create one inline if needed.')
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
                                'type' => ProductType::Hosting->value,
                                'description' => $data['description'] ?? null,
                                'is_active' => true,
                            ])->id),
                        TextInput::make('name')->required(),
                        TextInput::make('tagline')
                            ->maxLength(255)
                            ->columnSpanFull()
                            ->helperText('Short marketing line shown under the plan name.'),
                        Toggle::make('is_popular')->label('Mark as popular / recommended'),
                        Toggle::make('is_active')->label('Active (visible on the site)')->default(true),
                        TextInput::make('sort_order')->numeric()->default(0)->helperText('Lower numbers appear first.'),
                    ]),

                Section::make('Pricing (GBP)')
                    ->description('Stored on the linked product and used at checkout. Frontend updates instantly.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('price_monthly')->label('Monthly price')->numeric()->prefix('£')->minValue(0)->dehydrated(false),
                        TextInput::make('price_yearly')->label('Yearly price')->numeric()->prefix('£')->minValue(0)->dehydrated(false),
                    ]),

                Section::make('Limits & resources')
                    ->description('Leave a field blank to advertise it as unlimited / unmetered.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('disk_limit_mb')->label('Disk (MB)')->numeric(),
                        TextInput::make('bandwidth_limit_mb')->label('Bandwidth (MB)')->numeric(),
                        TextInput::make('email_accounts_limit')->label('Email accounts')->numeric(),
                        TextInput::make('database_limit')->label('Databases')->numeric(),
                        TextInput::make('domain_limit')->label('Domains')->numeric(),
                    ]),

                Section::make('Inclusions & provisioning')
                    ->columns(2)
                    ->schema([
                        Toggle::make('ssl_included')->label('Free SSL included')->default(true),
                        Toggle::make('includes_free_domain')
                            ->label('Includes free first-year domain')
                            ->helperText('Checkout offers a free new-domain registration with this plan.'),
                        TextInput::make('whm_package_name')
                            ->label('WHM package name')
                            ->required()
                            ->columnSpanFull()
                            ->helperText('Exact WHM/cPanel package name used for provisioning. Editable because WHM package names can change.'),
                        TagsInput::make('features')
                            ->label('Feature bullets')
                            ->columnSpanFull()
                            ->helperText('Custom feature list for the plan card. Leave empty to auto-generate from the limits above.'),
                    ]),
            ]);
    }
}
