<?php

namespace Tests\Feature;

use App\Models\Issue;
use App\Models\Journal;
use App\Models\Manuscript;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Tampilan publik multijurnal: kartu jurnal (cover, nama, bidang), terbitan terbaru per jurnal,
 * halaman jurnal dan edisi dengan cover besar, serta tombol kembali ke dashboard.
 */
class PublicJournalPagesTest extends TestCase
{
    use RefreshDatabase;

    private Journal $a;   // jurnal bawaan (dibuat migrasi), bidang awal terisi
    private Journal $b;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');

        $this->a = Journal::default();
        $this->b = Journal::create(['name' => 'Jurnal Kesehatan', 'abbreviation' => 'JKM', 'field' => 'Kesehatan Masyarakat']);
    }

    private function user(string $role): User
    {
        return User::create(['name' => $role . ' ' . uniqid(), 'email' => uniqid() . '@t.test', 'password' => 'password', 'role' => $role]);
    }

    private function issue(Journal $journal, int $year, int $volume, int $number, array $extra = []): Issue
    {
        return Issue::create($extra + [
            'journal_id' => $journal->id, 'volume' => $volume, 'number' => $number, 'year' => $year,
            'status' => 'published', 'published_at' => now(),
        ]);
    }

    private function section(string $html, string $from, string $to): string
    {
        return Str::between($html, $from, $to);
    }

    // ---------------------------------------------------------------- beranda

    public function test_home_has_hoverable_stat_cards(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('hover:-translate-y-1', $html);              // kartu statistik terangkat saat disorot
        $this->assertStringContainsString('hover:shadow-lg', $html);
    }

    public function test_home_journal_cards_show_cover_name_and_field(): void
    {
        $this->b->update(['cover_image' => 'journals/covers/kesehatan.jpg']);
        Storage::disk('public')->put('journals/covers/kesehatan.jpg', 'x');

        $html = $this->section($this->get('/')->assertOk()->getContent(), 'id="jurnal"', 'id="tentang"');

        $this->assertStringContainsString($this->a->name, $html);
        $this->assertStringContainsString('Riset dan Inovasi Daerah', $html);        // bidang jurnal bawaan
        $this->assertStringContainsString('Jurnal Kesehatan', $html);
        $this->assertStringContainsString('Kesehatan Masyarakat', $html);
        $this->assertStringContainsString('journals/covers/kesehatan.jpg', $html);   // cover yang diunggah
        $this->assertStringContainsString('from-red-500', $html);                    // jurnal tanpa cover: sampul gradasi bawaan
    }

    public function test_template_button_is_removed_from_author_guide(): void
    {
        $this->get('/')->assertOk()->assertSee('Panduan Penulis')
            ->assertDontSee('Pilih Jurnal')->assertDontSee('Unduh Template');
    }

    public function test_latest_issues_show_one_cover_and_title_per_journal(): void
    {
        $this->issue($this->a, 2025, 1, 1);
        $latestA = $this->issue($this->a, 2026, 2, 1);
        $this->issue($this->b, 2026, 1, 1, ['title' => 'Edisi Perdana Kesehatan', 'cover_image' => 'issues/covers/b.jpg']);
        Storage::disk('public')->put('issues/covers/b.jpg', 'x');

        $section = $this->section($this->get('/')->assertOk()->getContent(), 'id="terbitan"', 'id="arsip"');

        $this->assertStringContainsString($latestA->label, $section);                 // edisi terbaru jurnal A
        $this->assertStringNotContainsString('Vol. 1 No. 1 (2025)', $section);        // edisi lama jurnal A tidak ikut
        $this->assertStringContainsString('Edisi Perdana Kesehatan', $section);       // judul edisi jurnal B
        $this->assertStringContainsString('issues/covers/b.jpg', $section);           // cover edisi
        $this->assertStringContainsString('JKM', $section);                           // asal jurnal tampil di bawah judul

        // edisi lama tetap ada di arsip
        $archive = $this->section($this->get('/')->getContent(), 'id="arsip"', 'id="fokus"');
        $this->assertStringContainsString('Vol. 1 No. 1 (2025)', $archive);
    }

    public function test_latest_issues_skip_drafts_and_inactive_journals(): void
    {
        $this->issue($this->a, 2026, 1, 1, ['status' => 'draft', 'published_at' => null, 'title' => 'Masih Draft']);
        $this->issue($this->b, 2026, 1, 1, ['title' => 'Milik Jurnal Nonaktif']);
        $this->b->update(['is_active' => false]);

        $section = $this->section($this->get('/')->assertOk()->getContent(), 'id="terbitan"', 'id="arsip"');

        $this->assertStringNotContainsString('Masih Draft', $section);
        $this->assertStringNotContainsString('Milik Jurnal Nonaktif', $section);
        $this->assertStringContainsString('Belum ada edisi yang diterbitkan', $section);
    }

    // ---------------------------------------------------------------- halaman jurnal

    public function test_journals_index_uses_cards_with_cover_name_and_field(): void
    {
        $this->get(route('journals.index'))->assertOk()
            ->assertSee($this->a->name)->assertSee('Riset dan Inovasi Daerah')
            ->assertSee('Jurnal Kesehatan')->assertSee('Kesehatan Masyarakat');
    }

    public function test_journal_page_shows_large_cover_description_template_and_issues(): void
    {
        $this->b->update(['description' => 'Menerbitkan riset kesehatan masyarakat perkotaan.', 'cover_image' => 'journals/covers/jkm.jpg']);
        Storage::disk('public')->put('journals/covers/jkm.jpg', 'x');
        $this->issue($this->b, 2026, 1, 1, ['title' => 'Kesehatan Kota']);

        $this->get(route('journals.show', $this->b))->assertOk()
            ->assertSee('journals/covers/jkm.jpg')                                   // cover besar
            ->assertSee('Jurnal Kesehatan')->assertSee('Kesehatan Masyarakat')
            ->assertSee('Menerbitkan riset kesehatan masyarakat perkotaan.')
            ->assertSee('Template Naskah')
            ->assertSee('Edisi Terbit')->assertSee('Kesehatan Kota');
    }

    public function test_journal_without_cover_gets_gradient_cover_with_initials(): void
    {
        $this->get(route('journals.show', $this->b))->assertOk()
            ->assertSee('from-red-500 via-red-600 to-red-800')->assertSee('JKM');
    }

    public function test_issue_page_shows_large_cover_journal_and_articles(): void
    {
        $issue = $this->issue($this->b, 2026, 1, 1, ['title' => 'Kesehatan Kota', 'cover_image' => 'issues/covers/jkm1.jpg']);
        Storage::disk('public')->put('issues/covers/jkm1.jpg', 'x');
        $author = $this->user('Author');
        Manuscript::create([
            'journal_id' => $this->b->id, 'author_id' => $author->id, 'title' => 'Gizi Anak Perkotaan', 'status' => 'diterbitkan',
            'issue_id' => $issue->id, 'published_at' => now(),
        ]);

        $this->get(route('archives.show', $issue))->assertOk()
            ->assertSee('issues/covers/jkm1.jpg')->assertSee('Jurnal Kesehatan')
            ->assertSee($issue->label)->assertSee('Kesehatan Kota')->assertSee('Gizi Anak Perkotaan');
    }

    // ---------------------------------------------------------------- kembali ke dashboard

    public function test_back_to_dashboard_button_appears_only_when_coming_from_dashboard(): void
    {
        $issue = $this->issue($this->a, 2026, 1, 1);
        $author = $this->user('Author');

        $pages = [route('journals.index'), route('journals.show', $this->a), route('archives.index'), route('archives.show', $issue)];

        foreach ($pages as $url) {
            // dari dashboard: ada tombol, tertuju ke dashboard
            $this->actingAs($author)->get($url . '?dari=dashboard')->assertOk()
                ->assertSee('Kembali ke Dashboard')->assertSee(route('dashboard'), false);

            // lewat menu publik (tanpa penanda): tidak ada tombol
            $this->actingAs($author)->get($url)->assertOk()->assertDontSee('Kembali ke Dashboard');
        }

        // tamu tidak punya dashboard
        auth()->logout();
        $this->get(route('journals.index') . '?dari=dashboard')->assertOk()->assertDontSee('Kembali ke Dashboard');
    }

    public function test_dashboard_marker_is_kept_while_browsing_journal_to_issue(): void
    {
        $issue = $this->issue($this->a, 2026, 1, 1);
        $author = $this->user('Author');
        $this->actingAs($author);

        $this->get(route('journals.index') . '?dari=dashboard')->assertSee('jurnal/' . $this->a->slug . '?dari=dashboard', false);
        $this->get(route('journals.show', $this->a) . '?dari=dashboard')->assertSee('archives/' . $issue->id . '?dari=dashboard', false);
        $this->get(route('archives.show', $issue) . '?dari=dashboard')->assertSee('Kembali ke Dashboard');
    }

    public function test_dashboard_sidebar_link_carries_the_marker(): void
    {
        $author = $this->user('Author');

        $this->actingAs($author)->get(route('author.dashboard'))->assertOk()->assertSee('/jurnal?dari=dashboard', false);
    }

    // ---------------------------------------------------------------- admin: bidang dan cover

    public function test_admin_creates_journal_with_field_and_cover_and_can_replace_or_remove_it(): void
    {
        $admin = $this->user('Administrator');

        $this->actingAs($admin)->post(route('admin.journals.store'), [
            'name' => 'Jurnal Lingkungan', 'abbreviation' => 'JLK', 'field' => 'Lingkungan Hidup',
            'cover' => UploadedFile::fake()->image('cover.jpg', 300, 400),
        ])->assertSessionHasNoErrors();

        $journal = Journal::where('name', 'Jurnal Lingkungan')->firstOrFail();
        $this->assertSame('Lingkungan Hidup', $journal->field);
        $first = $journal->cover_image;
        $this->assertStringStartsWith('journals/covers/', $first);
        Storage::disk('public')->assertExists($first);

        // ganti cover: berkas lama dihapus
        $this->put(route('admin.journals.update', $journal), [
            'name' => 'Jurnal Lingkungan', 'field' => 'Lingkungan Hidup', 'is_active' => 1,
            'cover' => UploadedFile::fake()->image('baru.png', 300, 400),
        ])->assertSessionHasNoErrors();
        $second = $journal->fresh()->cover_image;
        $this->assertNotSame($first, $second);
        Storage::disk('public')->assertMissing($first);
        Storage::disk('public')->assertExists($second);

        // hapus cover: kembali ke sampul gradasi
        $this->put(route('admin.journals.update', $journal), ['name' => 'Jurnal Lingkungan', 'is_active' => 1, 'remove_cover' => 1])
            ->assertSessionHasNoErrors();
        $this->assertNull($journal->fresh()->cover_image);
        Storage::disk('public')->assertMissing($second);
    }

    public function test_journal_cover_must_be_a_real_image(): void
    {
        $this->actingAs($this->user('Administrator'))->post(route('admin.journals.store'), [
            'name' => 'Jurnal Palsu', 'cover' => UploadedFile::fake()->create('cover.jpg', 10, 'application/pdf'),
        ])->assertSessionHasErrors('cover');

        $this->assertDatabaseMissing('journals', ['name' => 'Jurnal Palsu']);
    }

    public function test_admin_pages_show_field_and_cover_inputs(): void
    {
        $this->actingAs($this->user('Administrator'));

        $this->get(route('admin.journals.index'))->assertOk()->assertSee('name="field"', false)->assertSee('name="cover"', false);
        $this->get(route('admin.journals.show', $this->b))->assertOk()->assertSee('name="field"', false)->assertSee('Cover jurnal');
    }
}
