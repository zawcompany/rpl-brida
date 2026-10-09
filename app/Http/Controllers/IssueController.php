<?php

namespace App\Http\Controllers;

use App\Http\Requests\Editor\IssueManuscriptRequest;
use App\Http\Requests\Editor\IssueRequest;
use App\Models\Issue;
use App\Models\Manuscript;
use App\Services\IssueService;
use Closure;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Edisi & Publikasi. Form biasa (non-AJAX) karena ada upload file; hasil lewat flash message.
 */
class IssueController extends Controller
{
    public function __construct(private readonly IssueService $issueService)
    {
    }

    public function index(Request $request): View
    {
        $issues = $this->issueService->getIssues();

        // Edisi aktif: dari query ?issue=, default draft terbaru.
        $active = $issues->firstWhere('id', (int) $request->query('issue'))
            ?? $issues->firstWhere('status', Issue::DRAFT);

        $editing = $issues->firstWhere('id', (int) $request->query('edit'));

        return view('roles.editor.issues', [
            'issues'              => $issues,
            'activeIssue'         => $active,
            'editingIssue'        => $editing?->isDraft() ? $editing : null,
            'issueManuscripts'    => $active ? $this->issueService->getIssueManuscripts($active) : collect(),
            'eligibleManuscripts' => $active?->isDraft() ? $this->issueService->getEligibleManuscripts() : collect(),
        ]);
    }

    public function store(IssueRequest $request): RedirectResponse
    {
        $issue = $this->issueService->createIssue($request->safe()->except('cover_image'), $request->file('cover_image'));

        return redirect()->route('editor.issues.index', ['issue' => $issue->id])
            ->with('success', "Edisi {$issue->label} berhasil dibuat.");
    }

    public function update(IssueRequest $request, Issue $issue): RedirectResponse
    {
        return $this->act(
            fn () => $this->issueService->updateIssue($issue, $request->safe()->except('cover_image'), $request->file('cover_image')),
            'Edisi berhasil diperbarui.',
            $issue
        );
    }

    public function destroy(Issue $issue): RedirectResponse
    {
        return $this->act(
            fn () => $this->issueService->deleteIssue($issue),
            'Edisi berhasil dihapus.'
        );
    }

    public function attachManuscript(IssueManuscriptRequest $request, Issue $issue): RedirectResponse
    {
        $manuscript = Manuscript::findOrFail($request->validated('manuscript_id'));

        return $this->act(
            fn () => $this->issueService->attachManuscript($issue, $manuscript, $request->file('final_file')),
            'Naskah dan berkas final berhasil disimpan pada edisi.',
            $issue
        );
    }

    public function detachManuscript(Issue $issue, Manuscript $manuscript): RedirectResponse
    {
        return $this->act(
            fn () => $this->issueService->detachManuscript($issue, $manuscript),
            'Naskah dikeluarkan dari edisi.',
            $issue
        );
    }

    public function publish(Issue $issue): RedirectResponse
    {
        return $this->act(
            fn () => $this->issueService->publish($issue),
            'Edisi berhasil diterbitkan. Seluruh naskahnya kini berstatus Diterbitkan.',
            $issue
        );
    }

    /** Jalankan aksi; DomainException menjadi flash error, bukan halaman 500. */
    private function act(Closure $action, string $success, ?Issue $issue = null): RedirectResponse
    {
        $target = redirect()->route('editor.issues.index', $issue ? ['issue' => $issue->id] : []);

        try {
            $action();
        } catch (DomainException $e) {
            return $target->with('error', $e->getMessage());
        }

        return $target->with('success', $success);
    }
}
