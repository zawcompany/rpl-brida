<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Journal;
use App\Models\Manuscript;
use App\Models\Review;
use App\Models\User;
use App\Services\Files\CoverImageStore;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

/**
 * JournalService — aturan keanggotaan jurnal.
 *  - Penulis bergabung sendiri (kelompok jurnal); setelah itu baru bisa mengunduh template dan mengirim naskah.
 *  - Editor ditunjuk admin platform; reviewer diundang editor jurnal atau admin.
 * Pelanggaran aturan dilempar sebagai DomainException.
 */
class JournalService
{
    // -------------------------------------------------------------------------
    // Daftar
    // -------------------------------------------------------------------------

    /**
     * Jurnal aktif beserta jumlah anggota penulis dan artikel terbit; $viewer menentukan
     * penanda "sudah bergabung" (`joined`) pada setiap jurnal.
     *
     * @return Collection<int, Journal>
     */
    public function listActive(?User $viewer = null): Collection
    {
        $joined = $viewer ? $viewer->journalIds(Journal::AUTHOR) : [];

        return Journal::active()
            ->withCount([
                'members as authors_count' => fn ($q) => $q->where('journal_user.role', Journal::AUTHOR),
                'manuscripts as articles_count' => fn ($q) => $q->published(),
            ])
            ->orderBy('name')
            ->get()
            ->each(fn (Journal $j) => $j->setAttribute('joined', in_array($j->id, $joined, true)));
    }

    // -------------------------------------------------------------------------
    // Penulis bergabung / keluar
    // -------------------------------------------------------------------------

    /** @throws DomainException */
    public function join(User $user, Journal $journal): void
    {
        if ($user->role !== Journal::AUTHOR) {
            throw new DomainException('Hanya akun Penulis yang dapat bergabung sendiri. Editor dan reviewer ditunjuk oleh admin atau editor jurnal.');
        }

        if (! $journal->is_active) {
            throw new DomainException('Jurnal ini sedang tidak menerima anggota baru.');
        }

        $user->joinJournal($journal, Journal::AUTHOR);

        ActivityLog::record('journal.joined', "{$user->name} bergabung ke {$journal->name}", $user->id);
    }

    /** @throws DomainException */
    public function leave(User $user, Journal $journal): void
    {
        if (Manuscript::where('journal_id', $journal->id)->where('author_id', $user->id)->exists()) {
            throw new DomainException('Anda sudah mengirim naskah ke jurnal ini, jadi tidak dapat keluar dari kelompoknya.');
        }

        $user->leaveJournal($journal, Journal::AUTHOR);

        ActivityLog::record('journal.left', "{$user->name} keluar dari {$journal->name}", $user->id);
    }

    // -------------------------------------------------------------------------
    // Kelola jurnal (admin)
    // -------------------------------------------------------------------------

    /** Cover jurnal disimpan di disk sampul (bukan disk privat naskah), pada folder tersendiri. */
    private const COVER_DIRECTORY = 'journals/covers';

    public function __construct(private readonly CoverImageStore $covers) {}

    public function create(array $data, ?UploadedFile $cover = null): Journal
    {
        $data = Arr::except($data, 'cover');

        if ($cover) {
            $data['cover_image'] = $this->covers->put($cover, self::COVER_DIRECTORY);
        }

        $journal = Journal::create($data + ['is_active' => true]);

        ActivityLog::record('journal.created', "Jurnal baru dibuat: {$journal->name}");

        return $journal;
    }

    /** Cover baru menggantikan yang lama; berkas lama dihapus setelah database berhasil diperbarui. */
    public function update(Journal $journal, array $data, ?UploadedFile $cover = null, bool $removeCover = false): Journal
    {
        $data = Arr::except($data, ['cover', 'remove_cover']);
        $old = $journal->cover_image;

        if ($cover) {
            $data['cover_image'] = $this->covers->put($cover, self::COVER_DIRECTORY);
        } elseif ($removeCover) {
            $data['cover_image'] = null;
        }

        try {
            $journal->update($data);
        } catch (\Throwable $e) {
            if ($cover) {
                $this->covers->delete($data['cover_image']); // jangan tinggalkan berkas yatim
            }
            throw $e;
        }

        if (($cover || $removeCover) && $old) {
            $this->covers->delete($old);
        }

        return $journal;
    }

    // -------------------------------------------------------------------------
    // Anggota (admin / editor jurnal)
    // -------------------------------------------------------------------------

    /**
     * Tambah anggota. Peran di jurnal harus sama dengan jenis akunnya (akun Editor menjadi Editor,
     * akun Reviewer menjadi Reviewer, akun Penulis menjadi Penulis).
     *
     * @throws DomainException
     */
    public function addMember(Journal $journal, User $user, string $role): void
    {
        if (! in_array($role, Journal::ROLES, true) || $user->role !== $role) {
            throw new DomainException("Akun {$user->name} berjenis {$user->role}, jadi hanya bisa menjadi {$user->role} di jurnal.");
        }

        $user->joinJournal($journal, $role);

        ActivityLog::record('journal.member_added', "{$user->name} ditambahkan sebagai {$role} di {$journal->name}");
    }

