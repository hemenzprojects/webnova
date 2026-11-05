<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index(Request $request)
    {
        $query = Service::where('is_active', true);

        if ($request->has('featured')) {
            $query->where('is_featured', true);
        }

        if ($request->get('sort') === 'name') {
            $query->orderBy('name');
        } else {
            $query->orderBy('order');
        }

        if ($request->filled('limit')) {
            $query->limit(max(1, (int) $request->limit));
        }

        $services = $query
            ->select('id', 'name', 'slug', 'description', 'icon', 'category', 'price_label', 'featured_image', 'is_featured', 'order')
            ->get();

        return response()->json($services);
    }

    public function show($slug)
    {
        $service = Service::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        return response()->json($service);
    }
}
