<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaController extends Controller
{
    public function index(Request $request)
    {
        $query = Media::latest();

        if ($search = $request->get('search')) {
            $query->where('original_name', 'ilike', "%{$search}%");
        }

        if ($type = $request->get('type')) {
            $query->where('mime_type', 'like', "{$type}/%");
        }

        $media = $query->paginate(40);

        return response()->json([
            'data' => $media->items(),
            'meta' => [
                'current_page' => $media->currentPage(),
                'last_page'    => $media->lastPage(),
                'total'        => $media->total(),
            ],
        ]);
    }

    public function upload(Request $request)
    {
        $request->validate([
            'image'  => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'type'   => 'nullable|string|in:hero,card,featured,general,media',
            'folder' => 'nullable|string|max:100|regex:/^[a-z0-9\-_\/]+$/i',
        ]);

        $image    = $request->file('image');
        $type     = $request->input('type', 'general');
        $folder   = $request->input('folder');
        $filename = Str::ulid() . '.' . $image->getClientOriginalExtension();

        $tenantId = tenancy()->tenant?->id;
        $dir = $folder
            ? ($tenantId ? "{$tenantId}/{$folder}" : $folder)
            : ($tenantId ? "{$tenantId}/pages/{$type}" : "pages/{$type}");

        $path = $image->storeAs($dir, $filename, 'public');

        $record = Media::create([
            'filename'      => $filename,
            'original_name' => $image->getClientOriginalName(),
            'path'          => $path,
            'disk'          => 'public',
            'mime_type'     => $image->getMimeType(),
            'size'          => $image->getSize(),
        ]);

        return response()->json([
            'success'  => true,
            'id'       => $record->id,
            'url'      => $path,
            'path'     => $path,
            'filename' => $filename,
        ]);
    }

    public function destroy(int $id)
    {
        $record = Media::findOrFail($id);
        Storage::disk($record->disk)->delete($record->path);
        $record->delete();

        return response()->json(['success' => true]);
    }

    public function delete(Request $request)
    {
        $request->validate(['path' => 'required|string']);

        $path     = $request->input('path');
        $tenantId = tenancy()->tenant?->id;

        // Validate path belongs to current tenant (or unscoped paths)
        $allowed = $tenantId
            ? Str::startsWith($path, [$tenantId . '/', 'pages/'])
            : Str::startsWith($path, 'pages/');

        if (!$allowed) {
            return response()->json(['success' => false, 'message' => 'Invalid file path'], 403);
        }

        Media::where('path', $path)->delete();

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }

        return response()->json(['success' => true, 'message' => 'File deleted']);
    }
}