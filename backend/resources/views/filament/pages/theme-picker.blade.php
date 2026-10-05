<x-filament-panels::page>
    @php($active = $this->getActiveSlug())

    {{-- Inline styles: Filament's compiled CSS only ships the Tailwind classes it uses itself --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:1.5rem">
        @foreach ($this->getThemes() as $theme)
            @php($c = $theme['tokens']['colors'] ?? [])
            @php($isActive = $theme['slug'] === $active)
            <x-filament::section>
                {{-- Miniature of the theme: header bar, hero band, cards, footer --}}
                <div style="border-radius:10px;overflow:hidden;border:1px solid rgba(0,0,0,.08);margin-bottom:1rem">
                    <div style="height:14px;background:{{ $c['surface'] ?? '#fff' }};display:flex;align-items:center;gap:4px;padding:0 8px">
                        <span style="width:28px;height:5px;border-radius:3px;background:{{ $c['primary'] ?? '#000' }}"></span>
                        <span style="flex:1"></span>
                        <span style="width:14px;height:5px;border-radius:3px;background:{{ $c['primary'] ?? '#000' }}"></span>
                    </div>
                    <div style="height:70px;background:{{ $c['surfaceMuted'] ?? '#eee' }};padding:12px 10px">
                        <div style="width:55%;height:8px;border-radius:3px;background:{{ $c['text'] ?? '#111' }};margin-bottom:5px"></div>
                        <div style="width:40%;height:8px;border-radius:3px;background:{{ $c['text'] ?? '#111' }};margin-bottom:9px"></div>
                        <div style="width:22%;height:9px;border-radius:{{ $theme['tokens']['radius']['button'] ?? '4px' }};background:{{ $c['accent'] ?? '#000' }}"></div>
                    </div>
                    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:6px;padding:8px 10px;background:{{ $c['surface'] ?? '#fff' }}">
                        @for ($i = 0; $i < 3; $i++)
                            <div style="height:28px;border-radius:{{ $theme['tokens']['radius']['card'] ?? '6px' }};background:{{ $c['surfaceMuted'] ?? '#eee' }}"></div>
                        @endfor
                    </div>
                    <div style="height:14px;background:{{ $c['footerBg'] ?? $c['primary'] ?? '#000' }}"></div>
                </div>

                <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.25rem">
                    <h3 style="font-size:1.05rem;font-weight:600">{{ $theme['name'] }}</h3>
                    @if ($isActive)
                        <x-filament::badge color="success">Active</x-filament::badge>
                    @endif
                </div>
                <p style="font-size:.875rem;opacity:.75;margin-bottom:.75rem">{{ $theme['description'] ?? '' }}</p>

                <div style="display:flex;gap:4px;margin-bottom:1rem">
                    @foreach (['primary', 'accent', 'surfaceMuted', 'text', 'footerBg'] as $key)
                        @isset($c[$key])
                            <span title="{{ $key }}" style="width:20px;height:20px;border-radius:9999px;border:1px solid rgba(0,0,0,.1);background:{{ $c[$key] }}"></span>
                        @endisset
                    @endforeach
                </div>

                @if ($theme['installable'])
                    {{ ($this->installAction)(['theme' => $theme['slug']]) }}
                @else
                    <p style="font-size:.8rem;opacity:.6">No starter content available for this theme yet.</p>
                @endif
            </x-filament::section>
        @endforeach
    </div>

    <x-filament::section
        heading="Backups"
        description="A backup of your site is saved automatically before every theme install or restore."
    >
        @forelse ($this->getBackups() as $backup)
            <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:.6rem 0;border-top:1px solid rgba(127,127,127,.15)">
                <div>
                    <div style="font-weight:500">{{ $backup['date'] }}</div>
                    <div style="font-size:.8rem;opacity:.7">Site using the {{ $backup['theme'] }} theme</div>
                </div>
                {{ ($this->restoreAction)(['file' => $backup['file']]) }}
            </div>
        @empty
            <p style="font-size:.875rem;opacity:.7">No backups yet.</p>
        @endforelse
    </x-filament::section>
</x-filament-panels::page>
