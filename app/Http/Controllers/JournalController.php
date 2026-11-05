<?php

namespace App\Http\Controllers;

use App\Http\Requests\Journal\UploadTemplateRequest;
use App\Models\ActivityLog;
use App\Models\Issue;
use App\Models\Journal;
use App\Services\Files\JournalTemplateStore;
use App\Services\JournalService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Halaman jurnal untuk pengunjung dan penulis: daftar jurnal, gabung ke kelompok jurnal,
 * dan unduh template (hanya anggota jurnal itu).
 */
class JournalController extends Controller
{
    public function __construct(
        private readonly JournalService $journals,
        private readonly JournalTemplateStore $templates,
    ) {
    }

    public function index(Request $request): View
    {
        return view('journals.index', ['journals' => $this->journals->listActive($request->user())]);
    }

    public function show(Request $request, Journal $journal): View
    {
        abort_unless($journal->is_active || $request->user()?->isAdmin(), 404);

        $user = $request->user();

        return view('journals.show', [
            'journal'   => $journal,
            'isMember'  => (bool) $user?->isMemberOf($journal),
            'isAuthor'  => (bool) $user?->isMemberOf($journal, Journal::AUTHOR),
            'canJoin'   => $user?->role === Journal::AUTHOR,
            'canManage' => $user && ($user->isAdmin() || in_array($journal->id, $user->journalIds(Journal::EDITOR), true)),
            'issues'    => Issue::where('journal_id', $journal->id)->published()
                ->with('journal')
                ->orderByDesc('year')->orderByDesc('volume')->orderByDesc('number')->limit(12)->get(),
        ]);
    }

    public function join(Request $request, Journal $journal): RedirectResponse
    {
        try {
            $this->journals->join($request->user(), $journal);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('journals.show', $journal)
            ->with('success', "Anda bergabung di {$journal->name}. Template naskah kini dapat diunduh.");
    }

    public function leave(Request $request, Journal $journal): RedirectResponse
    {
        try {
            $this->journals->leave($request->user(), $journal);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('journals.show', $journal)->with('success', "Anda keluar dari {$journal->name}.");
    }

    /** Unduh template: hanya anggota jurnal (peran apa pun) atau Administrator. */
    public function template(Request $request, Journal $journal): Response|RedirectResponse
    {
        $user = $request->user();

        if (! $user->isAdmin() && ! $user->isMemberOf($journal)) {
            return redirect()->route('journals.show', $journal)
                ->with('error', 'Template hanya dapat diunduh anggota jurnal. Bergabung dulu ke kelompok jurnal ini.');
        }

        ActivityLog::record('template.downloaded', "Template {$journal->name} diunduh oleh {$user->name}", $user->id);

        return $this->templates->download($journal);
    }

    /** Admin atau editor jurnal mengganti template jurnal. */
    public function uploadTemplate(UploadTemplateRequest $request, Journal $journal): RedirectResponse
    {
        $this->templates->replace($journal, $request->file('template'));

        ActivityLog::record('template.uploaded', "Template {$journal->name} diperbarui (versi {$journal->template_version})", $request->user()->id);

        return back()->with('success', "Template {$journal->name} diperbarui (versi {$journal->template_version}).");
    }
}
