<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsForEditor;
use App\Http\Requests\Reviewer\DeclineReviewRequest;
use App\Http\Requests\Reviewer\SubmitReviewRequest;
use App\Http\Requests\Reviewer\UpdateReviewRequest;
use App\Http\Resources\ReviewerReviewResource;
use App\Models\Review;
use App\Services\ReviewerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Controller tipis modul Reviewer: validasi (FormRequest) -> ReviewerService -> respons.
 * Pola tabel AJAX & penanganan DomainException sama dengan modul Editor/Author.
 */
class ReviewerController extends Controller
{
    use RespondsForEditor;

    private const FILTER_KEYS = ['search', 'status', 'per_page'];

    public function __construct(private readonly ReviewerService $reviewerService)
    {
    }

    // ------------------------------------------------------------------ Dashboard

    public function dashboard(Request $request): View
    {
        return view('roles.reviewer.dashboard', [
            'stats'  => $this->reviewerService->getDashboardStats($request->user()),
            'recent' => $this->reviewerService->getRecentReviews($request->user()),
        ]);
    }

    // ------------------------------------------------------------------ Daftar

    /** Naskah Ditugaskan: semua penugasan (filter status). */
    public function index(Request $request): View|JsonResponse
    {
        return $this->tableResponse(
            $request,
            'roles.reviewer.manuscripts',
            'roles.reviewer.partials.assignment-table-rows',
            $this->reviewerService->paginateAssignments($request->user(), $request->only(self::FILTER_KEYS)),
            ['statuses' => Review::STATUS_LABELS]
        );
    }

    /** Naskah Selesai Direview: hanya penugasan berstatus 'selesai'. */
    public function completed(Request $request): View|JsonResponse
    {
        return $this->tableResponse(
            $request,
            'roles.reviewer.manuscripts-selesai',
            'roles.reviewer.partials.assignment-table-rows',
            $this->reviewerService->paginateAssignments($request->user(), $request->only(self::FILTER_KEYS), ['selesai'])
        );
    }

    // ------------------------------------------------------------------ Detail & aksi

    public function detail(Review $review): JsonResponse
    {
        Gate::authorize('view', $review);
        $review->load(['manuscript.researchField']);

        return response()->json(['review' => ReviewerReviewResource::make($review)->resolve()]);
    }

    public function accept(Request $request, Review $review): JsonResponse
    {
        Gate::authorize('respond', $review);

        return $this->perform(fn () => $this->reviewerService->accept($review), 'Penugasan diterima. Silakan lakukan penilaian.');
    }

    public function decline(DeclineReviewRequest $request, Review $review): JsonResponse
    {
        return $this->perform(
            fn () => $this->reviewerService->decline($review, $request->validated('reason')),
            'Penugasan ditolak. Editor akan menugaskan reviewer lain.'
        );
    }

    public function submit(SubmitReviewRequest $request, Review $review): JsonResponse
    {
        return $this->perform(
            fn () => $this->reviewerService->submit($review, $request->validated(), $request->file('review_file')),
            'Review berhasil dikirim ke editor.'
        );
    }

    public function update(UpdateReviewRequest $request, Review $review): JsonResponse
    {
        return $this->perform(
            fn () => $this->reviewerService->update($review, $request->validated(), $request->file('review_file')),
            'Review berhasil diperbarui.'
        );
    }
}
