<?php

namespace Tests\Feature;

use App\Models\Issue;
use App\Models\Manuscript;
use App\Models\ResearchField;
use App\Models\User;
use App\Services\CitationFormatter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicReaderTest extends TestCase
{
    use RefreshDatabase;

    private User $author;
    private int $issueNo = 0;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->author = User::create(['name' => 'Budi Santoso', 'email' => 'a@t.test', 'password' => 'password', 'role' => 'Author', 'institution' => 'Universitas X']);
    }

    private function issue(string $status = 'published', int $year = 2026, int $volume = 1): Issue
    {
        return Issue::create(['volume' => $volume, 'number' => ++$this->issueNo, 'year' => $year, 'status' => $status, 'published_at' => $status === 'published' ? now() : null]);
    }

    private function article(string $title, string $status = 'diterbitkan', ?Issue $issue = null, array $extra = []): Manuscript
    {
        $issue ??= $this->issue();
        $m = Manuscript::create($extra + [
            'author_id' => $this->author->id, 'title' => $title, 'abstract' => 'Abstrak ' . $title,
            'keywords' => 'riset, ' . $title, 'status' => $status, 'issue_id' => $issue->id,
            'research_field_id' => ResearchField::firstOrCreate(['slug' => 'x'], ['name' => 'Bidang X'])->id,
            'published_at' => now(),
            'final_file_path' => "issues/{$issue->id}/{$title}.pdf", 'final_original_name' => 'final.pdf',
        ]);
        if ($m->final_file_path) {
            Storage::disk('local')->put($m->final_file_path, '%PDF-1.4 isi');
        }
        if (isset($extra['download_count'])) {
            $m->forceFill(['download_count' => $extra['download_count']])->save(); // kolom ini tidak mass-assignable
        }

        return $m;
    }

    // ------------------------------------------------------------ beranda

    public function test_home_is_public_with_stats_current_issue_and_sections(): void
    {
        $old = $this->issue('published', 2025);
        $this->article('Artikel Lama', 'diterbitkan', $old, ['download_count' => 5]);
        $new = $this->issue('published', 2026, 2);
        $this->article('Artikel Terbaru', 'diterbitkan', $new, ['download_count' => 7]);
        $this->article('Masih Draft', 'disetujui', $this->issue('draft'));

        $this->get('/')->assertOk()
            ->assertSee('Ajukan Naskah')->assertSee('Telusuri Artikel')
            ->assertSee('Terbitan Terbaru')->assertSee('Artikel Terbaru')->assertSee($new->label)
            ->assertDontSee('Masih Draft')
            ->assertSee('Arsip Terbitan')->assertSee('Fokus')->assertSee('Panduan Penulis')
            ->assertSee('Unduh Template Naskah');

        $this->get('/')->assertViewHas('stats', ['articles' => 2, 'volumes' => 2, 'downloads' => 12]);
    }

    public function test_home_works_with_no_published_issue(): void
    {
        $this->get('/')->assertOk()->assertSee('Belum ada edisi yang diterbitkan');
    }

    public function test_hero_cta_depends_on_login_state(): void
    {
        $this->get('/')->assertSee(route('register'));
        $this->actingAs($this->author)->get('/')->assertSee(route('author.manuscripts.create'));
    }

    // ------------------------------------------------------------ telusuri

    public function test_listing_shows_only_published_and_supports_search_and_field_filter(): void
    {
        $other = ResearchField::create(['name' => 'Bidang Lain', 'slug' => 'lain']);
        $this->article('Tata Kelola Digital');
        $this->article('Pertanian Cerdas', 'diterbitkan', null, ['research_field_id' => $other->id]);
        $this->article('Draft Rahasia', 'disetujui', $this->issue('draft'));
        $this->article('Status Salah', 'ditinjau');
        $this->article('Edisi Belum Terbit', 'diterbitkan', $this->issue('draft'));

        $this->get(route('reader.index'))->assertOk()
            ->assertSee('Tata Kelola Digital')->assertSee('Pertanian Cerdas')
            ->assertDontSee('Draft Rahasia')->assertDontSee('Status Salah')->assertDontSee('Edisi Belum Terbit');

        $this->get(route('reader.index', ['q' => 'Pertanian']))->assertSee('Pertanian Cerdas')->assertDontSee('Tata Kelola Digital');
        $this->get(route('reader.index', ['field' => $other->id]))->assertSee('Pertanian Cerdas')->assertDontSee('Tata Kelola Digital');
        $this->get(route('reader.index', ['q' => '%']))->assertDontSee('Pertanian Cerdas'); // wildcard di-escape
    }

    public function test_listing_validates_input_and_avoids_n_plus_one(): void
    {
        $this->get(route('reader.index', ['q' => str_repeat('a', 101)]))->assertSessionHasErrors('q');
        $this->get(route('reader.index', ['field' => 'abc']))->assertSessionHasErrors('field');
        $this->get(route('reader.index', ['field' => 99999]))->assertSessionHasErrors('field');

        $issue = $this->issue();
        foreach (range(1, 9) as $i) {
            $this->article("Artikel $i", 'diterbitkan', $issue);
        }

        DB::enableQueryLog();
        $this->get(route('reader.index'))->assertOk();
        $this->assertLessThan(15, count(DB::getQueryLog()), 'Terdeteksi N+1 pada daftar artikel');
    }

    // ------------------------------------------------------------ detail, pratinjau, unduh

    public function test_article_detail_shows_metadata_pdf_viewer_and_citations(): void
    {
        $m = $this->article('Judul Lengkap', 'diterbitkan', null, ['co_authors' => [['name' => 'Siti Aminah']], 'doi' => '10.1234/abc']);

        $this->get(route('reader.article', $m->id))->assertOk()
            ->assertSee('Judul Lengkap')->assertSee('Budi Santoso')->assertSee('Universitas X')->assertSee('Siti Aminah')
            ->assertSee('10.1234/abc')->assertSee('Abstrak Judul Lengkap')
            ->assertSee(route('articles.pdf', $m->id))->assertSee('Unduh PDF')
            ->assertSee('Cara Mengutip')->assertSee('Santoso, B.');
    }

    public function test_unpublished_articles_are_404_everywhere(): void
    {
        $draftIssue = $this->article('Edisi Draft', 'diterbitkan', $this->issue('draft'));
        $wrongStatus = $this->article('Belum Terbit', 'disetujui');
        $inReview = $this->article('Sedang Review', 'ditinjau');

        foreach ([$draftIssue, $wrongStatus, $inReview] as $m) {
            $this->get(route('reader.article', $m->id))->assertNotFound();
            $this->get(route('articles.pdf', $m->id))->assertNotFound();
            $this->get(route('articles.download', $m->id))->assertNotFound();
        }
        $this->get('/articles/99999')->assertNotFound();
        $this->get('/download/abc')->assertNotFound();
    }

    public function test_pdf_preview_is_inline_without_counting_and_download_counts(): void
    {
        $m = $this->article('Artikel PDF');

        $preview = $this->get(route('articles.pdf', $m->id))->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertStringContainsString('inline', $preview->headers->get('Content-Disposition'));
        $this->assertSame(0, $m->fresh()->download_count);

        $dl = $this->get(route('articles.download', $m->id))->assertOk();
        $this->assertStringContainsString('attachment', $dl->headers->get('Content-Disposition'));
        $this->assertStringContainsString('artikel-pdf.pdf', $dl->headers->get('Content-Disposition'));
        $this->get(route('articles.download', $m->id));
        $this->assertSame(2, $m->fresh()->download_count);
    }

    public function test_missing_pdf_file_is_404_and_not_counted(): void
    {
        $m = $this->article('Tanpa Berkas', 'diterbitkan', null, ['final_file_path' => null]);

        $this->get(route('articles.download', $m->id))->assertNotFound();
        $this->assertSame(0, $m->fresh()->download_count);
        $this->get(route('reader.article', $m->id))->assertOk()->assertSee('belum tersedia');
    }

    public function test_files_are_not_exposed_as_public_assets(): void
    {
        $m = $this->article('Privat');

        Storage::disk('local')->assertExists($m->final_file_path);
        $this->assertContains($this->get('/storage/' . $m->final_file_path)->status(), [403, 404]);
    }

    // ------------------------------------------------------------ arsip & panduan

    public function test_archives_group_by_year_and_issue_page_lists_articles(): void
    {
        $a = $this->issue('published', 2025);
        $b = $this->issue('published', 2026);
        $draft = $this->issue('draft', 2026);
        $this->article('Artikel 2025', 'diterbitkan', $a);
        $this->article('Artikel 2026', 'diterbitkan', $b);

        $this->get(route('archives.index'))->assertOk()
            ->assertSeeInOrder(['2026', $b->label, '2025', $a->label])->assertDontSee($draft->label);

        $this->get(route('archives.show', $a))->assertOk()->assertSee('Artikel 2025')->assertDontSee('Artikel 2026');
        $this->get(route('archives.show', $draft))->assertNotFound();
    }

    public function test_author_template_is_downloadable_and_legacy_urls_redirect(): void
    {
        $this->get(route('guide.template'))->assertOk()->assertDownload('Template-Naskah-SIMPIL.docx');

        $this->get('/reader')->assertRedirect('/articles');
        $this->get('/reader/artikel/7')->assertRedirect('/articles/7');
    }

    // ------------------------------------------------------------ sitasi

    public function test_citation_formats(): void
    {
        $m = $this->article('Judul Uji', 'diterbitkan', $this->issue('published', 2026, 3), ['co_authors' => [['name' => 'Siti Aminah']], 'doi' => '10.1/xyz']);
        $m->load(['author', 'issue']);

        $c = app(CitationFormatter::class)->all($m);

        $this->assertStringContainsString('Santoso, B., & Aminah, S. (' . now()->format('Y') . '). Judul Uji.', $c['apa']);
        $this->assertStringContainsString('3(', $c['apa']);
        $this->assertStringContainsString('B. Santoso, and S. Aminah, "Judul Uji,"', $c['ieee']);
        $this->assertStringContainsString('Santoso, Budi, and Siti Aminah. "Judul Uji."', $c['mla']);
        foreach ($c as $text) {
            $this->assertStringContainsString('https://doi.org/10.1/xyz', $text);
        }
    }
}
