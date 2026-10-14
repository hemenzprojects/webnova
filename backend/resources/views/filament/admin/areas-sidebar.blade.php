{{-- Area switcher at the top of the sidebar on small screens, where the top-bar tabs are hidden --}}
@php($areas = \App\Admin\Areas::visible())
@php($current = \App\Admin\Areas::current())

@if (count($areas))
    <div class="lg:hidden" style="margin-bottom:1rem;padding-bottom:1rem;border-bottom:1px solid rgba(127,127,127,.2)">
        <p style="font-size:.7rem;font-weight:600;text-transform:uppercase;letter-spacing:.05em;opacity:.6;margin:0 .5rem .4rem">Areas</p>
        @foreach ($areas as $key => $area)
            @php($active = $key === $current)
            <a
                href="{{ $area['url'] }}"
                style="display:flex;align-items:center;gap:.6rem;padding:.45rem .5rem;border-radius:.5rem;font-size:.875rem;font-weight:{{ $active ? '600' : '500' }};color:{{ $active ? 'rgb(var(--primary-600))' : 'inherit' }};background:{{ $active ? 'rgba(var(--primary-500),.1)' : 'transparent' }}"
            >
                <x-filament::icon :icon="$area['icon']" style="width:1.15rem;height:1.15rem" />
                {{ $area['label'] }}
            </a>
        @endforeach
    </div>
@endif
