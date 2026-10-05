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
use App\Http\Controllers\Api\FooterController;
use App\Http\Controllers\Api\HeaderController;
use App\Http\Controllers\Api\ThemeController;
use App\Plugins\Membership\Http\MembershipController;
use App\Plugins\PluginManager;

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
    Route::get('/pages/{slug}', [PageController::class, 'show']);

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

    // Menus
    Route::get('/menus', [MenuController::class, 'index']);
    Route::get('/menus/{location}', [MenuController::class, 'show']);

    // Header
    Route::get('/header', [HeaderController::class, 'index']);

    // Footer
    Route::get('/footer', [FooterController::class, 'index']);

    // Contact Form
    Route::post('/contact-form', [ContactFormController::class, 'store']);

    // Plugins switched on for this site (the frontend hides blocks of inactive ones)
    Route::get('/plugins', fn (PluginManager $plugins) => response()->json(['active' => $plugins->activeKeys()]));

    // Membership plugin
    Route::prefix('membership')->middleware('plugin:membership')->group(function () {
        Route::get('/form', [MembershipController::class, 'form']);
        Route::post('/register', [MembershipController::class, 'register'])->middleware('throttle:10,1');
        Route::get('/registrations/{reference}', [MembershipController::class, 'status']);
        Route::post('/registrations/{reference}/pay', [MembershipController::class, 'retryPayment'])->middleware('throttle:10,1');
        Route::post('/paystack/webhook', [MembershipController::class, 'paystackWebhook']);
    });
});
