<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PageController;
use App\Http\Controllers\Api\NewsController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\MemberController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\TeamMemberController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\BrandingController;
use App\Http\Controllers\Api\ContactFormController;
use App\Http\Controllers\Api\MediaController;
use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\CustomCssController;
use App\Http\Controllers\Api\FooterController;
use App\Http\Controllers\Api\HeaderController;
use App\Http\Controllers\Api\ThemeController;
use App\Plugins\ComingSoon\Http\ComingSoonController;
use App\Plugins\Donations\Http\DonationController;
use App\Plugins\Membership\Http\MemberAccountController;
use App\Plugins\Membership\Http\MembershipController;
use App\Plugins\PluginManager;
use App\Services\SiteHealth;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Public API routes
Route::prefix('v1')->group(function () {
    // Media library: admins only (signed in to /admin; uses the admin session)
    Route::middleware(['web', 'admin.can:media,view'])->get('/media', [MediaController::class, 'index']);
    Route::middleware(['web', 'admin.can:media,manage'])->group(function () {
        Route::post('/media/upload', [MediaController::class, 'upload']);
        Route::delete('/media/{id}', [MediaController::class, 'destroy']);
        Route::post('/media/delete', [MediaController::class, 'delete']); // legacy
    });

    // Pages
    Route::get('/pages', [PageController::class, 'index']);
    // Page editor: admins only
    Route::middleware(['web', 'admin.can:pages,view'])->get('/pages/{id}/edit', [PageController::class, 'edit']);
    Route::middleware(['web', 'admin.can:pages,manage'])->group(function () {
        Route::put('/pages/{id}/blocks', [PageController::class, 'updateBlocks']);
        Route::post('/pages/{id}/publish', [PageController::class, 'publish']);
    });
    // On the web session: members-only pages need the member portal's (or the admin's) sign-in
    Route::middleware('web')->get('/pages/{slug}', [PageController::class, 'show']);

    // News
    Route::get('/news', [NewsController::class, 'index']);
    Route::get('/news/{slug}', [NewsController::class, 'show']);

    // Events
    Route::get('/events', [EventController::class, 'index']);
    Route::get('/events/{slug}', [EventController::class, 'show']);

    // Members
    Route::get('/members', [MemberController::class, 'index']);
    Route::get('/members/{slug}', [MemberController::class, 'show']);

    // Services
    Route::get('/services', [ServiceController::class, 'index']);
    Route::get('/services/{slug}', [ServiceController::class, 'show']);

    // Team Members
    Route::get('/team-members', [TeamMemberController::class, 'index']);
    Route::get('/team-members/{slug}', [TeamMemberController::class, 'show']);

    // Settings
    Route::get('/settings', [SettingController::class, 'index']);
    Route::get('/settings/{key}', [SettingController::class, 'show']);

    // Branding
    Route::get('/branding', [BrandingController::class, 'index']);

    // Theme
    Route::get('/theme', [ThemeController::class, 'index']);
    Route::get('/themes/{slug}/screenshot', [ThemeController::class, 'screenshot']);

    // Menus
    Route::get('/menus', [MenuController::class, 'index']);
    Route::get('/menus/{location}', [MenuController::class, 'show']);

    // Header
    Route::get('/header', [HeaderController::class, 'index']);

    // Footer
    Route::get('/footer', [FooterController::class, 'index']);

    // Custom CSS (Appearance → Custom CSS)
    Route::get('/custom-css', [CustomCssController::class, 'index']);

    // Page-view beacon from the public site (Platform → Site Health)
    Route::post('/track', function (Request $request, SiteHealth $health) {
        $data = $request->validate(['path' => 'required|string|max:500']);
        $health->recordPageView(tenancy()->tenant?->id, $data['path']);

        return response()->noContent();
    })->middleware('throttle:120,1');

    // Contact Form
    Route::post('/contact-form', [ContactFormController::class, 'store']);

    // Plugins switched on for this site (the frontend hides blocks of inactive ones)
    Route::get('/plugins', fn (PluginManager $plugins) => response()->json([
        'active' => $plugins->activeKeys(),
        // Switched-on features of each active plugin, e.g. {"membership": ["accounts"]}
        'features' => (object) collect($plugins->activeKeys())->mapWithKeys(fn ($key) => [$key => $plugins->activeFeatures($key)])->filter()->all(),
    ]));

    // Coming Soon plugin: whether to hide the public site. Uses the admin
    // session, so signed-in admins still see the real site.
    Route::middleware('web')->get('/coming-soon', [ComingSoonController::class, 'show']);

    // Membership plugin
    Route::prefix('membership')->middleware('plugin:membership')->group(function () {
        Route::get('/form', [MembershipController::class, 'form']);
        Route::post('/register', [MembershipController::class, 'register'])->middleware('throttle:10,1');
        Route::get('/registrations/{reference}', [MembershipController::class, 'status']);
        Route::post('/registrations/{reference}/pay', [MembershipController::class, 'retryPayment'])->middleware('throttle:10,1');
        // Application documents: admins only, on the admin session
        Route::middleware(['web', 'admin.can:membership-registrations,view'])
            ->get('/registrations/{reference}/files/{key}', [MembershipController::class, 'file']);
        Route::post('/paystack/webhook', [MembershipController::class, 'paystackWebhook']);

        // Member portal (Member accounts feature): session sign-in on the "member" guard
        Route::prefix('account')->middleware(['web', 'plugin:membership,accounts'])->group(function () {
            Route::get('/', [MemberAccountController::class, 'show']);
            Route::post('/login', [MemberAccountController::class, 'login'])->middleware('throttle:20,1');
            Route::post('/logout', [MemberAccountController::class, 'logout']);
            Route::post('/forgot-password', [MemberAccountController::class, 'forgotPassword'])->middleware('throttle:5,1');
            Route::post('/reset-password', [MemberAccountController::class, 'resetPassword'])->middleware('throttle:10,1');
            Route::post('/password', [MemberAccountController::class, 'changePassword'])->middleware('throttle:10,1');
            Route::get('/pages', [MemberAccountController::class, 'pages']);
        });
    });

    // Donations plugin
    Route::prefix('donations')->middleware('plugin:donations')->group(function () {
        Route::get('/form', [DonationController::class, 'form']);
        Route::post('/', [DonationController::class, 'donate'])->middleware('throttle:10,1');
        Route::post('/paystack/webhook', [DonationController::class, 'paystackWebhook']);
        Route::get('/{reference}', [DonationController::class, 'status']);
        Route::post('/{reference}/pay', [DonationController::class, 'retryPayment'])->middleware('throttle:10,1');
    });
});
