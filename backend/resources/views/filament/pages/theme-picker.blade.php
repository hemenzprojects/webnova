<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}

        <div class="mt-6 flex gap-2">
            <x-filament::button type="submit">Apply theme</x-filament::button>
        </div>
    </form>
</x-filament-panels::page>