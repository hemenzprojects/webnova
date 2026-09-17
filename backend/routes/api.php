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

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Public API routes
Route::prefix('v1')->group(function () {
    // Media Upload
    Route::post('/media/upload', [MediaController::class, 'upload']);
    Route::post('/media/delete', [MediaController::class, 'delete']);

    // Pages
    Route::get('/pages', [PageController::class, 'index']);
    Route::get('/pages/{id}/edit', [PageController::class, 'edit']);
    Route::put('/pages/{id}/blocks', [PageController::class, 'updateBlocks']);
    Route::post('/pages/{id}/publish', [PageController::class, 'publish']);
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

    // Menus
    Route::get('/menus', [MenuController::class, 'index']);
    Route::get('/menus/{location}', [MenuController::class, 'show']);

    // Contact Form
    Route::post('/contact-form', [ContactFormController::class, 'store']);
});
