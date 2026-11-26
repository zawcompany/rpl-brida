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
    public function __construct(private readonly JournalService $journals) {}

    // ------------------------------------------------------------------ Administrator

    public function adminIndex(): View
    {
        return view('roles.admin.journals', [
            'journals' => Journal::withCount([
                'members as editors_count' => fn ($q) => $q->where('journal_user.role', Journal::EDITOR),
                'members as reviewers_count' => fn ($q) => $q->where('journal_user.role', Journal::REVIEWER),
                'members as authors_count' => fn ($q) => $q->where('journal_user.role', Journal::AUTHOR),
                'manuscripts',
            ])->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:journals,name'],
            'abbreviation' => ['nullable', 'string', 'max:30'],
            'field' => ['nullable', 'string', 'max:120'],
            'e_issn' => ['nullable', 'string', 'regex:'.Journal::ISSN_PATTERN],
            'license' => ['nullable', Rule::in(array_keys(Journal::LICENSES))],
            'description' => ['nullable', 'string', 'max:2000'],
            'cover' => $this->coverRules(),
        ], $this->coverMessages() + ['e_issn.regex' => 'Format e-ISSN harus 0000-0000 (angka terakhir boleh X).']);

        $data = $this->withoutBlankLicense($data);

        $journal = $this->journals->create($data, $request->file('cover'));

        return redirect()->route('admin.journals.show', $journal)->with('success', "Jurnal {$journal->name} dibuat. Tunjuk editor dan unggah templatenya.");
    }

    public function adminShow(Journal $journal): View
    {
        return view('roles.admin.journal-show', $this->detail($journal, [Journal::EDITOR, Journal::REVIEWER]));
    }

    public function update(Request $request, Journal $journal): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('journals', 'name')->ignore($journal->id)],
            'abbreviation' => ['nullable', 'string', 'max:30'],
            'field' => ['nullable', 'string', 'max:120'],
            'e_issn' => ['nullable', 'string', 'regex:'.Journal::ISSN_PATTERN],
            'license' => ['nullable', Rule::in(array_keys(Journal::LICENSES))],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['nullable', 'boolean'],
            'cover' => $this->coverRules(),
            'focus_scope' => ['nullable', 'string', 'max:5000'],
            'author_guidelines' => ['nullable', 'string', 'max:10000'],
            'accreditation_rank' => ['nullable', 'integer', Rule::in(Journal::ACCREDITATION_RANKS)],
            'accreditation_decree' => ['nullable', 'string', 'max:120'],
            'accreditation_valid_until' => ['nullable', 'date', 'required_with:accreditation_rank'],
        ], $this->coverMessages() + [
            'e_issn.regex' => 'Format e-ISSN harus 0000-0000 (angka terakhir boleh X).',
            'accreditation_rank.in' => 'Peringkat akreditasi hanya 1 sampai 4.',
            'accreditation_valid_until.required_with' => 'Isi tanggal berakhirnya masa berlaku akreditasi.',
        ]);
        $data = $this->withAccreditation($this->withoutBlankLicense($data), $journal);
        $data['is_active'] = $request->boolean('is_active');

        $this->journals->update($journal, $data, $request->file('cover'), $request->boolean('remove_cover'));

        return back()->with('success', 'Data jurnal diperbarui.');
    }

    /** Hanya admin (rute admin): editor tidak lagi menambah/mengeluarkan anggota jurnal. */
    public function addMember(Request $request, Journal $journal): RedirectResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'role' => ['required', Rule::in(Journal::ROLES)],
        ]);

        return $this->act(fn () => $this->journals->addMember($journal, User::findOrFail($data['user_id']), $data['role']), 'Anggota ditambahkan.');
    }

    public function removeMember(Journal $journal, User $user, string $role): RedirectResponse
    {
        abort_unless(in_array($role, Journal::ROLES, true), 404);

        return $this->act(fn () => $this->journals->removeMember($journal, $user, $role), 'Anggota dikeluarkan.');
    }

    // ------------------------------------------------------------------ Editor

    /** Halaman "Jurnal Saya" untuk editor: template dan reviewer per jurnal yang ia kelola. */
    public function editorIndex(Request $request): View
    {
        $journals = Journal::whereIn('id', $request->user()->journalIds(Journal::EDITOR))->orderBy('name')->get();

        // ?jurnal= yang bukan jurnal editor ini (atau tidak ada) dijawab 404, bukan diam-diam membuka jurnal lain.
        $journal = $request->filled('jurnal')
            ? $journals->firstWhere('id', (int) $request->query('jurnal')) ?? abort(404)
            : $journals->first();

        return view('roles.editor.journals', ['journals' => $journals] + ($journal ? $this->detail($journal, [Journal::REVIEWER]) : ['journal' => null]));
    }

    // ------------------------------------------------------------------ util

    /**
     * Tanpa peringkat berarti tidak terakreditasi: SK dan masa berlaku ikut dikosongkan.
     * Masa berlaku yang berubah membuka kembali pengingat akreditasi ulang.
     */
    private function withAccreditation(array $data, Journal $journal): array
    {
        if (blank($data['accreditation_rank'] ?? null)) {
            $data['accreditation_rank'] = null;
            $data['accreditation_decree'] = null;
            $data['accreditation_valid_until'] = null;
        }

        if (($data['accreditation_valid_until'] ?? null) !== $journal->accreditation_valid_until?->toDateString()) {
            $data['accreditation_reminded_at'] = null;
        }

        return $data;
    }

    /** Lisensi kosong berarti 'tidak diubah' (update) atau memakai bawaan database (buat baru). */
    private function withoutBlankLicense(array $data): array
    {
        if (blank($data['license'] ?? null)) {
            unset($data['license']);
        }

        return $data;
    }

    /** Aturan unggah cover: gambar sungguhan (bukan sekadar ekstensi), batas ukuran dan resolusi sama dengan sampul edisi. */
    private function coverRules(): array
    {
        return ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:'.config('simpil.upload.cover_kb'), 'dimensions:min_width=50,min_height=50,max_width=6000,max_height=6000'];
    }

    private function coverMessages(): array
    {
        return [
            'cover.image' => 'Cover harus berupa gambar.',
            'cover.mimes' => 'Cover harus berformat JPG, PNG, atau WebP.',
            'cover.max' => 'Ukuran cover maksimal 2 MB.',
            'cover.dimensions' => 'Berkas bukan gambar yang valid, atau resolusinya di luar batas (50 sampai 6000 piksel).',
            'cover.uploaded' => 'Cover gagal diunggah. Pastikan ukurannya tidak melebihi 2 MB.',
        ];
    }

    /** Data halaman detail: anggota per peran + kandidat untuk dropdown tambah anggota. */
    private function detail(Journal $journal, array $roles): array
    {
        $members = $journal->members()->orderBy('name')->get()->groupBy(fn (User $u) => $u->pivot->role);

        return [
            'journal' => $journal,
            'members' => $members,
            'roles' => $roles,
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
