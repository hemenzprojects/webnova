{{-- Inline styles: Filament's compiled CSS only ships the Tailwind classes it uses itself --}}
<x-filament-widgets::widget>
    <x-filament::section heading="Recent changes" description="The latest content added or edited">
        @if ($items->isEmpty())
            <p style="font-size:.875rem;color:rgb(var(--gray-500))">Nothing here yet. New pages, news, events and services will show up here.</p>
        @else
            <ul style="margin:-.5rem 0">
                @foreach ($items as $item)
                    <li style="display:flex;align-items:center;gap:.75rem;padding:.6rem 0;{{ $loop->last ? '' : 'border-bottom:1px solid rgba(var(--gray-200),.7)' }}">
                        <x-filament::icon :icon="$item['icon']" style="width:1.25rem;height:1.25rem;flex:none;color:rgb(var(--gray-400))" />
                        <div style="min-width:0;flex:1">
                            <div style="font-size:.875rem;font-weight:500;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                                @if ($item['url'])
                                    <a href="{{ $item['url'] }}" style="color:inherit" onmouseover="this.style.color='rgb(var(--primary-600))'" onmouseout="this.style.color='inherit'">{{ $item['title'] }}</a>
                                @else
                                    {{ $item['title'] }}
                                @endif
                            </div>
                            <div style="font-size:.75rem;color:rgb(var(--gray-500))">
                                {{ $item['type'] }} · {{ $item['created'] ? 'Added' : 'Edited' }} {{ $item['updated_at']?->diffForHumans() }}
                            </div>
                        </div>
                        @unless ($item['live'])
                            <x-filament::badge color="warning" size="sm">Not live</x-filament::badge>
                        @endunless
                        @if ($item['builder'])
                            <x-filament::link :href="$item['builder']" target="_blank" icon="heroicon-m-cube" size="sm">Page Builder</x-filament::link>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
