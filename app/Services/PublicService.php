<?php

namespace App\Services;

use App\Models\Issue;
use App\Models\Manuscript;
use App\Models\ResearchField;
use App\Services\Files\ManuscriptFileStore;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * PublicService — seluruh query & aturan akses untuk halaman publik (tanpa login).
 * Satu sumber kebenaran "artikel terbit": naskah berstatus 'diterbitkan' pada edisi 'published'
 * (Manuscript::scopePublished). Naskah draft / dalam review tidak pernah keluar dari service ini.
 */
class PublicService
{
    private const PER_PAGE = 9;
    private const HOME_ARCHIVE_ISSUES = 6;

    public function __construct(private readonly ManuscriptFileStore $files)
    {
    }

    // -------------------------------------------------------------------------
    // Beranda
    // -------------------------------------------------------------------------

    /** @return array{articles: int, volumes: int, downloads: int} */
    public function stats(): array
    {
        return [
            'articles'  => Manuscript::published()->count(),
            'volumes'   => Issue::published()->distinct()->count('volume'),
            'downloads' => (int) Manuscript::published()->sum('download_count'),
        ];
    }

    /** Edisi terbit paling baru beserta artikelnya (eager loading), atau null bila belum ada. */
    public function currentIssue(): ?Issue
    {
        return Issue::published()
            ->orderByDesc('year')->orderByDesc('volume')->orderByDesc('number')
            ->with(['manuscripts' => fn ($q) => $q->published()->with(['author', 'researchField'])->orderBy('id')])
            ->first();
    }

    /** Edisi terbit terbaru (selain edisi aktif) untuk ringkasan arsip di beranda. */
    public function recentArchive(?Issue $exclude = null): Collection
    {
        return $this->publishedIssues()
            ->when($exclude, fn (Builder $q) => $q->whereKeyNot($exclude->id))
            ->limit(self::HOME_ARCHIVE_ISSUES)
            ->get();
    }

    // -------------------------------------------------------------------------
    // Telusuri & arsip
    // -------------------------------------------------------------------------

    /** @param array{q?: ?string, field?: ?int|string, issue?: ?int|string} $filters */
    public function search(array $filters = []): LengthAwarePaginator
    {
        $term = $filters['q'] ?? null;

        return Manuscript::published()
            ->with(['author', 'researchField', 'issue'])
            ->when(filled($term), function (Builder $q) use ($term) {
                $like = '%' . addcslashes($term, '%_\\') . '%';

                $q->where(fn (Builder $q) => $q->where('title', 'like', $like)
                    ->orWhere('keywords', 'like', $like)
                    ->orWhere('abstract', 'like', $like));
            })
            ->when(filled($filters['field'] ?? null), fn (Builder $q) => $q->where('research_field_id', $filters['field']))
            ->when(filled($filters['issue'] ?? null), fn (Builder $q) => $q->where('issue_id', $filters['issue']))
            ->latest('published_at')->latest('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();
    }

    /** Bidang yang memiliki artikel terbit (untuk filter). */
    public function fieldsWithArticles(): Collection
    {
        return ResearchField::whereHas('manuscripts', fn (Builder $q) => $q->published())->orderBy('name')->get(['id', 'name']);
    }

    /** Arsip: edisi terbit dikelompokkan per tahun (terbaru dulu). @return \Illuminate\Support\Collection<int, Collection> */
    public function archives(): \Illuminate\Support\Collection
    {
        return $this->publishedIssues()->get()->groupBy('year');
    }

    /** @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException bila edisi belum terbit */
    public function issueWithArticles(Issue $issue): Issue
    {
        abort_unless($issue->isPublished(), 404);

        return $issue->load(['manuscripts' => fn ($q) => $q->published()->with(['author', 'researchField'])->orderBy('id')]);
    }

    // -------------------------------------------------------------------------
    // Artikel & berkas
    // -------------------------------------------------------------------------

    /** @throws \Illuminate\Database\Eloquent\ModelNotFoundException bila tidak ada / belum terbit */
    public function findPublished(int $id): Manuscript
    {
        return Manuscript::published()->with(['author', 'researchField', 'issue'])->findOrFail($id);
    }

    /** Pratinjau (inline) atau unduhan (attachment, menambah penghitung) PDF artikel terbit. */
    public function pdfResponse(int $id, bool $download): StreamedResponse
    {
        $article = $this->findPublished($id);

        abort_unless($this->files->exists($article->final_file_path), 404, 'Berkas artikel belum tersedia.');

        if ($download) {
            $article->increment('download_count'); // atomik di database
        }

        $name = str($article->title)->slug()->limit(80, '')->append('.pdf')->toString();

        return $this->files->response($article->final_file_path, $name, inline: ! $download);
    }

    // -------------------------------------------------------------------------

    private function publishedIssues(): Builder
    {
        return Issue::published()
            ->withCount(['manuscripts as articles_count' => fn (Builder $q) => $q->published()])
            ->orderByDesc('year')->orderByDesc('volume')->orderByDesc('number');
    }
}
