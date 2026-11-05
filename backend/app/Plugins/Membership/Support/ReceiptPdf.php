<?php

namespace App\Plugins\Membership\Support;

use App\Models\Branding;
use App\Plugins\Membership\Models\MembershipPayment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/**
 * A membership payment's receipt as a PDF (resources/views/membership/receipt-pdf.blade.php):
 * downloaded from the website, the member portal and the admin, and attached to the receipt email.
 */
class ReceiptPdf
{
    public function render(MembershipPayment $payment): string
    {
        $payment->loadMissing(['memberAccount', 'membershipType', 'registration']);
        $branding = Branding::settings();

        return Pdf::loadView('membership.receipt-pdf', [
            'payment' => $payment,
            'receipt' => $payment->toReceiptArray(),
            'siteName' => trim((string) $branding->site_name) ?: (string) tenant()?->getTenantKey(),
            'logo' => $this->logo($branding->logo),
            'color' => preg_match('/^#[0-9a-f]{6}$/i', (string) $branding->primary_color) ? $branding->primary_color : '#111827',
            'contact' => array_filter([$branding->contact_address, $branding->contact_phone, $branding->contact_email], 'filled'),
            'siteUrl' => MembershipSettings::siteUrl(),
        ])->setPaper('a4')->output();
    }

    public function filename(MembershipPayment $payment): string
    {
        return 'Receipt-' . preg_replace('/[^A-Za-z0-9-]/', '', $payment->receipt_number) . '.pdf';
    }

    public function download(MembershipPayment $payment): Response
    {
        return response($this->render($payment), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $this->filename($payment) . '"',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /** The site's logo embedded in the PDF (dompdf is not allowed to fetch URLs); only the site's own files */
    private function logo(?string $path): ?string
    {
        $tenant = (string) tenant()?->getTenantKey();
        if (blank($path) || ! str_starts_with($path, $tenant . '/') || str_contains($path, '..')) {
            return null;
        }
        $disk = Storage::disk('public');
        if (! $disk->exists($path) || $disk->size($path) > 2 * 1024 * 1024) {
            return null;
        }
        $mime = $disk->mimeType($path);
        if (! in_array($mime, ['image/png', 'image/jpeg', 'image/gif'], true)) {
            return null; // SVG and WebP do not render reliably in dompdf
        }

        return 'data:' . $mime . ';base64,' . base64_encode($disk->get($path));
    }
}
