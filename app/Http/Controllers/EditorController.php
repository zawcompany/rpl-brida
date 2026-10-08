<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsForEditor;
use App\Http\Requests\Editor\AssignReviewerRequest;
use App\Http\Requests\Editor\ChangeReviewerRequest;
use App\Http\Requests\Editor\EditorialDecisionRequest;
use App\Http\Requests\Editor\ReReviewRequest;
use App\Http\Resources\ManuscriptSummaryResource;
use App\Http\Resources\ReviewResource;
use App\Models\Manuscript;
use App\Services\EditorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * EditorController — hanya HTTP layer (SRP). Logika bisnis ada di EditorService.
 */
class EditorController extends Controller
{
    use RespondsForEditor;

    private const FILTER_KEYS = ['search', 'date', 'per_page'];

    public function __construct(private readonly EditorService $editorService)
    {
    }

    // -------------------------------------------------------------------------
    // Dashboard
    // -------------------------------------------------------------------------

    public function dashboard(): View
    {
        return view('roles.editor.dashboard', [
            'stats'          => $this->editorService->getDashboardStats(),
            'recentActivity' => $this->editorService->getRecentActivity(),
        ]);
    }

    // -------------------------------------------------------------------------
    // Naskah Baru
    // -------------------------------------------------------------------------

    public function newManuscripts(Request $request): View|JsonResponse
    {
        return $this->tableResponse(
            $request,
            'roles.editor.new-manuscripts',
            'roles.editor.partials.manuscript-table-rows',
            $this->editorService->getNewManuscripts($request->only(self::FILTER_KEYS))
        );
    }

    /** AJAX: detail naskah + 1 rekomendasi reviewer terbaik (untuk modal). */
    public function manuscriptDetail(Manuscript $manuscript): JsonResponse
    {
        abort_unless(in_array($manuscript->status, Manuscript::NEW_STATUSES, true), 404);

        $manuscript->load(['author', 'researchField']);

        return response()->json([
            'manuscript'     => ManuscriptSummaryResource::make($manuscript)->resolve(),
            'recommendation' => $this->editorService->getBestReviewer($manuscript),
            'reviewers'      => $this->editorService->getAssignableReviewers(),
            'default_due_at' => $this->editorService->defaultDueDate(),
        ]);
    }

    public function assignReviewer(AssignReviewerRequest $request, Manuscript $manuscript): JsonResponse
    {
        $decision = $request->validated('decision');

        return $this->perform(
            fn () => $this->editorService->processDecision(
                manuscript: $manuscript,
                decision:   $decision,
                reviewerId: $request->validated('reviewer_id'),
                editorNote: $request->validated('editor_note'),
                editorId:   $request->user()->id,
                dueAt:      $request->validated('due_at'),
            ),
            $decision === 'ditolak' ? 'Naskah berhasil ditolak.' : 'Naskah berhasil diteruskan ke reviewer.'
        );
    }

    // -------------------------------------------------------------------------
    // Peninjauan Naskah
    // -------------------------------------------------------------------------

    public function underReview(Request $request): View|JsonResponse
    {
        return $this->tableResponse(
            $request,
            'roles.editor.under-review',
            'roles.editor.partials.review-table-rows',
            $this->editorService->getUnderReview($request->only(self::FILTER_KEYS))
        );
    }

    public function reviewDetail(Manuscript $manuscript): JsonResponse
    {
        abort_unless(in_array($manuscript->status, Manuscript::REVIEW_STATUSES, true), 404);

        $manuscript->load(['author', 'researchField', 'currentReview.reviewer']);
        $review = $manuscript->currentReview;

        return response()->json([
            'manuscript'     => ManuscriptSummaryResource::make($manuscript)->resolve(),
            'review'         => $review ? ReviewResource::make($review)->resolve() : null,
            'recommendation' => $review?->isReplaceable()
                ? $this->editorService->getBestReviewer($manuscript, $review->reviewer_id)
                : null,
            'reviewers'      => $this->editorService->getAssignableReviewers($review?->reviewer_id),
            'default_due_at' => $this->editorService->defaultDueDate(),
        ]);
    }

    public function sendReminder(Manuscript $manuscript): JsonResponse
    {
        return $this->perform(
            fn () => $this->editorService->sendReminder($manuscript),
            'Pengingat berhasil dikirim ke reviewer.'
        );
    }

    public function changeReviewer(ChangeReviewerRequest $request, Manuscript $manuscript): JsonResponse
    {
        return $this->perform(
            fn () => $this->editorService->replaceReviewer(
                $manuscript,
                (int) $request->validated('reviewer_id'),
                $request->validated('editor_note'),
                $request->user()->id,
                $request->validated('due_at'),
            ),
            'Reviewer berhasil diganti.'
        );
    }

    // -------------------------------------------------------------------------
    // Keputusan Editorial
    // -------------------------------------------------------------------------

    public function decisions(Request $request): View|JsonResponse
    {
        return $this->tableResponse(
            $request,
            'roles.editor.decisions',
            'roles.editor.partials.decision-table-rows',
            $this->editorService->getAwaitingDecision($request->only(self::FILTER_KEYS))
        );
    }

    public function decisionDetail(Manuscript $manuscript): JsonResponse
    {
        abort_unless($manuscript->status === 'menunggu_keputusan', 404);

        $manuscript->load(['author', 'researchField']);

        $reviews = $manuscript->reviews()
            ->whereNull('superseded_at')
            ->where('status', 'selesai')
            ->with('reviewer')
            ->get();

        return response()->json([
            'manuscript'     => ManuscriptSummaryResource::make($manuscript)->resolve(),
            'reviews'        => ReviewResource::collection($reviews)->resolve(),
            'recommendation' => $manuscript->isRevision() ? $this->editorService->getBestReviewer($manuscript) : null,
            'reviewers'      => $manuscript->isRevision() ? $this->editorService->getAssignableReviewers() : [],
            'default_due_at' => $this->editorService->defaultDueDate(),
        ]);
    }

    /** Revisi mayor: kirim naskah revisi kembali ke reviewer. */
    public function requestReReview(ReReviewRequest $request, Manuscript $manuscript): JsonResponse
    {
        return $this->perform(
            fn () => $this->editorService->requestReReview(
                $manuscript,
                (int) $request->validated('reviewer_id'),
                $request->validated('editor_note'),
                $request->user()->id,
                $request->validated('due_at'),
            ),
            'Naskah dikirim untuk review ulang.'
        );
    }

    public function storeDecision(EditorialDecisionRequest $request, Manuscript $manuscript): JsonResponse
    {
        $decision = $request->validated('decision');

        return $this->perform(
            fn () => $this->editorService->recordEditorialDecision(
                $manuscript,
                $decision,
                $request->validated('editorial_note'),
            ),
            'Keputusan editorial berhasil disimpan (' . Manuscript::STATUSES[Manuscript::DECISION_STATUS[$decision]] . ').'
        );
    }
}
