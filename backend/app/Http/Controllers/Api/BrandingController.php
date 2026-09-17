<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branding;
use Illuminate\Http\Request;

class BrandingController extends Controller
{
    /**
     * Get branding settings
     */
    public function index()
    {
        $branding = Branding::settings();

        return response()->json($branding);
    }
}
