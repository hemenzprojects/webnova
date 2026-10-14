<?php

namespace App\Http\Controllers\Api;

use App\Filament\Pages\HeaderSettings;
use App\Http\Controllers\Controller;
use App\Models\Branding;
use App\Models\Setting;
use App\Support\SocialPlatforms;

class HeaderController extends Controller
{
    /**
     * Get the header design and the details it displays
     */
    public function index()
    {
        $settings = Setting::where('group', HeaderSettings::GROUP)->pluck('value', 'key');
        $branding = Branding::settings();

        $get = fn (string $key) => $settings->has($key) ? $settings[$key] : HeaderSettings::DEFAULTS[$key][0];
        $flag = fn (string $key) => filter_var($get($key), FILTER_VALIDATE_BOOLEAN);

        $layout = $get('header_layout');
        $button = fn (string $prefix) => $flag("{$prefix}_enabled") && filled($get("{$prefix}_text")) && filled($get("{$prefix}_url"))
            ? [
                'text' => $get("{$prefix}_text"),
                'url' => $get("{$prefix}_url"),
                'new_tab' => $flag("{$prefix}_new_tab"),
            ]
            : null;

        return response()->json([
            'layout' => array_key_exists($layout, HeaderSettings::LAYOUTS) ? $layout : 'classic',
            'sticky' => $flag('header_sticky'),
            'show_site_name' => $flag('header_show_site_name'),
            'cta' => $button('header_cta'),
            'cta2' => $button('header_cta2'),
            // Contact details and social links are kept under Branding; the header chooses which to show
            'phone' => $flag('header_show_phone') ? ($branding->contact_phone ?: null) : null,
            'email' => $flag('header_show_email') ? ($branding->contact_email ?: null) : null,
            'social' => $flag('header_show_social') ? SocialPlatforms::fromBranding($branding) : [],
        ]);
    }
}
