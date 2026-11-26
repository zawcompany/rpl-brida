<?php

namespace Tests\Feature;

use App\Models\Issue;
use App\Models\Journal;
use App\Models\Manuscript;
use App\Models\ResearchField;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Fitur kepatuhan hukum: consent registrasi, halaman legal, deklarasi submit, e-ISSN & lisensi, ekspor metadata. */
class LegalComplianceTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        return User::create(['name' => $role.uniqid(), 'email' => uniqid().'@t.test', 'password' => 'password', 'role' => $role]);
    }

    private function registerData(array $extra = []): array
    {
        return $extra + ['name' => 'Penulis Baru', 'email' => 'baru@t.test', 'password' => 'password123', 'password_confirmation' => 'password123'];
    }

    // ------------------------------------------------------------- 1. consent registrasi

    public function test_registration_requires_privacy_consent(): void
    {
        $this->post('/register', $this->registerData())->assertSessionHasErrors('consent');
        $this->assertDatabaseMissing('users', ['email' => 'baru@t.test']);
    }

    public function test_registration_stores_consent_proof(): void
    {
        $this->post('/register', $this->registerData(['consent' => '1']))->assertRedirect('/dashboard');

        $u = User::where('email', 'baru@t.test')->firstOrFail();
        $this->assertNotNull($u->privacy_consented_at);
        $this->assertSame(config('simpil.legal.version'), $u->consent_version);
    }

    public function test_register_page_links_to_policies(): void
    {
        $this->get('/register')->assertOk()->assertSee(route('legal.privacy'), false)->assertSee(route('legal.terms'), false);
    }

    // ------------------------------------------------------------- 2/7. halaman legal

    public function test_legal_pages_are_public(): void
    {
        $this->get(route('legal.privacy'))->assertOk()->assertSee('Kebijakan Privasi')->assertSee('UU No. 27 Tahun 2022');
        $this->get(route('legal.terms'))->assertOk()->assertSee('Syarat Penggunaan');
        $this->get(route('legal.ethics'))->assertOk()->assertSee('Konflik kepentingan');
    }

    public function test_footer_links_to_legal_pages(): void
    {
        $this->get('/')->assertSee(route('legal.privacy'), false)->assertSee(route('legal.ethics'), false);
    }

    // ------------------------------------------------------------- 6/7. deklarasi submit

    private function submitPayload(Journal $j, ResearchField $f, array $extra = []): array
    {
        return $extra + [
            'journal_id' => $j->id, 'title' => 'T', 'research_field_id' => $f->id, 'abstract' => 'A', 'keywords' => 'k',
            'file' => UploadedFile::fake()->createWithContent('n.pdf', "%PDF-1.4\n1 0 obj<<>>endobj\ntrailer<<>>\n%%EOF"),
        ];
    }

    public function test_submit_requires_declarations(): void
    {
        Storage::fake('local');
        $j = Journal::default();
        $author = $this->user('Author');
        $author->joinJournal($j, Journal::AUTHOR);
        $f = ResearchField::create(['name' => 'X', 'slug' => 'x']);

        $this->actingAs($author)->post(route('author.manuscripts.store'), $this->submitPayload($j, $f))
            ->assertSessionHasErrors(['declare_originality', 'declare_copyright', 'conflict_of_interest']);

        $this->post(route('author.manuscripts.store'), $this->submitPayload($j, $f, [
            'declare_originality' => '1', 'declare_copyright' => '1', 'conflict_of_interest' => 'declared',
        ]))->assertSessionHasErrors('conflict_note');

        $this->assertSame(0, Manuscript::count());
    }

    public function test_submit_records_declarations_and_license_snapshot(): void
    {
        Storage::fake('local');
        $j = Journal::default();
        $j->update(['license' => 'CC-BY-NC-4.0']);
        $author = $this->user('Author');
        $author->joinJournal($j, Journal::AUTHOR);
        $f = ResearchField::create(['name' => 'X', 'slug' => 'x']);

        $this->actingAs($author)->post(route('author.manuscripts.store'), $this->submitPayload($j, $f, [
            'declare_originality' => '1', 'declare_copyright' => '1',
            'conflict_of_interest' => 'declared', 'conflict_note' => 'Dibiayai pihak ketiga.',
        ]))->assertSessionHasNoErrors();

        $m = Manuscript::firstOrFail();
        $this->assertNotNull($m->declarations_accepted_at);
        $this->assertTrue($m->conflict_of_interest);
        $this->assertSame('Dibiayai pihak ketiga.', $m->conflict_note);
        $this->assertSame('CC-BY-NC-4.0', $m->license);
    }

    public function test_submit_form_shows_declarations(): void
    {
        $this->actingAs($this->user('Author'))->get(route('author.manuscripts.create'))
            ->assertOk()->assertSee('Pernyataan Penulis')->assertSee('Konflik kepentingan');
    }

    // ------------------------------------------------------------- 5/8. e-ISSN & lisensi jurnal

    public function test_admin_sets_e_issn_and_license(): void
    {
        $admin = $this->user('Administrator');
        $j = Journal::default();

        $this->actingAs($admin)->put(route('admin.journals.update', $j), ['name' => $j->name, 'e_issn' => '2345-6789', 'license' => 'CC-BY-SA-4.0'])
            ->assertSessionHasNoErrors();
        $j->refresh();
        $this->assertSame('2345-6789', $j->e_issn);
        $this->assertSame('CC-BY-SA-4.0', $j->license);

        $this->put(route('admin.journals.update', $j), ['name' => $j->name, 'e_issn' => '1234-56', 'license' => 'CC-BY-SA-4.0'])
            ->assertSessionHasErrors('e_issn');
        $this->put(route('admin.journals.update', $j), ['name' => $j->name, 'license' => 'MIT'])
            ->assertSessionHasErrors('license');
    }

    public function test_blank_license_keeps_existing_license(): void
    {
        $j = Journal::default();
        $j->update(['license' => 'CC-BY-NC-4.0']);

        $this->actingAs($this->user('Administrator'))->put(route('admin.journals.update', $j), ['name' => $j->name])->assertSessionHasNoErrors();

        $this->assertSame('CC-BY-NC-4.0', $j->fresh()->license);
    }

    private function publishedArticle(Journal $j): Manuscript
    {
        $author = $this->user('Author');
        $issue = Issue::firstOrCreate(['journal_id' => $j->id, 'volume' => 1, 'number' => 1, 'year' => 2026], ['status' => Issue::PUBLISHED, 'published_at' => now()]);
        $f = ResearchField::firstOrCreate(['slug' => 'x'], ['name' => 'X']);

        return Manuscript::create([
            'journal_id' => $j->id, 'author_id' => $author->id, 'research_field_id' => $f->id, 'title' => 'Judul <Terbit>',
            'abstract' => 'Abstrak.', 'keywords' => 'riset, brida', 'file_path' => 'x', 'status' => 'diterbitkan', 'issue_id' => $issue->id,
            'published_at' => now(), 'doi' => '10.1234/abc', 'co_authors' => [['name' => 'Rekan']],
            'license' => 'CC-BY-4.0',
        ]);
    }

    public function test_article_and_journal_pages_show_license_and_e_issn(): void
    {
        $j = Journal::default();
        $j->update(['e_issn' => '2345-6789']);
        $a = $this->publishedArticle($j);

        $this->get(route('reader.article', $a->id))->assertOk()
            ->assertSee('CC BY 4.0')->assertSee('creativecommons.org/licenses/by/4.0', false)->assertSee('2345-6789')->assertSee('Hak cipta tetap pada penulis');
        $this->get(route('journals.show', $j))->assertOk()->assertSee('2345-6789')->assertSee('CC BY 4.0');
    }

    // ------------------------------------------------------------- 10. ekspor metadata

    public function test_metadata_export_xml_contains_only_published_and_no_private_data(): void
    {
        $j = Journal::default();
        $a = $this->publishedArticle($j);
        $author = $a->author;
        // naskah belum terbit tidak boleh bocor
        Manuscript::create(['journal_id' => $j->id, 'author_id' => $author->id, 'research_field_id' => $a->research_field_id, 'title' => 'RAHASIA', 'abstract' => 'x', 'keywords' => 'k', 'file_path' => 'y', 'status' => 'pending']);

        $res = $this->get(route('journals.metadata', [$j, 'xml']))->assertOk();
        $this->assertStringContainsString('application/xml', $res->headers->get('Content-Type'));
        $xml = $res->getContent();
        $this->assertNotFalse(simplexml_load_string($xml));
        $this->assertStringContainsString('Judul &lt;Terbit&gt;', $xml);
        $this->assertStringContainsString('https://doi.org/10.1234/abc', $xml);
        $this->assertStringContainsString('Rekan', $xml);
        $this->assertStringNotContainsString('RAHASIA', $xml);
        $this->assertStringNotContainsString($author->email, $xml);
    }

    public function test_metadata_export_json(): void
    {
        $j = Journal::default();
        $this->publishedArticle($j);

        $this->get(route('journals.metadata', [$j, 'json']))->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonPath('records.0.title', 'Judul <Terbit>')
            ->assertJsonPath('records.0.subject', ['riset', 'brida'])
            ->assertJsonPath('records.0.rights', 'https://creativecommons.org/licenses/by/4.0/');
    }

    public function test_journal_urls_also_accept_numeric_id(): void
    {
        $j = Journal::default();
        $this->publishedArticle($j);

        $this->get("/jurnal/{$j->id}/metadata.json")->assertOk()->assertJsonPath('count', 1);
        $this->get("/jurnal/{$j->id}/metadata.xml")->assertOk();
        $this->get("/jurnal/{$j->id}")->assertOk()->assertSee($j->name);
        $this->get('/jurnal/99999/metadata.json')->assertNotFound();
    }

    // ------------------------------------------------------------- DOI oleh editor

    private function editorFor(Journal $j): User
    {
        $e = $this->user('Editor');
        // Akun baru otomatis menjadi anggota jurnal bawaan; batasi hanya ke jurnal yang dimaksud.
        $default = Journal::default();
        if ($default && $default->id !== $j->id) {
            $e->leaveJournal($default, Journal::EDITOR);
        }
        $e->joinJournal($j, Journal::EDITOR);

        return $e;
    }

    public function test_editor_sets_and_clears_doi_and_url_form_is_normalized(): void
    {
        $j = Journal::default();
        $a = $this->publishedArticle($j);
        $a->update(['doi' => null]);
        $e = $this->editorFor($j);
        $url = route('editor.issues.manuscripts.doi', [$a->issue, $a]);

        $this->actingAs($e)->patch($url, ['doi' => ' https://doi.org/10.55555/simpil.2026.001 '])->assertSessionHasNoErrors();
        $this->assertSame('10.55555/simpil.2026.001', $a->fresh()->doi);

        $this->get(route('reader.article', $a->id))->assertSee('https://doi.org/10.55555/simpil.2026.001', false);

        $this->patch($url, ['doi' => ''])->assertSessionHasNoErrors();
        $this->assertNull($a->fresh()->doi);
    }

    public function test_doi_validation_format_and_uniqueness(): void
    {
        $j = Journal::default();
        $a = $this->publishedArticle($j);          // doi 10.1234/abc
        $b = $this->publishedArticle($j);
        $b->update(['doi' => null]);
        $e = $this->editorFor($j);

        $this->actingAs($e)->patch(route('editor.issues.manuscripts.doi', [$b->issue, $b]), ['doi' => 'bukan-doi'])
            ->assertSessionHasErrors('doi');
        $this->patch(route('editor.issues.manuscripts.doi', [$b->issue, $b]), ['doi' => '10.1234/abc'])
            ->assertSessionHasErrors('doi');
        $this->patch(route('editor.issues.manuscripts.doi', [$a->issue, $a]), ['doi' => '10.1234/abc'])
            ->assertSessionHasNoErrors(); // DOI miliknya sendiri tidak dianggap duplikat
    }

    public function test_doi_access_is_limited_to_own_journal_and_editor_role(): void
    {
        $j = Journal::default();
        $other = Journal::create(['name' => 'Jurnal Lain']);
        $a = $this->publishedArticle($j);
        $url = route('editor.issues.manuscripts.doi', [$a->issue, $a]);

        $this->actingAs($this->editorFor($other))->patch($url, ['doi' => '10.9999/x'])->assertNotFound();
        $this->actingAs($this->user('Author'))->patch($url, ['doi' => '10.9999/x'])->assertForbidden();
        $this->assertSame('10.1234/abc', $a->fresh()->doi);
    }

    public function test_doi_rejects_manuscript_not_in_that_issue(): void
    {
        $j = Journal::default();
        $a = $this->publishedArticle($j);
        $other = Issue::create(['journal_id' => $j->id, 'volume' => 2, 'number' => 1, 'year' => 2026, 'status' => Issue::DRAFT]);

        $this->actingAs($this->editorFor($j))->patch(route('editor.issues.manuscripts.doi', [$other, $a]), ['doi' => '10.9999/x'])
            ->assertSessionHas('error');
        $this->assertSame('10.1234/abc', $a->fresh()->doi);
    }

    public function test_metadata_export_hidden_for_inactive_journal_and_bad_format(): void
    {
        $j = Journal::default();
        $this->get('/jurnal/'.$j->slug.'/metadata.csv')->assertNotFound();
        $j->update(['is_active' => false]);
        $this->get(route('journals.metadata', [$j, 'xml']))->assertNotFound();
    }
}
