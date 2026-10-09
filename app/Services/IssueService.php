<?php

namespace App\Services;

use App\Models\Issue;
use App\Models\Manuscript;
use App\Services\Files\CoverImageStore;
use App\Services\Files\ManuscriptFileStore;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * IssueService — kelola edisi jurnal, penetapan naskah, dan publikasi.
 * Pelanggaran aturan alur dilempar sebagai DomainException.
 */
class IssueService
{
    public function __construct(
        private readonly ManuscriptFileStore $files,
        private readonly CoverImageStore $covers,
        private readonly NotificationService $notifier,
    ) {
    }

    // -------------------------------------------------------------------------
    // Edisi
    // -------------------------------------------------------------------------

    /** Semua edisi, terbaru di atas, beserta jumlah naskahnya. */
    public function getIssues(): Collection
    {
        return Issue::withCount('manuscripts')
            ->orderByDesc('year')->orderByDesc('volume')->orderByDesc('number')
            ->get();
    }

    public function createIssue(array $data, ?UploadedFile $cover = null): Issue
    {
        $data = Arr::except($data, 'cover_image');

        if ($cover) {
            $data['cover_image'] = $this->covers->put($cover);
        }

        return Issue::create($data + ['status' => Issue::DRAFT]);
    }

    /**
     * Sampul baru menggantikan yang lama; berkas lama dihapus setelah database berhasil diperbarui.
     *
     * @throws DomainException
     */
    public function updateIssue(Issue $issue, array $data, ?UploadedFile $cover = null): Issue
    {
        $this->ensureDraft($issue);

        $data = Arr::except($data, 'cover_image');
        $oldCover = $issue->cover_image;

        if ($cover) {
            $data['cover_image'] = $this->covers->put($cover);
        }

        try {
            $issue->update($data);
        } catch (\Throwable $e) {
            if ($cover) {
                $this->covers->delete($data['cover_image']); // jangan tinggalkan berkas yatim
            }
            throw $e;
        }

        if ($cover) {
            $this->covers->delete($oldCover);
        }

        return $issue;
    }

    /** @throws DomainException */
    public function deleteIssue(Issue $issue): void
    {
        $this->ensureDraft($issue);

        if ($issue->manuscripts()->exists()) {
            throw new DomainException('Edisi yang sudah memiliki naskah tidak dapat dihapus. Keluarkan naskahnya terlebih dahulu.');
        }

        $issue->delete();
        $this->covers->delete($issue->cover_image);
    }

    // -------------------------------------------------------------------------
    // Penetapan naskah + file final
    // -------------------------------------------------------------------------

    /** Naskah berstatus 'disetujui' yang belum masuk edisi mana pun. */
    public function getEligibleManuscripts(): Collection
    {
        return Manuscript::with('author:id,name')
            ->where('status', 'disetujui')
            ->whereNull('issue_id')
            ->orderBy('title')
            ->get();
    }

    public function getIssueManuscripts(Issue $issue): Collection
    {
        return $issue->manuscripts()->with(['author:id,name'])->orderBy('title')->get();
    }

    /**
     * Tambahkan naskah ke edisi + simpan PDF final.
     * Jika naskah sudah ada di edisi ini, file final-nya diganti.
     *
     * @throws DomainException
     */
    public function attachManuscript(Issue $issue, Manuscript $manuscript, UploadedFile $finalFile): Manuscript
    {
        $this->ensureDraft($issue);

        $belongsHere = $manuscript->issue_id === $issue->id;

        if ($manuscript->status !== 'disetujui' || ($manuscript->issue_id !== null && ! $belongsHere)) {
            throw new DomainException('Hanya naskah berstatus Disetujui yang belum masuk edisi lain yang dapat ditambahkan.');
        }

        $oldPath = $manuscript->final_file_path;
        $path    = $this->files->put($finalFile, "issues/{$issue->id}");

        $manuscript->update([
            'issue_id'            => $issue->id,
            'final_file_path'     => $path,
            'final_original_name' => $finalFile->getClientOriginalName(),
        ]);

        $this->files->delete($oldPath);

        return $manuscript;
    }

    /** @throws DomainException */
    public function detachManuscript(Issue $issue, Manuscript $manuscript): void
    {
        $this->ensureDraft($issue);

        if ($manuscript->issue_id !== $issue->id) {
            throw new DomainException('Naskah ini tidak berada pada edisi tersebut.');
        }

        $this->files->delete($manuscript->final_file_path);

        $manuscript->update([
            'issue_id'            => null,
            'final_file_path'     => null,
            'final_original_name' => null,
        ]);
    }

    // -------------------------------------------------------------------------
    // Publikasi
    // -------------------------------------------------------------------------

    /**
     * Terbitkan edisi: semua naskahnya menjadi 'diterbitkan'.
     *
     * @throws DomainException bila edisi kosong atau ada naskah tanpa file final
     */
    public function publish(Issue $issue): Issue
    {
        $this->ensureDraft($issue);

        $manuscripts = $issue->manuscripts()->get();

        if ($manuscripts->isEmpty()) {
            throw new DomainException('Edisi belum memiliki naskah untuk diterbitkan.');
        }

        if ($missing = $manuscripts->whereNull('final_file_path')->count()) {
            throw new DomainException("{$missing} naskah belum memiliki berkas PDF final (camera-ready).");
        }

        return DB::transaction(function () use ($issue, $manuscripts) {
            $now = now();

            $issue->manuscripts()->update(['status' => 'diterbitkan', 'published_at' => $now]);
            $issue->update(['status' => Issue::PUBLISHED, 'published_at' => $now]);

            $manuscripts->each(fn (Manuscript $m) => $this->notifier->published($m));

            return $issue->refresh();
        });
    }

    /** @throws DomainException */
    private function ensureDraft(Issue $issue): void
    {
        if (! $issue->isDraft()) {
            throw new DomainException('Edisi yang sudah diterbitkan tidak dapat diubah.');
        }
    }
}
