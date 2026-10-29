<x-mail::message :site-name="$siteName" :site-url="$siteUrl">
@if ($kind === 'welcome')
# Welcome to {{ $siteName }}

Dear {{ $account->name }},

Your membership has been approved. You now have access to the member portal.

<x-mail::panel>
**Member ID:** {{ $account->member_number }}
@if ($account->membershipType)
<br>**Membership:** {{ $account->membershipType->name }}
@endif
<br>**Sign-in email:** {{ $account->email }}
</x-mail::panel>

To get started, choose your password:

<x-mail::button :url="$link">
Set your password
</x-mail::button>

This link is valid for 14 days. If it has expired, use "Forgot your password?" on the login page.
@else
# Reset your password

Dear {{ $account->name }},

We received a request to reset the password for your {{ $siteName }} member account.

<x-mail::button :url="$link">
Choose a new password
</x-mail::button>

This link is valid for 60 minutes. If you did not ask for this, you can ignore this email: your password stays the same.
@endif

Kind regards,<br>
{{ $siteName }}
</x-mail::message>
