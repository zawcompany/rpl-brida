<?php

namespace Tests\Feature;

use App\Models\Issue;
use App\Models\Manuscript;
use App\Models\ResearchField;
use App\Models\Review;
use App\Models\User;
use App\Notifications\ReviewReminderNotification;
use App\Services\EditorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\MakesDocuments;
use Tests\TestCase;

class EditorModuleTest extends TestCase
{
    use RefreshDatabase, MakesDocuments;

    private User $editor;
    private User $author;
    private ResearchField $fieldA;
    private ResearchField $fieldB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->editor = $this->makeUser('Editor');
        $this->author = $this->makeUser('Author');
        $this->fieldA = ResearchField::create(['name' => 'Ilmu Komputer', 'slug' => 'ilmu-komputer']);
        $this->fieldB = ResearchField::create(['name' => 'Keamanan Siber', 'slug' => 'keamanan-siber']);
    }

    // ---------------------------------------------------------------- helpers

    private function makeUser(string $role, ?ResearchField $field = null): User
    {
        $user = User::create([
            'name'     => $role . ' ' . uniqid(),
            'email'    => uniqid() . '@example.com',
            'password' => 'password123',
            'role'     => $role,
        ]);

        $field && $user->researchFields()->attach($field->id);

        return $user;
    }

    private function makeManuscript(string $status = 'pending', ?ResearchField $field = null, array $attrs = []): Manuscript
    {
        return Manuscript::create($attrs + [
            'author_id'         => $this->author->id,
            'research_field_id' => ($field ?? $this->fieldA)->id,
            'title'             => 'Naskah ' . uniqid(),
            'status'            => $status,
        ]);
    }

    private function makeReview(Manuscript $m, User $reviewer, string $status = 'diterima', array $attrs = []): Review
    {
        return Review::create($attrs + [
            'manuscript_id' => $m->id,
            'reviewer_id'   => $reviewer->id,
            'assigned_by'   => $this->editor->id,
            'status'        => $status,
            'due_at'        => now()->addDays(7),
        ]);
    }

    // ----------------------------------------------------------------- akses

    public function test_hanya_editor_yang_boleh_mengakses_modul(): void
    {
        $this->get('/editor/naskah-baru')->assertRedirect('/login');

        $this->actingAs($this->author)->get('/editor/naskah-baru')->assertForbidden();
        $this->actingAs($this->author)->get('/editor/edisi')->assertForbidden();

        $this->actingAs($this->editor)->get('/editor/naskah-baru')->assertOk();
    }

    public function test_semua_halaman_editor_dapat_dirender(): void
    {
        $this->actingAs($this->editor);

        foreach (['dashboard', 'naskah-baru', 'peninjauan', 'keputusan', 'edisi', 'reviewer'] as $path) {
            $this->get("/editor/{$path}")->assertOk();
        }
    }

    // ------------------------------------------------- rekomendasi 1 reviewer

    public function test_rekomendasi_adalah_satu_reviewer_dengan_bidang_cocok_dan_beban_paling_sedikit(): void
    {
        $sibuk   = $this->makeUser('Reviewer', $this->fieldA);
        $longgar = $this->makeUser('Reviewer', $this->fieldA);
        $lain    = $this->makeUser('Reviewer', $this->fieldB); // beban 0 tapi bidang beda

        $this->makeReview($this->makeManuscript('ditinjau'), $sibuk);
        $this->makeReview($this->makeManuscript('ditinjau'), $sibuk);
        $this->makeReview($this->makeManuscript('ditinjau'), $longgar);

        $best = app(EditorService::class)->getBestReviewer($this->makeManuscript('pending', $this->fieldA));

        $this->assertSame($longgar->id, $best['id']);
        $this->assertSame(1, $best['active_load']);
    }

    public function test_beban_aktif_mengabaikan_review_selesai_dan_yang_sudah_diganti(): void
    {
        $a = $this->makeUser('Reviewer', $this->fieldA);
        $b = $this->makeUser('Reviewer', $this->fieldA);

        // A: 1 aktif + 3 selesai/digantikan; B: 2 aktif
        $this->makeReview($this->makeManuscript('ditinjau'), $a);
        foreach (range(1, 2) as $_) {
            $this->makeReview($this->makeManuscript('menunggu_keputusan'), $a, 'selesai');
        }
        $this->makeReview($this->makeManuscript('ditinjau'), $a, 'diterima', ['superseded_at' => now()]);
        $this->makeReview($this->makeManuscript('ditinjau'), $b);
        $this->makeReview($this->makeManuscript('ditinjau'), $b);

        $best = app(EditorService::class)->getBestReviewer($this->makeManuscript('pending'));

        $this->assertSame($a->id, $best['id']);
        $this->assertSame(1, $best['active_load']);
    }

    public function test_tidak_ada_rekomendasi_bila_tidak_ada_bidang_yang_cocok(): void
    {
        $this->makeUser('Reviewer', $this->fieldB);

        $this->assertNull(app(EditorService::class)->getBestReviewer($this->makeManuscript('pending', $this->fieldA)));
    }

    public function test_endpoint_detail_mengembalikan_tepat_satu_rekomendasi(): void
    {
        $this->makeUser('Reviewer', $this->fieldA);
        $this->makeUser('Reviewer', $this->fieldA);
        $m = $this->makeManuscript('pending');

        $json = $this->actingAs($this->editor)->getJson("/editor/naskah/{$m->id}/detail")->assertOk()->json();

        $this->assertIsArray($json['recommendation']);
        $this->assertArrayHasKey('id', $json['recommendation']);
        $this->assertCount(2, $json['reviewers']);
    }

    // ------------------------------------------------------- naskah baru

    public function test_lanjut_ke_review_membuat_penugasan_dan_mengubah_status(): void
    {
        $reviewer = $this->makeUser('Reviewer', $this->fieldA);
        $m = $this->makeManuscript('pending');

        $this->actingAs($this->editor)
            ->postJson("/editor/naskah/{$m->id}/assign", [
                'decision' => 'ditinjau', 'reviewer_id' => $reviewer->id, 'editor_note' => 'Mohon segera.',
            ])->assertOk()->assertJson(['success' => true]);

        $this->assertSame('ditinjau', $m->fresh()->status);
        $this->assertDatabaseHas('reviews', [
            'manuscript_id' => $m->id, 'reviewer_id' => $reviewer->id, 'status' => 'ditugaskan', 'assigned_by' => $this->editor->id,
        ]);
    }

    public function test_lanjut_ke_review_tanpa_reviewer_ditolak_validasi(): void
    {
        $m = $this->makeManuscript('pending');

        $this->actingAs($this->editor)
            ->postJson("/editor/naskah/{$m->id}/assign", ['decision' => 'ditinjau'])
            ->assertStatus(422)->assertJsonValidationErrors('reviewer_id');
    }

    public function test_tolak_di_tempat_dan_tidak_bisa_diproses_dua_kali(): void
    {
        $m = $this->makeManuscript('pending');

        $this->actingAs($this->editor)
            ->postJson("/editor/naskah/{$m->id}/assign", ['decision' => 'ditolak'])->assertOk();
        $this->assertSame('ditolak', $m->fresh()->status);

        $this->actingAs($this->editor)
            ->postJson("/editor/naskah/{$m->id}/assign", ['decision' => 'ditolak'])->assertStatus(422);
    }

    public function test_pencarian_dan_filter_tanggal_naskah_baru_via_ajax(): void
    {
        $this->makeManuscript('pending', null, ['title' => 'Kecerdasan Buatan Medis']);
        $this->makeManuscript('pending', null, ['title' => 'Jaringan Saraf Tiruan']);

        $json = $this->actingAs($this->editor)
            ->getJson('/editor/naskah-baru?search=Medis&date=' . now()->toDateString())
            ->assertOk()->json();

        $this->assertSame(1, $json['total']);
        $this->assertStringContainsString('Kecerdasan Buatan Medis', $json['html']);
        $this->assertStringNotContainsString('Jaringan Saraf', $json['html']);
    }

    // ---------------------------------------------------------- peninjauan

    public function test_pengingat_dikirim_dan_dibatasi_cooldown(): void
    {
        Notification::fake();
        $reviewer = $this->makeUser('Reviewer', $this->fieldA);
        $m = $this->makeManuscript('ditinjau');
        $this->makeReview($m, $reviewer);

        $this->actingAs($this->editor)->postJson("/editor/peninjauan/{$m->id}/pengingat")->assertOk();
        Notification::assertSentTo($reviewer, ReviewReminderNotification::class);

        $review = $m->currentReview()->first();
        $this->assertSame(1, $review->reminder_count);

        $this->actingAs($this->editor)->postJson("/editor/peninjauan/{$m->id}/pengingat")->assertStatus(422);
    }

    public function test_reviewer_hanya_bisa_diganti_bila_menolak_atau_terlambat(): void
    {
        $lama = $this->makeUser('Reviewer', $this->fieldA);
        $baru = $this->makeUser('Reviewer', $this->fieldA);
        $m = $this->makeManuscript('ditinjau');
        $review = $this->makeReview($m, $lama); // masih berjalan & belum terlambat

        $this->actingAs($this->editor)
            ->postJson("/editor/peninjauan/{$m->id}/ganti-reviewer", ['reviewer_id' => $baru->id])
            ->assertStatus(422);

        $review->update(['due_at' => now()->subDay()]); // terlambat

        $this->actingAs($this->editor)
            ->postJson("/editor/peninjauan/{$m->id}/ganti-reviewer", ['reviewer_id' => $baru->id])
            ->assertOk();

        $this->assertNotNull($review->fresh()->superseded_at);
        $this->assertSame($baru->id, $m->currentReview()->first()->reviewer_id);
        $this->assertSame(0, $lama->activeReviewCount());
        $this->assertSame(1, $baru->activeReviewCount());
    }

    public function test_reviewer_yang_menolak_dapat_diganti_dan_detail_menawarkan_rekomendasi_baru(): void
    {
        $lama = $this->makeUser('Reviewer', $this->fieldA);
        $baru = $this->makeUser('Reviewer', $this->fieldA);
        $m = $this->makeManuscript('ditinjau');
        $this->makeReview($m, $lama, 'ditolak_reviewer');

        $json = $this->actingAs($this->editor)->getJson("/editor/peninjauan/{$m->id}/detail")->assertOk()->json();

        $this->assertTrue($json['review']['can_replace']);
        $this->assertSame($baru->id, $json['recommendation']['id']); // reviewer lama dikecualikan
    }

    // ---------------------------------------------------- keputusan editorial

    public function test_keputusan_akhir_memetakan_status_dengan_benar(): void
    {
        $cases = ['diterima' => 'disetujui', 'revisi' => 'revisi', 'ditolak' => 'ditolak'];

        foreach ($cases as $decision => $status) {
            $m = $this->makeManuscript('menunggu_keputusan');

            $this->actingAs($this->editor)
                ->postJson("/editor/keputusan/{$m->id}", ['decision' => $decision, 'editorial_note' => 'Catatan editor'])
                ->assertOk();

            $m->refresh();
            $this->assertSame($status, $m->status);
            $this->assertSame('Catatan editor', $m->editorial_note);
            $this->assertNotNull($m->decided_at);
        }
    }

    public function test_revisi_dan_penolakan_wajib_catatan_tapi_penerimaan_tidak(): void
    {
        $m = $this->makeManuscript('menunggu_keputusan');

        $this->actingAs($this->editor)->postJson("/editor/keputusan/{$m->id}", ['decision' => 'revisi'])
            ->assertStatus(422)->assertJsonValidationErrors('editorial_note');

        $this->actingAs($this->editor)->postJson("/editor/keputusan/{$m->id}", ['decision' => 'diterima'])
            ->assertOk();
    }

    public function test_keputusan_hanya_untuk_naskah_menunggu_keputusan(): void
    {
        $m = $this->makeManuscript('ditinjau');

        $this->actingAs($this->editor)
            ->postJson("/editor/keputusan/{$m->id}", ['decision' => 'diterima'])->assertStatus(422);
    }

    public function test_detail_keputusan_memuat_rekap_review_dan_berkas_revisi(): void
    {
        $reviewer = $this->makeUser('Reviewer', $this->fieldA);
        $m = $this->makeManuscript('menunggu_keputusan', null, ['revision_file_path' => 'revisi/a.pdf']);
        $this->makeReview($m, $reviewer, 'selesai', ['recommendation' => 'revisi_minor', 'comments' => 'Perbaiki bab 3']);

        $json = $this->actingAs($this->editor)->getJson("/editor/keputusan/{$m->id}/detail")->assertOk()->json();

        $this->assertCount(1, $json['reviews']);
        $this->assertSame('Revisi Minor', $json['reviews'][0]['recommendation_label']);
        $this->assertSame('Perbaiki bab 3', $json['reviews'][0]['comments']);
        $this->assertNotNull($json['manuscript']['revision_file_url']);
    }

    // ----------------------------------------------------- edisi & publikasi

    public function test_alur_edisi_dari_pembuatan_hingga_publikasi(): void
    {
        Storage::fake('local');
        $this->actingAs($this->editor);

        $this->post('/editor/edisi', ['volume' => 1, 'number' => 2, 'year' => 2026, 'title' => 'Edisi Khusus'])
            ->assertSessionHasNoErrors();
        $issue = Issue::firstOrFail();
        $this->assertSame('draft', $issue->status);

        $this->post('/editor/edisi', ['volume' => 1, 'number' => 2, 'year' => 2026])
            ->assertSessionHasErrors('number'); // duplikat

        $approved = $this->makeManuscript('disetujui');
        $pending  = $this->makeManuscript('pending');

        // naskah belum disetujui tidak boleh ditambahkan
        $this->post("/editor/edisi/{$issue->id}/naskah", [
            'manuscript_id' => $pending->id,
            'final_file'    => $this->pdf('final.pdf'),
        ])->assertSessionHas('error');
        $this->assertNull($pending->fresh()->issue_id);

        // publikasi edisi kosong ditolak
        $this->post("/editor/edisi/{$issue->id}/publikasi")->assertSessionHas('error');
        $this->assertSame('draft', $issue->fresh()->status);

        // file bukan PDF ditolak
        $this->post("/editor/edisi/{$issue->id}/naskah", [
            'manuscript_id' => $approved->id,
            'final_file'    => UploadedFile::fake()->create('final.docx', 100, 'application/msword'),
        ])->assertSessionHasErrors('final_file');

        $this->post("/editor/edisi/{$issue->id}/naskah", [
            'manuscript_id' => $approved->id,
            'final_file'    => $this->pdf('final.pdf'),
        ])->assertSessionHas('success');

        $approved->refresh();
        $this->assertSame($issue->id, $approved->issue_id);
        Storage::disk('local')->assertExists($approved->final_file_path);

        $this->post("/editor/edisi/{$issue->id}/publikasi")->assertSessionHas('success');

        $this->assertSame('published', $issue->fresh()->status);
        $this->assertSame('diterbitkan', $approved->fresh()->status);
        $this->assertNotNull($approved->fresh()->published_at);

        // edisi terbit terkunci
        $this->delete("/editor/edisi/{$issue->id}/naskah/{$approved->id}")->assertSessionHas('error');
    }

    public function test_publikasi_ditolak_bila_ada_naskah_tanpa_file_final(): void
    {
        $issue = Issue::create(['volume' => 1, 'number' => 1, 'year' => 2026]);
        $m = $this->makeManuscript('disetujui', null, ['issue_id' => $issue->id]);

        $this->actingAs($this->editor)->post("/editor/edisi/{$issue->id}/publikasi")->assertSessionHas('error');

        $this->assertSame('draft', $issue->fresh()->status);
        $this->assertSame('disetujui', $m->fresh()->status);
    }

    // --------------------------------------------------- direktori reviewer

    public function test_direktori_reviewer_menampilkan_beban_status_dan_filter_bidang(): void
    {
        $a = $this->makeUser('Reviewer', $this->fieldA);
        $b = $this->makeUser('Reviewer', $this->fieldB);

        foreach (range(1, User::MAX_ACTIVE_REVIEWS) as $_) {
            $this->makeReview($this->makeManuscript('ditinjau'), $a);
        }

        $json = $this->actingAs($this->editor)
            ->getJson("/editor/reviewer?field={$this->fieldA->id}")->assertOk()->json();

        $this->assertSame(1, $json['total']);
        $this->assertStringContainsString($a->name, $json['html']);
        $this->assertStringContainsString('Sibuk', $json['html']);
        $this->assertStringNotContainsString($b->name, $json['html']);

        $profile = $this->getJson("/editor/reviewer/{$a->id}/profil")->assertOk()->json();
        $this->assertTrue($profile['is_busy']);
        $this->assertSame(User::MAX_ACTIVE_REVIEWS, $profile['active']);
        $this->assertCount(User::MAX_ACTIVE_REVIEWS, $profile['history']);

        $this->getJson("/editor/reviewer/{$this->author->id}/profil")->assertNotFound();
    }

    // -------------------------------------------- tenggat & review ulang

    public function test_tenggat_default_14_hari_dan_dapat_diubah_manual(): void
    {
        $reviewer = $this->makeUser('Reviewer', $this->fieldA);

        $default = $this->makeManuscript('pending');
        $this->actingAs($this->editor)->postJson("/editor/naskah/{$default->id}/assign", [
            'decision' => 'ditinjau', 'reviewer_id' => $reviewer->id,
        ])->assertOk();
        $this->assertTrue($default->currentReview()->first()->due_at->isSameDay(now()->addDays(14)));

        $custom = $this->makeManuscript('pending');
        $due = now()->addDays(30)->toDateString();
        $this->actingAs($this->editor)->postJson("/editor/naskah/{$custom->id}/assign", [
            'decision' => 'ditinjau', 'reviewer_id' => $reviewer->id, 'due_at' => $due,
        ])->assertOk();
        $this->assertSame($due, $custom->currentReview()->first()->due_at->toDateString());

        $past = $this->makeManuscript('pending');
        $this->actingAs($this->editor)->postJson("/editor/naskah/{$past->id}/assign", [
            'decision' => 'ditinjau', 'reviewer_id' => $reviewer->id, 'due_at' => now()->subDay()->toDateString(),
        ])->assertStatus(422)->assertJsonValidationErrors('due_at');

        $this->actingAs($this->editor)->getJson("/editor/naskah/{$this->makeManuscript('pending')->id}/detail")
            ->assertJsonPath('default_due_at', now()->addDays(14)->toDateString());
    }

    public function test_naskah_revisi_dapat_dikirim_review_ulang_dengan_riwayat_tetap(): void
    {
        $reviewer = $this->makeUser('Reviewer', $this->fieldA);
        $m = $this->makeManuscript('menunggu_keputusan', null, ['revision_file_path' => 'revisi/a.pdf']);
        $old = $this->makeReview($m, $reviewer, 'selesai', ['recommendation' => 'revisi_mayor']);

        $this->actingAs($this->editor)->postJson("/editor/keputusan/{$m->id}/review-ulang", [
            'reviewer_id' => $reviewer->id, 'due_at' => now()->addDays(10)->toDateString(),
        ])->assertOk();

        $this->assertSame('ditinjau', $m->fresh()->status);
        $this->assertSame('selesai', $old->fresh()->status);
        $this->assertNull($old->fresh()->superseded_at);
        $this->assertSame(2, $m->reviews()->count());
        $this->assertSame('ditugaskan', $m->currentReview()->first()->status);
    }

    public function test_review_ulang_hanya_untuk_naskah_revisi(): void
    {
        $reviewer = $this->makeUser('Reviewer', $this->fieldA);
        $m = $this->makeManuscript('menunggu_keputusan'); // bukan revisi

        $this->actingAs($this->editor)->postJson("/editor/keputusan/{$m->id}/review-ulang", [
            'reviewer_id' => $reviewer->id,
        ])->assertStatus(422);
    }
}
