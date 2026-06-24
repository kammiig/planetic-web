<x-filament-panels::page>
    <form wire:submit="save" class="space-y-6">
        {{ $this->form }}

        <div>
            <x-filament::button type="submit">
                Save changes
            </x-filament::button>
        </div>
    </form>

    <x-filament::section>
        <x-slot name="heading">Per-order registrar</x-slot>
        <x-slot name="description">
            The registrar used for each individual domain is recorded on the domain record and shown as a badge on the
            <a href="{{ \App\Filament\Resources\Domains\DomainResource::getUrl() }}" class="text-primary-600 underline">Domains</a>
            list.
        </x-slot>
    </x-filament::section>
</x-filament-panels::page>
