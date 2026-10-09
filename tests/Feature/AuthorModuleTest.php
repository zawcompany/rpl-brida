<?php

namespace Tests\Feature;

use App\Models\Manuscript;
use App\Models\ResearchField;
use App\Models\Review;
use App\Models\User;
use App\Services\AuthorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\MakesDocuments;
use Tests\TestCase;

class AuthorModuleTest extends TestCase
{
    use RefreshDatabase, MakesDocuments;

    private User $author;
    private ResearchField $field;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $this->author = $this->makeUser('Author');
        $this->field = ResearchField::create(['name' => 'Ilmu Komputer', 'slug' => 'ilmu-komputer']);
    }

    private function makeUser(string $role, ?string $email = null): User
    {
        return User::create([
            'name' => "Tes {$role}", 'email' => $email ?? strtolower($role) . '@t.test',
            'password' => 'password', 'role' => $role,
        ]);
    }

    private function makeManuscript(string $status = 'pending', ?User $owner = null, array $attrs = []): Manuscript
    {
        return Manuscript::create($attrs + [
            'author_id' => ($owner ?? $this->author)->id, 'research_field_id' => $this->field->id,
            'title' => 'Naskah ' . $status, 'abstract' => 'Abstrak', 'keywords' => 'a, b',
            'file_path' => 'manuscripts/x.pdf', 'file_original_name' => 'x.pdf',
            'status' => $status, 'submitted_at' => now(),
        ]);
    }

    private function payload(array $override = []): array
    {
        return $override + [
            'title' => 'Judul Baru', 'research_field_id' => $this->field->id,
            'abstract' => 'Abstrak lengkap.', 'keywords' => 'riset, brida',
            'co_authors' => [['name' => 'Budi', 'email' => 'budi@x.test'], ['name' => '', 'email' => '']],
            'file' => $this->pdf(),
        ];
    }

    // ---------------------------------------------------------------- akses

    public function test_only_author_role_can_access(): void
    {
        $this->get(route('author.manuscripts.index'))->assertRedirect(route('login'));

        foreach (['Editor', 'Reviewer'] as $role) {
            $this->actingAs($this->makeUser($role))->get(route('author.manuscripts.index'))->assertForbidden();
        }

        $this->actingAs($this->author)->get(route('author.manuscripts.index'))->assertOk();
    }

    public function test_dashboard_route_renders_author_dashboard_with_stats(): void
    {
        $this->makeManuscript('ditinjau');
        $this->makeManuscript('revisi');
        $this->makeManuscript('diterbitkan');
        $this->makeManuscript('pending', $this->makeUser('Author', 'other@t.test')); // milik orang lain

        $stats = collect(app(AuthorService::class)->getDashboardStats($this->author))->pluck('count', 'label');

        $this->assertSame(3, $stats['Total Naskah']);
        $this->assertSame(1, $stats['Sedang Ditinjau']);
        $this->assertSame(1, $stats['Perlu Revisi']);
        $this->assertSame(1, $stats['Diterbitkan']);

        $this->actingAs($this->author)->get('/dashboard')
            ->assertOk()->assertSee('Aktivitas / Naskah Terbaru')->assertSee('Perlu Revisi');
    }

    public function test_dashboard_recent_shows_only_five_latest_own_manuscripts(): void
    {
        foreach (range(1, 7) as $i) {
            $this->makeManuscript('pending', null, ['title' => "Naskah ke-$i", 'submitted_at' => now()->addMinutes($i)]);
        }

        $recent = app(AuthorService::class)->getRecentManuscripts($this->author);

        $this->assertCount(5, $recent);
        $this->assertSame('Naskah ke-7', $recent->first()->title);
    }

    // ---------------------------------------------------------------- Naskah Baru

    public function test_submit_form_lists_research_fields(): void
    {
        $this->actingAs($this->author)->get(route('author.manuscripts.create'))
            ->assertOk()->assertSee('Ilmu Komputer');
    }

    public function test_author_can_submit_manuscript(): void
    {
        $this->actingAs($this->author)->post(route('author.manuscripts.store'), $this->payload())
            ->assertRedirect(route('author.manuscripts.index'))->assertSessionHas('success');

        $m = Manuscript::firstOrFail();
        $this->assertSame($this->author->id, $m->author_id);
        $this->assertSame('pending', $m->status);
        $this->assertNotNull($m->submitted_at);
        $this->assertCount(1, $m->co_authors); // baris kosong dibuang
        $this->assertSame('Budi', $m->co_authors[0]['name']);
        Storage::disk('local')->assertExists($m->file_path);
    }

    public function test_submit_validation(): void
    {
        $this->actingAs($this->author);

        $this->post(route('author.manuscripts.store'), [])
            ->assertSessionHasErrors(['title', 'research_field_id', 'abstract', 'keywords', 'file']);

        $this->post(route('author.manuscripts.store'), $this->payload(['file' => UploadedFile::fake()->create('a.exe', 10)]))
            ->assertSessionHasErrors('file');

        $this->post(route('author.manuscripts.store'), $this->payload(['file' => UploadedFile::fake()->create('big.pdf', 10241, 'application/pdf')]))
            ->assertSessionHasErrors('file');

        $this->post(route('author.manuscripts.store'), $this->payload(['co_authors' => [['name' => '', 'email' => 'x@y.test']]]))
            ->assertSessionHasErrors('co_authors.0.name');

        $this->post(route('author.manuscripts.store'), $this->payload(['research_field_id' => 999]))
            ->assertSessionHasErrors('research_field_id');

        $this->assertSame(0, Manuscript::count());
    }

    // ---------------------------------------------------------------- Naskah Saya

    public function test_my_manuscripts_search_status_filter_and_isolation(): void
    {
        $this->makeManuscript('ditinjau', null, ['title' => 'Tata Kelola Digital']);
        $this->makeManuscript('pending', null, ['title' => 'Inovasi Daerah']);
        $this->makeManuscript('ditinjau', $this->makeUser('Author', 'o@t.test'), ['title' => 'Tata Milik Orang Lain']);

        $json = fn (array $q) => $this->actingAs($this->author)->getJson(route('author.manuscripts.index', $q))->assertOk()->json();

        $this->assertSame(2, $json([])['total']);
        $this->assertSame(1, $json(['search' => 'Tata'])['total']);
        $this->assertSame(1, $json(['status' => 'pending'])['total']);
        $this->assertStringContainsString('Inovasi Daerah', $json(['status' => 'pending'])['html']);
    }

    public function test_tracking_detail_has_timeline_and_is_owner_only(): void
    {
        $m = $this->makeManuscript('ditinjau');

        $res = $this->actingAs($this->author)->getJson(route('author.manuscripts.show', $m))->assertOk();
        $states = collect($res->json('timeline'))->pluck('state', 'key');
        $this->assertSame('done', $states['submitted']);
        $this->assertSame('current', $states['review']);
        $this->assertSame('upcoming', $states['published']);

        $this->actingAs($this->makeUser('Author', 'o@t.test'))
            ->getJson(route('author.manuscripts.show', $m))->assertForbidden();
    }

    public function test_timeline_states_for_rejected_and_published(): void
    {
        $svc = app(AuthorService::class);

        $rejected = collect($svc->buildTimeline($this->makeManuscript('ditolak')))->pluck('state', 'key');
        $this->assertSame('rejected', $rejected['decision']);
        $this->assertSame('skipped', $rejected['published']);

        $published = collect($svc->buildTimeline($this->makeManuscript('diterbitkan')))->pluck('state', 'key');
        $this->assertSame(['done', 'done', 'skipped', 'done', 'done'], $published->values()->all());
    }

    public function test_file_route_requires_ownership_and_uses_private_disk(): void
    {
        Storage::disk('local')->put('manuscripts/x.pdf', 'isi');
        $m = $this->makeManuscript('pending');

        $this->actingAs($this->author)->get(route('files.manuscript', [$m, 'original']))->assertOk();
        $this->actingAs($this->author)->get(route('files.manuscript', [$m, 'revision']))->assertNotFound();
        $this->actingAs($this->makeUser('Author', 'o@t.test'))
            ->get(route('files.manuscript', [$m, 'original']))->assertForbidden();
    }

    // ---------------------------------------------------------------- Hasil Review & Revisi

    public function test_revision_queue_lists_only_revisi_status(): void
    {
        $this->makeManuscript('revisi', null, ['title' => 'Perlu Diperbaiki', 'editorial_note' => 'Perbaiki metode']);
        $this->makeManuscript('ditinjau');

        $this->actingAs($this->author)->get(route('author.revisions.index'))->assertOk()->assertSee('Unggah Revisi');
        $this->actingAs($this->author)->get(route('author.manuscripts.index', ['status' => 'revisi']))->assertOk()->assertSee('Naskah Saya');

        $res = $this->actingAs($this->author)->getJson(route('author.revisions.index'))->assertOk();
        $this->assertSame(1, $res->json('total'));
        $this->assertStringContainsString('Perbaiki metode', $res->json('html'));
    }

    public function test_revision_detail_shows_anonymous_reviewer_feedback(): void
    {
        $m = $this->makeManuscript('revisi', null, ['editorial_note' => 'Catatan editor', 'decided_at' => now()]);
        $reviewer = $this->makeUser('Reviewer');
        Review::create([
            'manuscript_id' => $m->id, 'reviewer_id' => $reviewer->id, 'assigned_by' => $this->makeUser('Editor')->id, 'status' => 'selesai',
            'recommendation' => 'revisi_minor', 'comments' => 'Perjelas metodologi', 'completed_at' => now(),
        ]);

        $res = $this->actingAs($this->author)->getJson(route('author.revisions.detail', $m))->assertOk();
        $this->assertSame('Perjelas metodologi', $res->json('reviews.0.comments'));
        $this->assertSame('Reviewer 1', $res->json('reviews.0.label'));
        $this->assertStringNotContainsString($reviewer->name, $res->getContent());

        $this->actingAs($this->author)
            ->getJson(route('author.revisions.detail', $this->makeManuscript('ditinjau')))->assertNotFound();
    }

    public function test_upload_revision_moves_manuscript_to_editor_decision_queue(): void
    {
        $m = $this->makeManuscript('revisi');

        $this->actingAs($this->author)->postJson(route('author.revisions.upload', $m), [
            'revision_file' => $this->docx('revisi.docx'),
            'author_response' => 'Metodologi sudah diperjelas pada bab 3.',
        ])->assertOk()->assertJson(['success' => true]);

        $m->refresh();
        $this->assertSame('menunggu_keputusan', $m->status);
        $this->assertTrue($m->isRevision());
        $this->assertSame('Metodologi sudah diperjelas pada bab 3.', $m->author_response);
        $this->assertNotNull($m->revision_submitted_at);
        Storage::disk('local')->assertExists($m->revision_file_path);

        // muncul di antrean Keputusan Editorial milik editor
        $this->actingAs($this->makeUser('Editor'))
            ->getJson(route('editor.decisions.index'))->assertOk()->assertJson(['total' => 1]);
    }

    public function test_second_revision_round_replaces_previous_file(): void
    {
        $m = $this->makeManuscript('revisi');
        $svc = app(AuthorService::class);

        $svc->submitRevision($m, $this->pdf('r1.pdf'), 'Tanggapan putaran pertama.');
        $first = $m->fresh()->revision_file_path;

        $m->update(['status' => 'revisi']); // editor meminta revisi lagi
        $svc->submitRevision($m->fresh(), $this->pdf('r2.pdf'), 'Tanggapan putaran kedua.');

        Storage::disk('local')->assertMissing($first);
        Storage::disk('local')->assertExists($m->fresh()->revision_file_path);
    }

    public function test_upload_revision_validation_and_guards(): void
    {
        $m = $this->makeManuscript('revisi');
        $this->actingAs($this->author);

        $this->postJson(route('author.revisions.upload', $m), [])
            ->assertUnprocessable()->assertJsonValidationErrors(['revision_file', 'author_response']);

        $this->postJson(route('author.revisions.upload', $m), [
            'revision_file' => UploadedFile::fake()->create('r.exe', 10), 'author_response' => 'Sudah diperbaiki semua.',
        ])->assertUnprocessable()->assertJsonValidationErrors('revision_file');

        // bukan pemilik -> 403
        $this->actingAs($this->makeUser('Author', 'o@t.test'))->postJson(route('author.revisions.upload', $m), [
            'revision_file' => $this->pdf('r.pdf'), 'author_response' => 'Sudah diperbaiki semua.',
        ])->assertForbidden();

        // status bukan 'revisi' -> 422 dari service
        $other = $this->makeManuscript('ditinjau');
        $this->actingAs($this->author)->postJson(route('author.revisions.upload', $other), [
            'revision_file' => $this->pdf('r.pdf'), 'author_response' => 'Sudah diperbaiki semua.',
        ])->assertUnprocessable()->assertJson(['success' => false]);

        $this->assertSame('revisi', $m->fresh()->status);
    }
}
