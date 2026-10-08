<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsForEditor;
use App\Http\Requests\Author\SubmitManuscriptRequest;
use App\Http\Requests\Author\UploadRevisionRequest;
use App\Http\Resources\AuthorManuscriptResource;
use App\Models\Manuscript;
use App\Services\AuthorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Controller tipis: validasi (FormRequest) -> panggil AuthorService -> kembalikan respons.
 * Semua aturan bisnis ada di AuthorService.
 */
class AuthorController extends Controller
{
    use RespondsForEditor; // pola respons tabel AJAX & DomainException -> 422 dipakai bersama

    private const FILTER_KEYS = ['search', 'status', 'per_page'];

    public function __construct(private readonly AuthorService $authorService)
    {
    }

    // ------------------------------------------------------------------ Dashboard

    public function dashboard(Request $request): View
    {
        return view('roles.author.dashboard', [
            'stats'  => $this->authorService->getDashboardStats($request->user()),
            'recent' => $this->authorService->getRecentManuscripts($request->user()),
        ]);
    }

    // ------------------------------------------------------------------ Naskah Baru

    public function create(): View
    {
        return view('roles.author.submit', ['fields' => $this->authorService->getResearchFields()]);
    }

    public function store(SubmitManuscriptRequest $request): RedirectResponse
    {
        $this->authorService->submit(
            $request->user(),
            $request->safe()->except('file'),
            $request->file('file')
        );

        return redirect()->route('author.manuscripts.index')
            ->with('success', 'Naskah berhasil diajukan dan masuk antrean editor.');
    }

    // ------------------------------------------------------------------ Naskah Saya

    public function index(Request $request): View|JsonResponse
    {
        return $this->tableResponse(
            $request,
            'roles.author.manuscripts',
            'roles.author.partials.manuscript-table-rows',
            $this->authorService->getMyManuscripts($request->user(), $request->only(self::FILTER_KEYS)),
            ['statuses' => Manuscript::STATUSES, 'initialStatus' => (string) $request->query('status', '')]
        );
    }

    public function show(Request $request, Manuscript $manuscript): JsonResponse
    {
        $this->authorizeOwner($request, $manuscript);
        $manuscript->load('researchField');

        return response()->json([
            'manuscript' => AuthorManuscriptResource::make($manuscript)->resolve(),
            'timeline'   => $this->authorService->buildTimeline($manuscript),
        ]);
    }

    public function download(Request $request, Manuscript $manuscript, string $type): StreamedResponse
    {
        $this->authorizeOwner($request, $manuscript);
        abort_unless(in_array($type, ['original', 'revision'], true), 404);

        $file = $this->authorService->resolveDownload($manuscript, $type);
        abort_if($file === null, 404, 'Berkas tidak ditemukan.');

        return $this->authorService->disk()->download($file['path'], $file['name']);
    }

    // ------------------------------------------------------------------ Hasil Review & Revisi

    public function revisions(Request $request): View|JsonResponse
    {
        return $this->tableResponse(
            $request,
            'roles.author.revisions',
            'roles.author.partials.revision-table-rows',
            $this->authorService->getRevisionQueue($request->user(), $request->only(self::FILTER_KEYS))
        );
    }

    public function revisionDetail(Request $request, Manuscript $manuscript): JsonResponse
    {
        $this->authorizeOwner($request, $manuscript);
        abort_unless($manuscript->status === 'revisi', 404);
        $manuscript->load('researchField');

        return response()->json([
            'manuscript' => AuthorManuscriptResource::make($manuscript)->resolve(),
            'reviews'    => $this->authorService->getReviewerFeedback($manuscript),
        ]);
    }

    public function uploadRevision(UploadRevisionRequest $request, Manuscript $manuscript): JsonResponse
    {
        return $this->perform(
            fn () => $this->authorService->submitRevision(
                $manuscript,
                $request->file('revision_file'),
                $request->validated('author_response')
            ),
            'Revisi berhasil dikirim dan menunggu keputusan editor.'
        );
    }

    // ------------------------------------------------------------------ helper

    /** Author hanya boleh mengakses naskahnya sendiri. */
    private function authorizeOwner(Request $request, Manuscript $manuscript): void
    {
        abort_unless($this->authorService->owns($request->user(), $manuscript), 403);
    }
}
