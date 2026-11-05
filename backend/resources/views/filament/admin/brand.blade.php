{{-- The site's logo and name in the admin top bar and on the sign-in page. Inline styles: see areas-topbar. --}}
<span style="display:flex;align-items:center;gap:.6rem">
    @if ($logo)
        <img src="{{ $logo }}" alt="" style="height:2rem;width:auto;max-width:8rem;object-fit:contain">
    @endif
    <span style="font-size:1.05rem;font-weight:700;letter-spacing:-.01em;color:rgb(var(--gray-950));white-space:nowrap">{{ $name }}</span>
</span>
