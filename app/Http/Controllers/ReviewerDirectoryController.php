<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsForEditor;
use App\Models\User;
use App\Services\EditorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReviewerDirectoryController extends Controller
{
    use RespondsForEditor;

    public function __construct(private readonly EditorService $editorService)
    {
    }

    public function index(Request $request): View|JsonResponse
    {
        return $this->tableResponse(
            $request,
            'roles.editor.reviewers',
            'roles.editor.partials.reviewer-table-rows',
            $this->editorService->getReviewerDirectory($request->only(['search', 'field', 'per_page'])),
            ['fields' => $this->editorService->getResearchFields()]
        );
    }

    /** AJAX: profil + riwayat penugasan reviewer (untuk modal). */
    public function profile(User $user): JsonResponse
    {
        abort_unless($user->role === 'Reviewer', 404);

        $profile = $this->editorService->getReviewerProfile($user);

        return response()->json([
            'reviewer' => [
                'name'   => $user->name,
                'email'  => $user->email,
                'fields' => $user->researchFields->pluck('name'),
            ],
            'active'    => $profile['active'],
            'completed' => $profile['completed'],
            'is_busy'   => $profile['is_busy'],
            'history'   => $profile['history']->map(fn ($r) => [
                'title'                => $r->manuscript?->title,
                'status_label'         => $r->status_label,
                'status_badge_class'   => $r->status_badge_class,
                'recommendation_label' => $r->recommendation_label,
                'assigned'             => $r->created_at->format('d M Y'),
                'superseded'           => $r->superseded_at !== null,
            ]),
        ]);
    }
}
