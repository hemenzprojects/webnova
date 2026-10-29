<x-filament-widgets::widget>
    <x-filament::section>
        <div style="display:flex;flex-wrap:wrap;align-items:center;gap:.5rem">
            @foreach ($actions as $action)
                <x-filament::button tag="a" :href="$action['url']" :icon="$action['icon']" size="sm" color="gray">
                    {{ $action['label'] }}
                </x-filament::button>
            @endforeach

            <x-filament::button tag="a" :href="$siteUrl" target="_blank" icon="heroicon-m-arrow-top-right-on-square" size="sm">
                View site
            </x-filament::button>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
