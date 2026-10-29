<?php

namespace App\Plugins\ComingSoon\Http;

use App\Http\Controllers\Controller;
use App\Plugins\ComingSoon\Support\ComingSoonSettings;
use App\Plugins\PluginManager;
use App\Support\SocialPlatforms;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ComingSoonController extends Controller
{
    /**
     * Whether the public site should show the coming soon screen. Runs on the
     * admin session (web middleware): signed-in admins get "preview" instead,
     * so they keep seeing the real site, unless they ask to see the screen
     * with ?preview=1.
     */
    public function show(Request $request, PluginManager $plugins): JsonResponse
    {
        if (! $plugins->isActive('coming_soon')) {
            return response()->json(['enabled' => false, 'preview' => false]);
        }

        $user = auth()->guard('web')->user();
        if ($user && $user->role_id !== null && ! $request->boolean('preview')) {
            return response()->json(['enabled' => false, 'preview' => true, 'mode' => ComingSoonSettings::get('mode')]);
        }

        $settings = ComingSoonSettings::forForm();

        return response()->json([
            'enabled' => true,
            'preview' => false,
            'mode' => $settings['mode'],
            'heading' => $settings['heading'],
            'message' => $settings['message'],
            'launch_at' => $settings['launch_at'] ? Carbon::parse($settings['launch_at'])->toIso8601String() : null,
            'background_image' => $settings['background_image'],
            'show_logo' => (bool) $settings['show_logo'],
            'contact' => $settings['show_contact'] ? [
                'heading' => $settings['contact_heading'],
                'email' => $settings['contact_email'] ?: null,
                'phone' => $settings['contact_phone'] ?: null,
                'address' => $settings['contact_address'] ?: null,
            ] : null,
            'social' => $settings['show_social'] ? SocialPlatforms::links($settings['social_links'] ?? []) : [],
        ])->header('Cache-Control', 'no-store');
    }
}
