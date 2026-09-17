<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TeamMember;
use Illuminate\Http\Request;

class TeamMemberController extends Controller
{
    public function index(Request $request)
    {
        $query = TeamMember::where('is_active', true);

        $teamMembers = $query->orderBy('order')
            ->select('id', 'name', 'slug', 'role', 'bio', 'photo', 'email', 'phone', 'social_links', 'order')
            ->get();

        return response()->json($teamMembers);
    }

    public function show($slug)
    {
        $teamMember = TeamMember::where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        return response()->json($teamMember);
    }
}
