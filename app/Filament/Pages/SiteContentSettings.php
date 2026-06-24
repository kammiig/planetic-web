<?php

namespace App\Filament\Pages;

use App\Models\SiteSetting;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;
use UnitEnum;

/**
 * One screen to edit all marketing copy, contact details, social links and
 * footer content — driven entirely by the site_settings table, so new keys
 * added by a seeder appear here automatically. Setting keys contain dots
 * (e.g. "hero.title"); they are mapped to flat field names ("hero__title") to
 * avoid Livewire nesting the form state.
 */
class SiteContentSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPencilSquare;

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?string $navigationLabel = 'Website Content';

    protected static ?string $title = 'Website Content';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.site-content-settings';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->isStaff() ?? false;
    }

    public function mount(): void
    {
        $state = [];

        foreach ($this->settings() as $setting) {
            $state[$this->fieldKey($setting->key)] = $setting->castValue();
        }

        $this->form->fill($state);
    }

    public function form(Schema $schema): Schema
    {
        $sections = [];

        foreach ($this->settings()->groupBy('group') as $group => $items) {
            $sections[] = Section::make(Str::headline($group))
                ->columns(2)
                ->collapsible()
                ->schema($items->map(fn (SiteSetting $s) => $this->fieldFor($s))->all());
        }

        return $schema->components($sections)->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();

        foreach ($this->settings() as $setting) {
            $value = $state[$this->fieldKey($setting->key)] ?? null;
            $setting->update(['value' => is_bool($value) ? ($value ? '1' : '0') : $value]);
        }

        // booted() on the model flushes the cache on each save.
        Notification::make()->title('Website content updated')->success()->send();
    }

    /** Editable content settings (the registrar override lives on its own page). */
    private function settings()
    {
        return SiteSetting::query()
            ->where('group', '!=', 'registrar')
            ->orderBy('group')
            ->orderBy('sort_order')
            ->get();
    }

    private function fieldFor(SiteSetting $s)
    {
        $name = $this->fieldKey($s->key);
        $label = $s->label ?: Str::headline(Str::afterLast($s->key, '.'));

        $field = match ($s->type) {
            'textarea' => Textarea::make($name)->rows(3)->columnSpanFull(),
            'boolean' => Toggle::make($name),
            'url' => TextInput::make($name)->url(),
            'email' => TextInput::make($name)->email(),
            default => TextInput::make($name),
        };

        return $field->label($label)->helperText($s->help);
    }

    private function fieldKey(string $key): string
    {
        return str_replace('.', '__', $key);
    }
}
