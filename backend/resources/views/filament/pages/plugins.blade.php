<x-filament-panels::page>
    @php($plugins = $this->getPlugins())

    @if (empty($plugins))
        <x-filament::section>
            <p style="font-size:.9rem;opacity:.75">No plugins are available for this site yet.</p>
        </x-filament::section>
    @else
        {{-- Inline styles: Filament's compiled CSS only ships the Tailwind classes it uses itself --}}
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:1.5rem">
            @foreach ($plugins as $plugin)
                <x-filament::section>
                    <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:.5rem">
                        <x-filament::icon :icon="$plugin['icon']" style="width:2rem;height:2rem;color:rgb(var(--primary-500))" />
                        <h3 style="font-size:1.05rem;font-weight:600">{{ $plugin['name'] }}</h3>
                        @if ($plugin['active'])
                            <x-filament::badge color="success">Active</x-filament::badge>
                        @endif
                    </div>
                    <p style="font-size:.875rem;opacity:.75;margin-bottom:1rem">{{ $plugin['description'] }}</p>

                    @if ($plugin['active'])
                        <div style="display:flex;gap:.75rem;flex-wrap:wrap">
                            @if ($plugin['settings_url'])
                                <x-filament::button tag="a" :href="$plugin['settings_url']" icon="heroicon-o-cog-6-tooth">Settings</x-filament::button>
                            @endif
                            {{ ($this->deactivateAction)(['plugin' => $plugin['key']]) }}
                        </div>
                    @else
                        {{ ($this->activateAction)(['plugin' => $plugin['key']]) }}
                    @endif
                </x-filament::section>
            @endforeach
        </div>
    @endif
</x-filament-panels::page>
