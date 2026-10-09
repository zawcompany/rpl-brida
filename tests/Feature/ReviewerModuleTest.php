<?php

namespace Tests\Feature;

use App\Models\Manuscript;
use App\Models\ResearchField;
use App\Models\Review;
use App\Models\User;
use App\Services\ReviewerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\MakesDocuments;
use Tests\TestCase;

class ReviewerModuleTest extends TestCase
{
    use RefreshDatabase, MakesDocuments;

    private User $reviewer;
    private User $editor;
    private User $author;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $this->reviewer = $this->user('Reviewer', 'rev@t.test');
        $this->editor = $this->user('Editor', 'ed@t.test');
        $this->author = $this->user('Author', 'au@t.test');
    }

    private function user(string $role, string $email): User
    {
        return User::create(['name' => "Tes {$role}", 'email' => $email, 'password' => 'password', 'role' => $role]);
    }

    private function assignment(string $status = 'ditugaskan', ?User $reviewer = null, array $review = [], string $msStatus = 'ditinjau'): Review
    {
        $m = Manuscript::create([
            'author_id' => $this->author->id, 'title' => 'Naskah ' . $status, 'abstract' => 'Abstrak', 'keywords' => 'k',
            'status' => $msStatus, 'file_path' => 'manuscripts/a.pdf', 'file_original_name' => 'a.pdf', 'submitted_at' => now(),
            'research_field_id' => ResearchField::firstOrCreate(['slug' => 'x'], ['name' => 'Bidang X'])->id,
        ]);

        return Review::create($review + [
            'manuscript_id' => $m->id, 'reviewer_id' => ($reviewer ?? $this->reviewer)->id, 'assigned_by' => $this->editor->id,
            'status' => $status, 'due_at' => now()->addDays(10),
        ]);
    }

    private function payload(array $override = []): array
    {
        return $override + [
            'relevansi_topik' => 'Baik', 'metodologi_penelitian' => 'Sangat Baik', 'kebaruan' => 'Cukup', 'kualitas_penulisan' => 'Baik',
            'comments' => 'Metodologi perlu dijelaskan lebih rinci pada bab 3.', 'recommendation' => 'revisi_minor',
        ];
    }

    // ------------------------------------------------------------ akses & halaman

    public function test_only_reviewer_role_can_access(): void
    {
        $this->get(route('reviewer.manuscripts.index'))->assertRedirect(route('login'));

        foreach ([$this->author, $this->editor] as $other) {
            $this->actingAs($other)->get(route('reviewer.manuscripts.index'))->assertForbidden();
        }

        $this->actingAs($this->reviewer)->get(route('reviewer.manuscripts.index'))->assertOk()->assertSee('Naskah Ditugaskan');
        $this->get(route('reviewer.manuscripts-selesai'))->assertOk()->assertSee('Naskah Selesai Direview');
    }

    public function test_dashboard_shows_widgets_and_recent_table(): void
    {
        $this->assignment('ditugaskan');
        $this->assignment('diterima', null, ['due_at' => now()->addDay()]);   // mendekati deadline
        $this->assignment('selesai', null, ['recommendation' => 'diterima'], 'menunggu_keputusan');
        $this->assignment('ditugaskan', $this->user('Reviewer', 'other@t.test')); // milik reviewer lain
        $this->assignment('ditugaskan', null, ['superseded_at' => now()]);          // sudah digantikan

        $stats = collect(app(ReviewerService::class)->getDashboardStats($this->reviewer))->pluck('count', 'label');
        $this->assertSame(3, $stats['Total Naskah Ditugaskan']);
        $this->assertSame(2, $stats['Belum Direview']);
        $this->assertSame(1, $stats['Selesai Direview']);
        $this->assertSame(1, $stats['Mendekati Deadline']);

        $this->actingAs($this->reviewer)->get('/dashboard')
            ->assertOk()->assertSee('Aktivitas / Riwayat Review Terbaru')->assertSee('Lanjutkan Review');
    }

    public function test_assignment_table_search_status_filter_and_isolation(): void
    {
        $this->assignment('ditugaskan');
        $this->assignment('selesai', null, [], 'menunggu_keputusan');
        $this->assignment('ditugaskan', $this->user('Reviewer', 'other@t.test'));
        $this->actingAs($this->reviewer);

        $json = fn (string $route, array $q = []) => $this->getJson(route($route, $q))->assertOk()->json();

        $this->assertSame(2, $json('reviewer.manuscripts.index')['total']);
        $this->assertSame(1, $json('reviewer.manuscripts.index', ['status' => 'selesai'])['total']);
        $this->assertSame(1, $json('reviewer.manuscripts.index', ['search' => 'selesai'])['total']);
        $this->assertSame(1, $json('reviewer.manuscripts-selesai')['total']);                      // dipaksa 'selesai'
        $this->assertSame(0, $json('reviewer.manuscripts-selesai', ['status' => 'ditugaskan'])['total']); // filter tidak bisa menembus batas 'selesai'
    }

    // ------------------------------------------------------------ detail (anonim) & IDOR

    public function test_detail_is_anonymous_and_owner_only(): void
    {
        $r = $this->assignment();

        $res = $this->actingAs($this->reviewer)->getJson(route('reviewer.reviews.detail', $r))->assertOk();
        $this->assertSame('Naskah ditugaskan', $res->json('review.manuscript.title'));
        $this->assertTrue($res->json('review.can.respond'));
        $this->assertStringNotContainsString($this->author->name, $res->getContent());
        $this->assertStringNotContainsString($this->author->email, $res->getContent());

        $this->actingAs($this->user('Reviewer', 'o@t.test'))->getJson(route('reviewer.reviews.detail', $r))->assertForbidden();
        $this->actingAs($this->author)->getJson(route('reviewer.reviews.detail', $r))->assertForbidden();
    }

    // ------------------------------------------------------------ terima / tolak

    public function test_accept_assignment(): void
    {
        $r = $this->assignment();
        $this->actingAs($this->reviewer)->postJson(route('reviewer.reviews.accept', $r))->assertOk();
        $this->assertSame('diterima', $r->fresh()->status);

        $this->postJson(route('reviewer.reviews.accept', $r))->assertUnprocessable(); // sudah direspon
    }

    public function test_decline_requires_reason_notifies_editor_and_makes_review_replaceable(): void
    {
        $r = $this->assignment();
        $this->actingAs($this->reviewer);

        $this->postJson(route('reviewer.reviews.decline', $r), [])->assertUnprocessable()->assertJsonValidationErrors('reason');
        $this->postJson(route('reviewer.reviews.decline', $r), ['reason' => 'Bukan bidang saya.'])->assertOk();

        $r->refresh();
        $this->assertSame('ditolak_reviewer', $r->status);
        $this->assertTrue($r->isReplaceable());
        $this->assertContains('review.declined', $this->editor->notifications()->get()->pluck('data.type')->all());
    }

    public function test_other_reviewer_cannot_act_on_assignment(): void
    {
        $r = $this->assignment();
        $this->actingAs($this->user('Reviewer', 'o@t.test'));

        $this->postJson(route('reviewer.reviews.accept', $r))->assertForbidden();
        $this->postJson(route('reviewer.reviews.decline', $r), ['reason' => 'Tidak boleh'])->assertForbidden();
        $this->postJson(route('reviewer.reviews.submit', $r), $this->payload())->assertForbidden();
        $this->assertSame('ditugaskan', $r->fresh()->status);
    }

    // ------------------------------------------------------------ kirim review

    public function test_submit_review_saves_result_moves_manuscript_notifies_editor_and_logs(): void
    {
        $r = $this->assignment('diterima');

        $this->actingAs($this->reviewer)->post(route('reviewer.reviews.submit', $r), $this->payload([
            'review_file' => $this->pdf('catatan.pdf'),
        ]), ['Accept' => 'application/json'])->assertOk()->assertJson(['success' => true]);

        $r->refresh();
        $this->assertSame('selesai', $r->status);
        $this->assertSame('revisi_minor', $r->recommendation);
        $this->assertSame('Sangat Baik', $r->metodologi_penelitian);
        $this->assertSame(3.0, $r->score_average); // (3+4+2+3)/4 = 3.0
        $this->assertNotNull($r->completed_at);
        Storage::disk('local')->assertExists($r->review_file_path);
        $this->assertSame('menunggu_keputusan', $r->manuscript->fresh()->status);

        $this->assertContains('review.completed', $this->editor->notifications()->get()->pluck('data.type')->all());
        $this->assertDatabaseHas('activity_logs', ['action' => 'review.submitted', 'user_id' => $this->reviewer->id]);

        // muncul di antrean Keputusan Editorial
        $this->actingAs($this->editor)->getJson(route('editor.decisions.index'))->assertOk()->assertJson(['total' => 1]);
    }

    public function test_submit_review_validation(): void
    {
        $r = $this->assignment();
        $this->actingAs($this->reviewer);

        $this->postJson(route('reviewer.reviews.submit', $r), [])->assertUnprocessable()
            ->assertJsonValidationErrors(['relevansi_topik', 'metodologi_penelitian', 'kebaruan', 'kualitas_penulisan', 'comments', 'recommendation']);
        $this->postJson(route('reviewer.reviews.submit', $r), $this->payload(['kebaruan' => 'Luar Biasa']))->assertJsonValidationErrors('kebaruan');
        $this->postJson(route('reviewer.reviews.submit', $r), $this->payload(['recommendation' => 'lulus']))->assertJsonValidationErrors('recommendation');
        $this->postJson(route('reviewer.reviews.submit', $r), $this->payload(['comments' => 'pendek']))->assertJsonValidationErrors('comments');
        $this->postJson(route('reviewer.reviews.submit', $r), $this->payload([
            'review_file' => \Illuminate\Http\UploadedFile::fake()->createWithContent('x.pdf', '<?php echo 1;'),
        ]))->assertJsonValidationErrors('review_file');

        $this->assertSame('ditugaskan', $r->fresh()->status);
    }

    public function test_cannot_submit_twice_or_after_being_replaced(): void
    {
        $done = $this->assignment('selesai', null, [], 'menunggu_keputusan');
        $replaced = $this->assignment('ditugaskan', null, ['superseded_at' => now()]);
        $this->actingAs($this->reviewer);

        $this->postJson(route('reviewer.reviews.submit', $done), $this->payload())->assertUnprocessable();
        $this->postJson(route('reviewer.reviews.submit', $replaced), $this->payload())->assertUnprocessable();
    }

    // ------------------------------------------------------------ ubah review

    public function test_reviewer_can_update_review_until_editor_decides(): void
    {
        $r = $this->assignment('selesai', null, ['recommendation' => 'diterima', 'comments' => 'Awal'], 'menunggu_keputusan');
        $this->actingAs($this->reviewer);

        $this->putJson(route('reviewer.reviews.update', $r), $this->payload(['recommendation' => 'revisi_mayor']))->assertOk();
        $this->assertSame('revisi_mayor', $r->fresh()->recommendation);
        $this->assertDatabaseHas('activity_logs', ['action' => 'review.updated']);

        // editor sudah memutuskan -> terkunci
        $r->manuscript->update(['status' => 'revisi']);
        $this->putJson(route('reviewer.reviews.update', $r), $this->payload())->assertUnprocessable();
    }

    // ------------------------------------------------------------ lampiran review & berkas naskah

    public function test_review_attachment_is_visible_only_to_owner_and_editor(): void
    {
        Storage::disk('local')->put('reviews/1/catatan.pdf', '%PDF-1.4');
        $r = $this->assignment('selesai', null, ['review_file_path' => 'reviews/1/catatan.pdf', 'review_file_name' => 'catatan.pdf'], 'menunggu_keputusan');

        foreach ([$this->reviewer, $this->editor] as $allowed) {
            $this->actingAs($allowed)->get(route('files.review', $r))->assertOk();
        }
        foreach ([$this->author, $this->user('Reviewer', 'o@t.test'), $this->user('Administrator', 'a@t.test')] as $denied) {
            $this->actingAs($denied)->get(route('files.review', $r))->assertForbidden();
        }
    }

    public function test_declined_or_replaced_reviewer_loses_access_to_manuscript_file(): void
    {
        Storage::disk('local')->put('manuscripts/a.pdf', '%PDF-1.4');
        $active = $this->assignment();
        $url = route('files.manuscript', [$active->manuscript, 'original']);

        $this->actingAs($this->reviewer)->get($url)->assertOk();

        $active->update(['status' => 'ditolak_reviewer']);
        $this->actingAs($this->reviewer)->get($url)->assertForbidden();
    }

    public function test_editor_sees_scores_and_attachment_in_decision_detail(): void
    {
        $r = $this->assignment('selesai', null, $this->payload() + ['review_file_path' => 'reviews/1/c.pdf', 'review_file_name' => 'c.pdf'], 'menunggu_keputusan');

        $res = $this->actingAs($this->editor)->getJson(route('editor.decisions.detail', $r->manuscript))->assertOk();

        $this->assertEquals(3.0, $res->json('reviews.0.score_average'));
        $this->assertSame('Baik', $res->json('reviews.0.scores.0.value'));
        $this->assertSame(route('files.review', $r->id), $res->json('reviews.0.file_url'));
    }
}
