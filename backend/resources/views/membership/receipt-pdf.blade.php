{{-- A membership payment receipt (App\Plugins\Membership\Support\ReceiptPdf), rendered by dompdf: simple tables and inline styles only --}}
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Receipt {{ $receipt['receipt_number'] }}</title>
<style>
    @page { margin: 36px 44px; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; }
    table { width: 100%; border-collapse: collapse; }
    .muted { color: #6b7280; }
    .label { font-size: 9px; text-transform: uppercase; letter-spacing: 1px; color: #6b7280; }
    .mono { font-family: DejaVu Sans Mono, monospace; }
    .bar { height: 6px; background: {{ $color }}; margin-bottom: 24px; }
    .items th { text-align: left; font-size: 9px; text-transform: uppercase; letter-spacing: 1px; color: #6b7280; padding: 6px 0; border-bottom: 1px solid #e5e7eb; }
    .items td { padding: 10px 0; border-bottom: 1px solid #f3f4f6; vertical-align: top; }
    .total td { border-top: 2px solid #111827; border-bottom: 0; font-weight: bold; font-size: 13px; padding-top: 10px; }
    .details td { padding: 4px 0; vertical-align: top; }
    .paid { display: inline-block; border: 2px solid #15803d; color: #15803d; font-weight: bold; padding: 4px 12px; letter-spacing: 2px; }
</style>
</head>
<body>
<div class="bar"></div>

<table>
    <tr>
        <td style="width: 60%; vertical-align: top;">
            @if ($logo)
                <img src="{{ $logo }}" style="max-height: 56px; max-width: 220px; margin-bottom: 6px;"><br>
            @endif
            <div style="font-size: 16px; font-weight: bold;">{{ $siteName }}</div>
            @foreach ($contact as $line)
                <div class="muted">{{ $line }}</div>
            @endforeach
        </td>
        <td style="width: 40%; text-align: right; vertical-align: top;">
            <div style="font-size: 22px; font-weight: bold; color: {{ $color }};">RECEIPT</div>
            <div class="mono" style="font-size: 13px; font-weight: bold; margin-top: 4px;">{{ $receipt['receipt_number'] }}</div>
            <div class="muted">{{ $payment->paid_at?->format('j F Y') }}</div>
            <div style="margin-top: 10px;"><span class="paid">PAID</span></div>
        </td>
    </tr>
</table>

<table style="margin-top: 28px;">
    <tr>
        <td style="width: 60%; vertical-align: top;">
            <div class="label">Received from</div>
            <div style="font-size: 13px; font-weight: bold; margin-top: 3px;">{{ $receipt['name'] ?: '—' }}</div>
            @if ($receipt['email'])<div class="muted">{{ $receipt['email'] }}</div>@endif
        </td>
        <td style="width: 40%; text-align: right; vertical-align: top;">
            @if ($receipt['member_id'])
                <div class="label">Member ID</div>
                <div class="mono" style="font-size: 15px; font-weight: bold; margin-top: 3px;">{{ $receipt['member_id'] }}</div>
            @endif
        </td>
    </tr>
</table>

<table class="items" style="margin-top: 28px;">
    <tr><th>Description</th><th style="text-align: right;">Amount</th></tr>
    <tr>
        <td>
            {{ $receipt['purpose'] }}, {{ $receipt['year'] }}
            @if ($receipt['membership_type'])<br><span class="muted">{{ $receipt['membership_type'] }} membership, 1 January to 31 December {{ $receipt['year'] }}</span>@endif
        </td>
        <td style="text-align: right; white-space: nowrap;">{{ $payment->formattedAmount() }}</td>
    </tr>
    <tr class="total"><td>Total paid</td><td style="text-align: right;">{{ $payment->formattedAmount() }}</td></tr>
</table>

<table class="details" style="margin-top: 24px;">
    <tr><td class="muted" style="width: 30%;">Payment method</td><td>{{ $receipt['method'] }}</td></tr>
    @if ($receipt['reference'])<tr><td class="muted">Payment reference</td><td class="mono">{{ $receipt['reference'] }}</td></tr>@endif
    @if ($receipt['application_reference'])<tr><td class="muted">Application reference</td><td class="mono">{{ $receipt['application_reference'] }}</td></tr>@endif
    <tr><td class="muted">Date paid</td><td>{{ $payment->paid_at?->format('j F Y, H:i') }}</td></tr>
</table>

<div style="margin-top: 48px; padding-top: 12px; border-top: 1px solid #e5e7eb;" class="muted">
    Thank you for your payment. This receipt was issued by {{ $siteName }}'s membership system and is valid without a signature.
    Keep it as proof of payment. {{ $siteUrl }}
</div>
</body>
</html>
