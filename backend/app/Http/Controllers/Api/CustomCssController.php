<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;

class CustomCssController extends Controller
{
    /**
     * The site's custom CSS (Appearance → Custom CSS), or an empty string while it is switched off
     */
    public function index()
    {
        $enabled = filter_var(Setting::get('custom_css_enabled', true), FILTER_VALIDATE_BOOLEAN);
        $css = $enabled ? (string) Setting::get('custom_css', '') : '';

        // The CSS goes inside a <style> tag: "<" is never needed in CSS outside strings, and \3c
        // is the same character inside one, so this keeps it from closing the tag
        return response()->json(['css' => str_replace('<', '\\3c ', $css)]);
    }
}
