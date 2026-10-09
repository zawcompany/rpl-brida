<?php

namespace App\Http\Controllers;

use App\Http\Requests\Public\SearchArticlesRequest;
use App\Models\Issue;
use App\Services\CitationFormatter;
use App\Services\PublicService;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Halaman publik (tanpa autentikasi): beranda, telusuri & detail artikel, arsip, dan berkas PDF.
 * Controller hanya menyusun respons; query & aturan akses ada di PublicService.
 */
class PublicController extends Controller
{
    public function __construct(
        private readonly PublicService $public,
        private readonly CitationFormatter $citations,
    ) {
    }

    public function home(): View
    {
        $current = $this->public->currentIssue();

        return view('landing', [
            'stats'   => $this->public->stats(),
            'current' => $current,
            'archive' => $this->public->recentArchive($current),
        ]);
    }

    // ------------------------------------------------------------------ Artikel

    public function index(SearchArticlesRequest $request): View
    {
        return view('reader_public.articles.index', [
            'articles' => $this->public->search($request->validated()),
            'fields'   => $this->public->fieldsWithArticles(),
            'filters'  => $request->validated(),
        ]);
    }

    public function article(int $id): View
    {
        $article = $this->public->findPublished($id);

        return view('reader_public.articles.articles', [
            'article'   => $article,
            'citations' => $this->citations->all($article),
            'hasPdf'    => filled($article->final_file_path),
        ]);
    }

    /** Pratinjau PDF di halaman (iframe) — inline, tidak menambah penghitung unduhan. */
    public function pdf(int $id): StreamedResponse
    {
        return $this->public->pdfResponse($id, download: false);
    }

    /** Unduh PDF asli + penghitung unduhan. */
    public function download(int $id): StreamedResponse
    {
        return $this->public->pdfResponse($id, download: true);
    }

    // ------------------------------------------------------------------ Arsip

    public function archives(): View
    {
        return view('reader_public.archives.index', ['archives' => $this->public->archives()]);
    }

    public function issue(Issue $issue): View
    {
        return view('reader_public.archives.show', ['issue' => $this->public->issueWithArticles($issue)]);
    }

    // ------------------------------------------------------------------ Panduan penulis

    public function template(): BinaryFileResponse
    {
        return response()->download(resource_path('templates/template-naskah.docx'), 'Template-Naskah-SIMPIL.docx');
    }
}
