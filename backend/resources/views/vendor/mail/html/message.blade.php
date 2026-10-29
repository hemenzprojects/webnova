{{-- Laravel's mail layout, with the sending site's name instead of the platform's (pass site-name / site-url) --}}
@props(['siteName' => null, 'siteUrl' => null])
<x-mail::layout>
{{-- Header --}}
<x-slot:header>
<x-mail::header :url="$siteUrl ?? config('app.url')">
{{ $siteName ?? config('app.name') }}
</x-mail::header>
</x-slot:header>

{{-- Body --}}
{!! $slot !!}

{{-- Subcopy --}}
@isset($subcopy)
<x-slot:subcopy>
<x-mail::subcopy>
{!! $subcopy !!}
</x-mail::subcopy>
</x-slot:subcopy>
@endisset

{{-- Footer --}}
<x-slot:footer>
<x-mail::footer>
© {{ date('Y') }} {{ $siteName ?? config('app.name') }}. {{ __('All rights reserved.') }}
</x-mail::footer>
</x-slot:footer>
</x-mail::layout>
