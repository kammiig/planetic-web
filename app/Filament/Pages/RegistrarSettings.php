<?php

namespace App\Filament\Pages;

use App\Models\SiteSetting;
use App\Providers\IntegrationServiceProvider;
use BackedEnum;
use Filament\Forms\Components\Radio;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Lets an admin choose the default domain registrar without touching the
 * server environment. Shows each provider's enabled/configured status but
 * never reveals API keys (those stay in the .env). The selection is stored in
 * site_settings (registrar.default) and honoured by IntegrationServiceProvider.
 */
class RegistrarSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    protected static string|UnitEnum|null $navigationGroup = 'System';

    protected static ?string $navigationLabel = 'Registrar Settings';

    protected static ?string $title = 'Registrar Settings';

    protected static ?int $navigationSort = 95;

    protected string $view = 'filament.pages.registrar-settings';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public function mount(): void
    {
        $this->form->fill([
            'default_registrar' => SiteSetting::get('registrar.default') ?: config('domain.default_registrar', 'porkbun'),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Default registrar')
                    ->description('Used for new domain registrations. API keys are read from the server environment only and are never shown or stored here.')
                    ->schema([
                        Radio::make('default_registrar')
                            ->hiddenLabel()
                            ->options([
                                'porkbun' => 'Porkbun (recommended)',
                                'namesilo' => 'NameSilo',
                                'namecheap' => 'Namecheap',
                            ])
                            ->descriptions($this->registrarDescriptions())
                            ->disableOptionWhen(fn (string $value): bool => ! $this->registrarEnabled($value))
                            ->required(),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $value = $this->form->getState()['default_registrar'] ?? null;

        if (! in_array($value, IntegrationServiceProvider::REGISTRARS, true) || ! $this->registrarEnabled($value)) {
            Notification::make()->title('That registrar is not enabled')->danger()->send();

            return;
        }

        SiteSetting::set('registrar.default', $value, 'registrar', 'text');

        Notification::make()->title('Default registrar updated')->success()->send();
    }

    private function registrarEnabled(string $name): bool
    {
        return config("domain.{$name}.enabled") !== false;
    }

    private function registrarConfigured(string $name): bool
    {
        return match ($name) {
            'porkbun' => filled(config('domain.porkbun.api_key')) && filled(config('domain.porkbun.secret_key')),
            'namesilo' => filled(config('domain.namesilo.api_key')),
            'namecheap' => filled(config('domain.namecheap.api_key')) && filled(config('domain.namecheap.api_user')),
            default => false,
        };
    }

    /** @return array<string, string> */
    private function registrarDescriptions(): array
    {
        $out = [];

        foreach (IntegrationServiceProvider::REGISTRARS as $name) {
            $out[$name] = ($this->registrarEnabled($name) ? 'Enabled' : 'Disabled')
                .' · '.($this->registrarConfigured($name) ? 'API keys present' : 'API keys missing');
        }

        return $out;
    }
}