    /** @throws DomainException bila anggota masih punya pekerjaan aktif di jurnal ini */
    public function removeMember(Journal $journal, User $user, string $role): void
    {
        if ($role === Journal::REVIEWER && $this->hasActiveReviews($journal, $user)) {
            throw new DomainException('Reviewer ini masih memiliki review yang berjalan di jurnal ini.');
        }

        if ($role === Journal::AUTHOR && Manuscript::where('journal_id', $journal->id)->where('author_id', $user->id)->exists()) {
            throw new DomainException('Penulis ini sudah mengirim naskah ke jurnal ini.');
        }

        $user->leaveJournal($journal, $role);

        ActivityLog::record('journal.member_removed', "{$user->name} dikeluarkan dari {$journal->name} ({$role})");
    }

    private function hasActiveReviews(Journal $journal, User $user): bool
    {
        return Review::where('reviewer_id', $user->id)
            ->active()
            ->whereHas('manuscript', fn ($q) => $q->where('journal_id', $journal->id))
            ->exists();
    }

    /**
     * Pengguna dengan jenis akun $role yang belum menjadi anggota $journal (untuk dropdown tambah anggota).
     *
     * @return Collection<int, User>
     */
    public function candidates(Journal $journal, string $role): Collection
    {
        return User::where('role', $role)
            ->where('is_active', true)
            ->whereDoesntHave('journals', fn ($q) => $q->whereKey($journal->id)->where('journal_user.role', $role))
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
    }

    // -------------------------------------------------------------------------
    // Informasi publik jurnal
    // -------------------------------------------------------------------------

    /**
     * Dewan editor yang tampil di laman jurnal (Permendiktisaintek 9/2026 Pasal 6 ayat (2) huruf b dan e).
     *
     * @return Collection<int, User>
     */
    public function editorialBoard(Journal $journal): Collection
    {
        return $journal->membersAs(Journal::EDITOR)
            ->where('users.is_active', true)
            ->orderBy('users.name')
            ->get(['users.id', 'users.name', 'users.institution']);
    }

    /**
     * Ringkasan kinerja jurnal yang diumumkan berkala (UU 14/2008 Pasal 9 ayat (2) huruf b).
     *
     * @return array{submitted: int, accepted: int, rejected: int, published: int, acceptance_rate: ?int, average_decision_days: ?int}
     */
    public function publicStatistics(Journal $journal): array
    {
        $manuscripts = Manuscript::where('journal_id', $journal->id)->get(['status', 'submitted_at', 'decided_at']);

        $accepted = $manuscripts->whereIn('status', ['disetujui', 'diterbitkan'])->count();
        $rejected = $manuscripts->where('status', 'ditolak')->count();
        $decided = $manuscripts->filter(fn (Manuscript $m) => $m->submitted_at && $m->decided_at);

        return [
            'submitted' => $manuscripts->count(),
            'accepted' => $accepted,
            'rejected' => $rejected,
            'published' => $manuscripts->where('status', 'diterbitkan')->count(),
            'acceptance_rate' => ($accepted + $rejected) > 0 ? (int) round($accepted / ($accepted + $rejected) * 100) : null,
            'average_decision_days' => $decided->isNotEmpty()
                ? (int) round($decided->avg(fn (Manuscript $m) => $m->submitted_at->diffInDays($m->decided_at)))
                : null,
        ];
    }

    /**
     * Kirim pengingat akreditasi ulang untuk jurnal yang masa berlakunya tinggal ≤ 6 bulan
     * (Permendiktisaintek 9/2026 Pasal 13). Satu jurnal diingatkan paling sering sekali sebulan.
     *
     * @return int jumlah jurnal yang diingatkan
     */
    public function remindExpiringAccreditations(NotificationService $notifier): int
    {
        $journals = Journal::whereNotNull('accreditation_rank')
            ->whereDate('accreditation_valid_until', '>=', now()->toDateString())
            ->whereDate('accreditation_valid_until', '<=', now()->addMonths(Journal::REACCREDITATION_NOTICE_MONTHS)->toDateString())
            ->where(fn ($q) => $q->whereNull('accreditation_reminded_at')->orWhere('accreditation_reminded_at', '<', now()->subDays(30)))
            ->get();

        foreach ($journals as $journal) {
            $notifier->accreditationExpiring($journal);
            $journal->update(['accreditation_reminded_at' => now()]);
            ActivityLog::record('journal.accreditation_reminder', "Pengingat akreditasi ulang dikirim untuk {$journal->name}", null);
        }

        return $journals->count();
    }
}
