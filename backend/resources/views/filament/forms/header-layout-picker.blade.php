@php
    $primary = $primaryColor ?? '#0A1E3E';
    $accent = $accentColor ?? '#00D9FF';

    // Building blocks of the miniature header drawings
    $bar = fn (string $width, string $color = '#cbd5e1', string $height = '5px') => "<span style=\"display:block;width:{$width};height:{$height};border-radius:3px;background:{$color}\"></span>";
    $logo = "<span style=\"display:flex;align-items:center;gap:5px\"><span style=\"width:16px;height:16px;border-radius:4px;background:{$primary}\"></span>" . $bar('34px', '#475569', '6px') . '</span>';
    $menu = fn (string $color = '#94a3b8') => '<span style="display:flex;align-items:center;gap:7px">' . str_repeat($bar('20px', $color), 5) . '</span>';
    $button = fn (string $radius) => "<span style=\"display:block;width:44px;height:14px;border-radius:{$radius};background:{$accent}\"></span>";
    $contact = '<span style="display:flex;align-items:center;gap:4px"><span style="width:9px;height:9px;border-radius:50%;border:1.5px solid ' . $primary . '"></span><span style="display:grid;gap:2px">' . $bar('16px', '#cbd5e1', '3px') . $bar('34px', '#475569', '4px') . '</span></span>';
    $row = 'display:flex;align-items:center;justify-content:space-between;padding:8px 10px;background:#fff';

    $previews = [
        'info_bar' => "<div style=\"{$row}\">{$logo}<span style=\"display:flex;align-items:center;gap:10px\">{$contact}{$contact}{$button('2px')}</span></div>"
            . "<div style=\"margin:0 10px;padding:6px 8px;background:{$primary}\">{$menu('#ffffff')}</div>",
        'classic' => "<div style=\"{$row}\">{$logo}<span style=\"display:flex;align-items:center;gap:10px\">{$menu()}{$button('4px')}</span></div>",
        'top_bar' => "<div style=\"display:flex;align-items:center;justify-content:space-between;padding:5px 10px;background:{$primary}\">"
            . '<span style="display:flex;gap:5px">' . str_repeat('<span style="width:7px;height:7px;border-radius:50%;background:#fff"></span>', 3) . '</span>'
            . '<span style="display:flex;gap:10px">' . $bar('44px', '#ffffff', '4px') . $bar('52px', '#ffffff', '4px') . '</span></div>'
            . "<div style=\"{$row}\">{$logo}<span style=\"display:flex;align-items:center;gap:10px\">{$menu()}{$button('999px')}</span></div>",
    ];
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        x-data="{ state: $wire.$entangle('{{ $getStatePath() }}') }"
        style="display:grid;gap:1rem;grid-template-columns:repeat(auto-fit,minmax(260px,1fr))"
    >
        @foreach ($layouts as $value => $layout)
            <label
                style="display:block;cursor:pointer;border-radius:0.75rem;border:2px solid #e5e7eb;overflow:hidden;transition:border-color .15s, box-shadow .15s"
                x-bind:style="state === '{{ $value }}' ? 'border-color: {{ $primary }}; box-shadow: 0 0 0 3px {{ $primary }}22' : ''"
            >
                <input
                    type="radio"
                    name="{{ $getId() }}"
                    value="{{ $value }}"
                    x-model="state"
                    style="position:absolute;opacity:0;width:1px;height:1px"
                />

                {{-- Miniature drawing of the design, in the site's brand colours --}}
                <div style="background:#f1f5f9;padding:14px 12px 0">
                    <div style="border-radius:6px 6px 0 0;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.12);background:#fff">
                        {!! $previews[$value] !!}
                        <div style="height:26px;background:linear-gradient(135deg,#1e293b,#475569)"></div>
                    </div>
                </div>

                <div style="padding:0.75rem 1rem;border-top:1px solid #e5e7eb">
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:0.5rem">
                        <span style="font-weight:600;font-size:0.875rem">{{ $layout['label'] }}</span>
                        <span
                            x-show="state === '{{ $value }}'"
                            x-cloak
                            style="font-size:0.7rem;font-weight:600;padding:2px 8px;border-radius:999px;color:#fff;background:{{ $primary }}"
                        >Selected</span>
                    </div>
                    <p style="margin-top:0.25rem;font-size:0.8rem;line-height:1.35;opacity:.7">{{ $layout['description'] }}</p>
                </div>
            </label>
        @endforeach
    </div>
</x-dynamic-component>
