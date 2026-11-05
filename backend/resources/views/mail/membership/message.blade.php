{{-- A Membership email (App\Plugins\Membership\Mail\MembershipMail): the site's Markdown text, with the button and member details drawn as mail components. Keep the lines unindented: four spaces would make them code blocks. --}}
<x-mail::message :site-name="$siteName" :site-url="$siteUrl">
@foreach ($parts as $part)
@if ($part['type'] === 'button')
<x-mail::button :url="$button['url']">
{{ $button['label'] }}
</x-mail::button>

@elseif ($part['type'] === 'details')
<x-mail::panel>
@foreach ($details as $label => $value)
**{{ $label }}:** {{ $value }}{{ $loop->last ? '' : '  ' }}
@endforeach
</x-mail::panel>

@else
{!! $part['text'] !!}

@endif
@endforeach
</x-mail::message>
