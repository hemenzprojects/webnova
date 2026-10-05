{{-- Functional areas as tabs in the top bar (desktop). The sidebar lists the current area's items. --}}
@php($areas = \App\Admin\Areas::visible())
@php($current = \App\Admin\Areas::current())

@if (count($areas))
    {{-- Inline styles: Filament's compiled CSS only ships the Tailwind classes it uses itself --}}
    <nav class="hidden lg:flex" style="align-items:center;gap:.25rem;height:100%" aria-label="Areas">
        @foreach ($areas as $key => $area)
            @php($active = $key === $current)
            <a
                href="{{ $area['url'] }}"
                @if ($active) aria-current="page" @endif
                style="display:flex;align-items:center;gap:.4rem;height:100%;padding:0 .85rem;font-size:.875rem;font-weight:{{ $active ? '600' : '500' }};border-bottom:2px solid {{ $active ? 'rgb(var(--primary-600))' : 'transparent' }};color:{{ $active ? 'rgb(var(--primary-600))' : 'rgb(var(--gray-600))' }};white-space:nowrap"
            >
                <x-filament::icon :icon="$area['icon']" style="width:1.15rem;height:1.15rem" />
                {{ $area['label'] }}
            </a>
        @endforeach
    </nav>
@endif
