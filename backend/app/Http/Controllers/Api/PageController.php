<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\News;
use App\Models\Event;
use App\Models\Service;
use App\Models\Member;
use App\Models\TeamMember;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function index()
    {
        $pages = Page::where('is_published', true)
            ->orderBy('order')
            ->select('id', 'title', 'slug', 'excerpt', 'featured_image', 'order')
            ->get();

        return response()->json($pages);
    }

    public function show($slug)
    {
        $page = Page::where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();

        // Transform blocks if using page builder
        if ($page->usesBuilder()) {
            $page->blocks = $this->transformBlocks($page->blocks);
        }

        return response()->json($page);
    }

    /**
     * Get page for editing in visual editor
     */
    public function edit($id)
    {
        $page = Page::findOrFail($id);

        // Transform blocks to include dynamic data for preview
        if ($page->usesBuilder()) {
            $page->blocks = $this->transformBlocks($page->blocks);
        }

        return response()->json($page);
    }

    /**
     * Update page blocks from visual editor
     */
    public function updateBlocks(Request $request, $id)
    {
        $page = Page::findOrFail($id);

        $validated = $request->validate([
            'blocks' => 'required|array',
        ]);

        $page->update([
            'blocks' => $validated['blocks'],
            'template_type' => 'builder',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Page blocks updated successfully',
            'page' => $page
        ]);
    }

    /**
     * Publish page from visual editor
     */
    public function publish(Request $request, $id)
    {
        $page = Page::findOrFail($id);

        $page->update([
            'is_published' => true
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Page published successfully',
            'page' => $page
        ]);
    }

    /**
     * Transform blocks to include dynamic data
     */
    protected function transformBlocks(?array $blocks): array
    {
        if (!$blocks) {
            return [];
        }

        return collect($blocks)->map(function ($block) {
            // Inject dynamic data for dynamic blocks
            switch ($block['type']) {
                case 'dynamic_news':
                    $block['data']['items'] = $this->fetchNewsForBlock($block['data']);
                    break;
                case 'dynamic_events':
                    $block['data']['items'] = $this->fetchEventsForBlock($block['data']);
                    break;
                case 'dynamic_services':
                    $block['data']['items'] = $this->fetchServicesForBlock($block['data']);
                    break;
                case 'dynamic_members':
                    $block['data']['items'] = $this->fetchMembersForBlock($block['data']);
                    break;
                case 'dynamic_team_members':
                    $block['data']['items'] = $this->fetchTeamMembersForBlock($block['data']);
                    break;
                case 'dynamic_carousel':
                    $block['data']['items'] = $this->fetchCarouselItems($block['data']);
                    break;
            }

            return $block;
        })->toArray();
    }

    /**
     * Fetch news items for dynamic news block
     */
    protected function fetchNewsForBlock(array $config): array
    {
        return News::where('is_published', true)
            ->orderBy('published_at', 'desc')
            ->limit($config['limit'] ?? 3)
            ->select('id', 'title', 'slug', 'excerpt', 'featured_image', 'published_at', 'category')
            ->get()
            ->toArray();
    }

    /**
     * Fetch events for dynamic events block
     */
    protected function fetchEventsForBlock(array $config): array
    {
        $query = Event::where('is_published', true);

        if (($config['filter'] ?? 'upcoming') === 'upcoming') {
            $query->where('start_date', '>=', now());
        } elseif ($config['filter'] === 'past') {
            $query->where('start_date', '<', now());
        }

        return $query->orderBy('start_date', 'asc')
            ->limit($config['limit'] ?? 4)
            ->select('id', 'title', 'slug', 'description', 'location', 'venue', 'featured_image', 'start_date', 'end_date')
            ->get()
            ->toArray();
    }

    /**
     * Fetch services for dynamic services block
     */
    protected function fetchServicesForBlock(array $config): array
    {
        $limit = ($config['limit'] ?? '6') === 'all' ? 999 : (int) $config['limit'];

        return Service::where('is_active', true)
            ->orderBy('order')
            ->limit($limit)
            ->select('id', 'name', 'slug', 'description', 'icon', 'featured_image')
            ->get()
            ->toArray();
    }

    /**
     * Fetch members for dynamic members block
     */
    protected function fetchMembersForBlock(array $config): array
    {
        $limit = ($config['limit'] ?? '12') === 'all' ? 999 : (int) $config['limit'];

        return Member::where('is_active', true)
            ->orderBy('name')
            ->limit($limit)
            ->select('id', 'name', 'slug', 'type', 'description', 'logo', 'website')
            ->get()
            ->toArray();
    }

    /**
     * Fetch team members for dynamic team members block
     */
    protected function fetchTeamMembersForBlock(array $config): array
    {
        $limit = ($config['limit'] ?? '4') === 'all' ? 999 : (int) $config['limit'];

        return TeamMember::where('is_active', true)
            ->orderBy('order')
            ->limit($limit)
            ->select('id', 'name', 'slug', 'role', 'bio', 'photo', 'email', 'phone', 'social_links')
            ->get()
            ->toArray();
    }

    /**
     * Fetch items for dynamic carousel block
     */
    protected function fetchCarouselItems(array $config): array
    {
        $contentType = $config['contentType'] ?? 'members';

        switch ($contentType) {
            case 'members':
                return $this->fetchMembersForBlock($config);
            case 'services':
                return $this->fetchServicesForBlock($config);
            case 'news':
                return $this->fetchNewsForBlock($config);
            case 'events':
                return $this->fetchEventsForBlock($config);
            case 'team_members':
                return $this->fetchTeamMembersForBlock($config);
            default:
                return [];
        }
    }
}
