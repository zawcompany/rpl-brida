<?php

namespace App\Http\Controllers;

use App\Models\Journal;
use App\Models\User;
use App\Services\JournalService;
use Closure;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Pengelolaan jurnal dan anggotanya.
 *  - Administrator : membuat/mengubah jurnal, menunjuk editor, mengelola semua anggota.
 *  - Editor        : mengelola template dan reviewer pada jurnal tempat ia menjadi editor.
 */
class JournalManagementController extends Controller
{
    public function __construct(private readonly JournalService $journals)
    {
    }

    // ------------------------------------------------------------------ Administrator

    public function adminIndex(): View
    {
        return view('roles.admin.journals', [
            'journals' => Journal::withCount([
                'members as editors_count'    => fn ($q) => $q->where('journal_user.role', Journal::EDITOR),
                'members as reviewers_count'  => fn ($q) => $q->where('journal_user.role', Journal::REVIEWER),
                'members as authors_count'    => fn ($q) => $q->where('journal_user.role', Journal::AUTHOR),
                'manuscripts',
            ])->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'         => ['required', 'string', 'max:255', 'unique:journals,name'],
            'abbreviation' => ['nullable', 'string', 'max:30'],
            'description'  => ['nullable', 'string', 'max:2000'],
        ]);

        $journal = $this->journals->create($data);

        return redirect()->route('admin.journals.show', $journal)->with('success', "Jurnal {$journal->name} dibuat. Tunjuk editor dan unggah templatenya.");
    }

    public function adminShow(Journal $journal): View
    {
        return view('roles.admin.journal-show', $this->detail($journal, [Journal::EDITOR, Journal::REVIEWER]));
    }

    public function update(Request $request, Journal $journal): RedirectResponse
    {
        $data = $request->validate([
            'name'         => ['required', 'string', 'max:255', Rule::unique('journals', 'name')->ignore($journal->id)],
            'abbreviation' => ['nullable', 'string', 'max:30'],
            'description'  => ['nullable', 'string', 'max:2000'],
            'is_active'    => ['nullable', 'boolean'],
        ]);
        $data['is_active'] = $request->boolean('is_active');

        $this->journals->update($journal, $data);

        return back()->with('success', 'Data jurnal diperbarui.');
    }

    public function addMember(Request $request, Journal $journal): RedirectResponse
    {
        $this->authorizeJournal($request, $journal);

        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'role'    => ['required', Rule::in($this->manageableRoles($request))],
        ]);

        return $this->act(fn () => $this->journals->addMember($journal, User::findOrFail($data['user_id']), $data['role']), 'Anggota ditambahkan.');
    }

    public function removeMember(Request $request, Journal $journal, User $user, string $role): RedirectResponse
    {
        $this->authorizeJournal($request, $journal);
        abort_unless(in_array($role, $this->manageableRoles($request), true), 403);

        return $this->act(fn () => $this->journals->removeMember($journal, $user, $role), 'Anggota dikeluarkan.');
    }

    // ------------------------------------------------------------------ Editor

    /** Halaman "Jurnal Saya" untuk editor: template dan reviewer per jurnal yang ia kelola. */
    public function editorIndex(Request $request): View
    {
        $journals = Journal::whereIn('id', $request->user()->journalIds(Journal::EDITOR))->orderBy('name')->get();

        $journal = $journals->firstWhere('id', (int) $request->query('jurnal')) ?? $journals->first();

        return view('roles.editor.journals', ['journals' => $journals] + ($journal ? $this->detail($journal, [Journal::REVIEWER]) : ['journal' => null]));
    }

    // ------------------------------------------------------------------ util

    /** Admin mengelola semua jurnal; editor hanya jurnalnya sendiri. */
    private function authorizeJournal(Request $request, Journal $journal): void
    {
        $user = $request->user();

        abort_unless($user->isAdmin() || in_array($journal->id, $user->journalIds(Journal::EDITOR), true), 404);
    }

    /** Peran yang boleh dikelola pengguna saat ini: admin semua, editor hanya reviewer. */
    private function manageableRoles(Request $request): array
    {
        return $request->user()->isAdmin() ? Journal::ROLES : [Journal::REVIEWER];
    }

    /** Data halaman detail: anggota per peran + kandidat untuk dropdown tambah anggota. */
    private function detail(Journal $journal, array $roles): array
    {
        $members = $journal->members()->orderBy('name')->get()->groupBy(fn (User $u) => $u->pivot->role);

        return [
            'journal'    => $journal,
            'members'    => $members,
            'roles'      => $roles,
            'candidates' => collect($roles)->mapWithKeys(fn (string $r) => [$r => $this->journals->candidates($journal, $r)]),
        ];
    }

    private function act(Closure $action, string $success): RedirectResponse
    {
        try {
            $action();
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', $success);
    }
}
