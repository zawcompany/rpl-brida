<?php

namespace App\Http\Controllers;

use App\Http\Requests\Editor\AssignReviewerRequest;
use App\Models\Manuscript;
use App\Models\ResearchField;
use App\Services\EditorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * EditorController — hanya bertanggung jawab pada HTTP layer (SRP).
 * Semua logika bisnis didelegasikan ke EditorService.
 */
class EditorController extends Controller
{
    public function __construct(private readonly EditorService $editorService)
    {
    }

    // -------------------------------------------------------------------------
    // Dashboard
    // -------------------------------------------------------------------------

    public function dashboard(): View
    {
        $stats          = $this->editorService->getDashboardStats();
        $recentActivity = $this->editorService->getRecentActivity();

        return view('roles.editor.dashboard', compact('stats', 'recentActivity'));
    }

    // -------------------------------------------------------------------------
    // Naskah Baru
    // -------------------------------------------------------------------------

    public function newManuscripts(Request $request): View|JsonResponse
    {
        $filters     = $request->only(['search', 'date', 'per_page']);
        $manuscripts = $this->editorService->getNewManuscripts($filters);

        // Kalau AJAX (dari live-search/pagination JS), kembalikan partial view
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'html'  => view('roles.editor.partials.manuscript-table-rows', compact('manuscripts'))->render(),
                'links' => $manuscripts->links('pagination::tailwind')->render(),
                'total' => $manuscripts->total(),
            ]);
        }

        return view('roles.editor.new-manuscripts', compact('manuscripts', 'filters'));
    }

    // -------------------------------------------------------------------------
    // AJAX: Detail Naskah + Rekomendasi Reviewer (untuk Modal)
    // -------------------------------------------------------------------------

    public function manuscriptDetail(Manuscript $manuscript): JsonResponse
    {
        $manuscript->load(['author', 'researchField']);
        $recommendations = $this->editorService->getReviewerRecommendations($manuscript);
        $allReviewers    = \App\Models\User::where('role', 'Reviewer')
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json([
            'manuscript'      => $manuscript,
            'recommendations' => $recommendations->map(fn ($r) => [
                'id'          => $r['reviewer']->id,
                'name'        => $r['reviewer']->name,
                'active_load' => $r['active_load'],
                'matched'     => $r['matched'],
                'score'       => $r['score'],
            ]),
            'all_reviewers'   => $allReviewers,
            'file_url'        => $manuscript->file_path
                ? asset('storage/' . $manuscript->file_path)
                : null,
        ]);
    }

    // -------------------------------------------------------------------------
    // POST: Proses Keputusan Administrasi Awal
    // -------------------------------------------------------------------------

    public function assignReviewer(AssignReviewerRequest $request, Manuscript $manuscript): JsonResponse
    {
        // Pastikan naskah masih dalam status "baru" agar tidak duplikat penugasan
        if (! in_array($manuscript->status, Manuscript::NEW_STATUSES)) {
            return response()->json([
                'success' => false,
                'message' => 'Naskah ini sudah diproses sebelumnya.',
            ], 422);
        }

        $updated = $this->editorService->processDecision(
            manuscript:  $manuscript,
            decision:    $request->validated('decision'),
            reviewerId:  $request->validated('reviewer_id'),
            editorNote:  $request->validated('editor_note'),
            editorId:    auth()->id(),
        );

        $actionLabel = $updated->status === 'ditolak' ? 'ditolak' : 'diteruskan ke reviewer';

        return response()->json([
            'success' => true,
            'message' => "Naskah berhasil {$actionLabel}.",
            'status'  => $updated->status,
        ]);
    }
}
