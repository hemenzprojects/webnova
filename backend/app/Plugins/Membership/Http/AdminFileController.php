<?php

namespace App\Plugins\Membership\Http;

use App\Http\Controllers\Controller;
use App\Plugins\Membership\Models\MemberDocument;
use App\Plugins\Membership\Models\MemberResource;
use App\Plugins\Membership\Support\PrivateFiles;

/**
 * Private Membership files for admins (on the admin session; the route
 * checks the role): members' documents and member resources.
 */
class AdminFileController extends Controller
{
    public function receipt(int $id, \App\Plugins\Membership\Support\ReceiptPdf $pdf)
    {
        return $pdf->download(\App\Plugins\Membership\Models\MembershipPayment::findOrFail($id));
    }

    public function memberDocument(int $id)
    {
        $document = MemberDocument::findOrFail($id);

        return PrivateFiles::download($document->file, $document->name);
    }

    public function memberResource(int $id)
    {
        $resource = MemberResource::findOrFail($id);

        return PrivateFiles::download($resource->file, $resource->name);
    }
}
