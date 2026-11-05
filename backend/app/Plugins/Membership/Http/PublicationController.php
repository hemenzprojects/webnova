<?php

namespace App\Plugins\Membership\Http;

use App\Http\Controllers\Controller;
use App\Plugins\Membership\Models\MemberAccount;
use App\Plugins\Membership\Models\MemberPublication;
use App\Plugins\Membership\Support\Publications;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * Member updates (Membership): the website's feed of published updates, and
 * the member portal's "My submissions" (member guard, web session).
 */
class PublicationController extends Controller
{
    public function __construct(private Publications $publications) {}

    /** Published updates, newest first (featured ones first when asked) */
    public function feed(Request $request): JsonResponse
    {
        $data = $request->validate([
            'kind' => ['nullable', Rule::in(array_keys(MemberPublication::KINDS))],
            'limit' => 'nullable|integer|min:1|max:48',
            'featured_first' => 'nullable|boolean',
        ]);

        $items = MemberPublication::published()
            ->with('memberAccount.profile')
            ->when($data['kind'] ?? null, fn ($q, $kind) => $q->where('kind', $kind))
            ->when($request->boolean('featured_first'), fn ($q) => $q->orderByDesc('is_featured'))
            ->orderByDesc('published_at')
            ->paginate((int) ($data['limit'] ?? 9));

        return response()->json([
            'items' => collect($items->items())->map->toPublicArray(),
            'kinds' => MemberPublication::KINDS,
            'page' => $items->currentPage(),
            'last_page' => $items->lastPage(),
        ])->header('Cache-Control', 'public, max-age=60');
    }

    public function index(): JsonResponse
    {
        $member = $this->signedIn();

        return response()->json([
            'submissions' => $member->publications()->latest()->get()->map->toPortalArray(),
            'kinds' => MemberPublication::KINDS,
            'can_submit' => $member->canUseMemberArea(),
            'max_pending' => MemberPublication::MAX_PENDING,
        ])->header('Cache-Control', 'no-store');
    }

    public function store(Request $request): JsonResponse
    {
        $member = $this->signedIn();
        $data = $request->validate(Publications::rules(), Publications::messages());
        $publication = $this->publications->submit($member, $data, $request->file('image'));

        return response()->json(['submission' => $publication->toPortalArray(), 'message' => 'Thank you! Your update has been sent to the editorial team for review.'], 201);
    }

    /** POST, not PUT: browsers send files only with POST */
    public function update(Request $request, int $id): JsonResponse
    {
        $publication = $this->signedIn()->publications()->findOrFail($id);
        $data = $request->validate(Publications::rules(), Publications::messages());
        $publication = $this->publications->update($publication, $data, $request->file('image'));

        return response()->json(['submission' => $publication->toPortalArray(), 'message' => 'Your changes have been saved and sent for review.']);
    }

    public function destroy(int $id): JsonResponse
    {
        $publication = $this->signedIn()->publications()->findOrFail($id);
        abort_unless($publication->editableByMember(), 403, 'Published updates can only be removed by the editorial team.');
        $publication->delete();

        return response()->json(['message' => 'Your submission has been withdrawn.']);
    }

    private function signedIn(): MemberAccount
    {
        $member = Auth::guard('member')->user();
        abort_unless($member, 401, 'Please sign in.');

        return $member;
    }
}
