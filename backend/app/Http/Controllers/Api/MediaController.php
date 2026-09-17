<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaController extends Controller
{
    /**
     * Upload an image file
     */
    public function upload(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // 5MB max
            'type' => 'nullable|string|in:hero,card,featured,general',
        ]);

        $image = $request->file('image');
        $type = $request->input('type', 'general');

        // Generate a unique filename
        $filename = Str::ulid() . '.' . $image->getClientOriginalExtension();

        // Store in public disk under pages/{type} directory
        $path = $image->storeAs("pages/{$type}", $filename, 'public');

        // Return the full URL
        $url = Storage::disk('public')->url($path);

        // Ensure we return the full URL with domain
        $fullUrl = url($url);

        return response()->json([
            'success' => true,
            'url' => $fullUrl,
            'path' => $path,
            'filename' => $filename
        ]);
    }

    /**
     * Delete an uploaded image
     */
    public function delete(Request $request)
    {
        $request->validate([
            'path' => 'required|string',
        ]);

        $path = $request->input('path');

        // Ensure the path is within the pages directory for security
        if (!Str::startsWith($path, 'pages/')) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid file path'
            ], 403);
        }

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);

            return response()->json([
                'success' => true,
                'message' => 'Image deleted successfully'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'File not found'
        ], 404);
    }
}