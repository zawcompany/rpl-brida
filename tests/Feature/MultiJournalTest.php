<?php

namespace Tests\Feature;

use App\Models\Issue;
use App\Models\Journal;
use App\Models\Manuscript;
use App\Models\ResearchField;
use App\Models\Review;
use App\Models\User;
use App\Services\AuthorService;
use App\Services\EditorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\MakesDocuments;
use Tests\TestCase;

/**
 * Platform multijurnal: kelompok (keanggotaan) per jurnal, template per jurnal yang hanya untuk anggota,
 * dan pembatasan editor/reviewer/edisi ke jurnalnya. Keanggotaan diatur manual di tes ini.
 */
class MultiJournalTest extends TestCase
{
    use RefreshDatabase, MakesDocuments;

    protected bool $autoJoinDefaultJournal = false;

    private Journal $a;   // jurnal bawaan (dibuat migrasi)
    private Journal $b;
    private ResearchField $field;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->a = Journal::default();
        $this->b = Journal::create(['name' => 'Jurnal Teknologi', 'abbreviation' => 'JT']);
        $this->field = ResearchField::create(['name' => 'Ilmu Komputer', 'slug' => 'ilmu-komputer']);
    }

    // ---------------------------------------------------------------- helpers

    /** @param Journal[] $journals jurnal tempat user menjadi anggota dengan peran yang sama seperti akunnya */
    private function user(string $role, array $journals = []): User
    {
        $user = User::create([
            'name'     => $role . ' ' . uniqid(),
            'email'    => uniqid() . '@example.com',
            'password' => 'password123',
            'role'     => $role,
        ]);

        foreach ($journals as $journal) {
            $user->joinJournal($journal, $role);
        }

        return $user;
    }

    private function manuscript(Journal $journal, User $author, string $status = 'pending', array $attrs = []): Manuscript
    {
        return Manuscript::create($attrs + [
            'journal_id'        => $journal->id,
            'author_id'         => $author->id,
            'research_field_id' => $this->field->id,
            'title'             => 'Naskah ' . uniqid(),
            'status'            => $status,
        ]);
    }

    // ---------------------------------------------------------------- data dasar

    public function test_migration_creates_default_journal_with_bundled_template(): void
    {
        $this->assertSame('simpil-brida', $this->a->slug);
        $this->assertTrue($this->a->hasTemplate());          // template bawaan aplikasi
        $this->assertFalse($this->b->hasTemplate());         // jurnal baru belum punya
        $this->assertSame($this->a->id, Journal::defaultId());
    }

    public function test_manuscript_and_issue_without_journal_fall_back_to_default(): void
    {
        $m = Manuscript::create(['author_id' => $this->user('Author')->id, 'title' => 'T', 'status' => 'pending']);
        $i = Issue::create(['volume' => 1, 'number' => 1, 'year' => 2026, 'status' => 'draft']);

        $this->assertSame($this->a->id, $m->journal_id);
        $this->assertSame($this->a->id, $i->journal_id);
    }

    // ---------------------------------------------------------------- halaman publik & template

    public function test_guest_can_browse_journals_but_template_requires_login(): void
    {
        $this->get(route('journals.index'))->assertOk()
            ->assertSee($this->a->name)->assertSee('Jurnal Teknologi');

        $this->get(route('journals.show', $this->b))->assertOk()->assertSee('Jurnal Teknologi');

        $this->get(route('journals.template', $this->a))->assertRedirect(route('login'));
    }

    public function test_inactive_journal_is_hidden_from_list_and_page(): void
    {
        $this->b->update(['is_active' => false]);

        $this->get(route('journals.index'))->assertDontSee('Jurnal Teknologi');
        $this->get(route('journals.show', $this->b))->assertNotFound();
    }

    public function test_author_must_join_journal_before_downloading_its_template(): void
    {
        $author = $this->user('Author');

        // belum anggota: ditolak dan diarahkan ke halaman jurnal untuk bergabung
        $this->actingAs($author)->get(route('journals.template', $this->a))
            ->assertRedirect(route('journals.show', $this->a))->assertSessionHas('error');

        // bergabung -> bisa mengunduh
        $this->post(route('journals.join', $this->a))->assertRedirect(route('journals.show', $this->a))->assertSessionHas('success');
        $this->assertTrue($author->isMemberOf($this->a, Journal::AUTHOR));

        $this->get(route('journals.template', $this->a))->assertOk()->assertDownload('Template-simpil-brida.docx');

        // keanggotaan satu jurnal tidak membuka jurnal lain
        $this->get(route('journals.template', $this->b))->assertRedirect(route('journals.show', $this->b));
        $this->assertDatabaseHas('activity_logs', ['action' => 'journal.joined']);
        $this->assertDatabaseHas('activity_logs', ['action' => 'template.downloaded']);
    }

    public function test_each_journal_has_its_own_versioned_template(): void
    {
        $editorB = $this->user('Editor', [$this->b]);
        $memberB = $this->user('Author', [$this->b]);

        $this->actingAs($editorB)
            ->post(route('journals.template.upload', $this->b), ['template' => $this->docx('template-jt.docx')])
            ->assertSessionHasNoErrors();

        $this->b->refresh();
        $this->assertSame(1, $this->b->template_version);
        $firstPath = $this->b->template_path;
        Storage::disk('local')->assertExists($firstPath);

        $this->actingAs($memberB)->get(route('journals.template', $this->b))->assertOk()->assertDownload('template-jt.docx');

        // unggah ulang: versi naik, berkas lama dihapus, jurnal lain tidak tersentuh
        $this->actingAs($editorB)->post(route('journals.template.upload', $this->b), ['template' => $this->docx('template-jt-v2.docx')]);
        $this->b->refresh();
        $this->assertSame(2, $this->b->template_version);
        Storage::disk('local')->assertMissing($firstPath);
        $this->assertNull($this->a->fresh()->template_path);
        $this->assertSame(0, $this->a->fresh()->template_version);
    }

    public function test_only_admin_or_editor_of_that_journal_can_upload_template(): void
    {
        $file = fn () => ['template' => $this->docx()];

        $this->actingAs($this->user('Editor', [$this->a]))->post(route('journals.template.upload', $this->b), $file())->assertForbidden();
        $this->actingAs($this->user('Author', [$this->b]))->post(route('journals.template.upload', $this->b), $file())->assertForbidden();
        $this->actingAs($this->user('Administrator'))->post(route('journals.template.upload', $this->b), $file())->assertSessionHasNoErrors();

        $this->assertSame(1, $this->b->fresh()->template_version);
    }

    public function test_template_upload_rejects_dangerous_files(): void
    {
        $this->actingAs($this->user('Administrator'))
            ->post(route('journals.template.upload', $this->b), ['template' => $this->docx('x.docx', withMacro: true)])
            ->assertSessionHasErrors('template');

        $this->assertNull($this->b->fresh()->template_path);
    }

    // ---------------------------------------------------------------- bergabung & keluar

    public function test_only_author_accounts_join_by_themselves(): void
    {
        $this->actingAs($this->user('Editor'))->post(route('journals.join', $this->a))->assertForbidden();
        $this->actingAs($this->user('Reviewer'))->post(route('journals.join', $this->a))->assertForbidden();
    }

    public function test_cannot_join_inactive_journal(): void
    {
        $this->b->update(['is_active' => false]);
        $author = $this->user('Author');

        $this->actingAs($author)->post(route('journals.join', $this->b))->assertSessionHas('error');
        $this->assertFalse($author->isMemberOf($this->b));
    }

    public function test_author_can_leave_group_only_without_submissions(): void
    {
        $author = $this->user('Author', [$this->a, $this->b]);
        $this->manuscript($this->a, $author);

        $this->actingAs($author)->delete(route('journals.leave', $this->a))->assertSessionHas('error');
        $this->assertTrue($author->isMemberOf($this->a, Journal::AUTHOR));

        $this->delete(route('journals.leave', $this->b))->assertSessionHas('success');
        $this->assertFalse($author->fresh()->isMemberOf($this->b));
    }

    // ---------------------------------------------------------------- pengajuan naskah

    private function payload(Journal $journal): array
    {
        return [
            'journal_id' => $journal->id, 'title' => 'Judul Baru', 'research_field_id' => $this->field->id,
            'abstract' => 'Abstrak lengkap.', 'keywords' => 'riset, brida', 'file' => $this->pdf(),
        ];
    }

    public function test_author_can_only_submit_to_a_journal_they_joined(): void
    {
        $author = $this->user('Author', [$this->a]);

        $this->actingAs($author)->post(route('author.manuscripts.store'), $this->payload($this->b))
            ->assertSessionHasErrors('journal_id');
        $this->assertSame(0, Manuscript::count());

        $this->post(route('author.manuscripts.store'), $this->payload($this->a))->assertSessionHasNoErrors();
        $this->assertSame($this->a->id, Manuscript::firstOrFail()->journal_id);
    }

    public function test_submit_form_offers_only_joined_journals_and_nudges_when_none(): void
    {
        $this->a->update(['name' => 'Jurnal Bawaan Uji']);   // nama unik: nama bawaan juga muncul di judul halaman
        $author = $this->user('Author', [$this->b]);

        $this->actingAs($author)->get(route('author.manuscripts.create'))
            ->assertOk()->assertSee('Jurnal Teknologi')->assertDontSee('Jurnal Bawaan Uji');

        $this->actingAs($this->user('Author'))->get(route('author.manuscripts.create'))
            ->assertOk()->assertSee('belum bergabung');
    }

    // ---------------------------------------------------------------- editor dibatasi per jurnal

    public function test_editor_sees_only_manuscripts_of_own_journal(): void
    {
        $author  = $this->user('Author', [$this->a, $this->b]);
        $editorA = $this->user('Editor', [$this->a]);
        $mine    = $this->manuscript($this->a, $author, 'pending', ['title' => 'Milik Jurnal A']);
        $other   = $this->manuscript($this->b, $author, 'pending', ['title' => 'Milik Jurnal B']);

        $titles = $this->actingAs($editorA)
            ? app(EditorService::class)->getNewManuscripts()->pluck('title')->all()
            : [];
        $this->assertSame(['Milik Jurnal A'], $titles);

        $this->get(route('editor.manuscripts.detail', $mine))->assertOk();
        $this->get(route('editor.manuscripts.detail', $other))->assertNotFound();   // bukan 403: keberadaannya tidak dibuka

        $stats = collect(app(EditorService::class)->getDashboardStats())->firstWhere('label', 'Naskah Baru');
        $this->assertSame(1, $stats['count']);
    }

    public function test_editor_of_two_journals_sees_both_and_admin_sees_all(): void
    {
        $author = $this->user('Author', [$this->a, $this->b]);
        $this->manuscript($this->a, $author);
        $this->manuscript($this->b, $author);

        $this->actingAs($this->user('Editor', [$this->a, $this->b]));
        $this->assertSame(2, app(EditorService::class)->getNewManuscripts()->total());

        $this->actingAs($this->user('Editor'));   // tanpa jurnal: tidak melihat apa pun
        $this->assertSame(0, app(EditorService::class)->getNewManuscripts()->total());

        $this->actingAs($this->user('Administrator'));
        $this->assertSame(2, app(EditorService::class)->getNewManuscripts()->total());
    }

    public function test_editor_cannot_open_files_of_another_journal(): void
    {
        $author = $this->user('Author', [$this->a, $this->b]);
        $mine   = $this->manuscript($this->a, $author, 'pending', ['file_path' => 'manuscripts/1/a.pdf', 'file_original_name' => 'a.pdf']);
        $other  = $this->manuscript($this->b, $author, 'pending', ['file_path' => 'manuscripts/1/b.pdf', 'file_original_name' => 'b.pdf']);
        Storage::disk('local')->put('manuscripts/1/a.pdf', '%PDF-1.4');
        Storage::disk('local')->put('manuscripts/1/b.pdf', '%PDF-1.4');

        $editorA = $this->user('Editor', [$this->a]);

        $this->actingAs($editorA)->get(route('files.manuscript', [$mine, 'original']))->assertOk();
        $this->get(route('files.manuscript', [$other, 'original']))->assertForbidden();
    }

    public function test_review_attachment_is_hidden_from_editor_of_another_journal(): void
    {
        $author   = $this->user('Author', [$this->b]);
        $reviewer = $this->user('Reviewer', [$this->b]);
        $m        = $this->manuscript($this->b, $author, 'ditinjau');
        $review   = Review::create([
            'manuscript_id' => $m->id, 'reviewer_id' => $reviewer->id, 'assigned_by' => $reviewer->id,
            'status' => 'selesai', 'review_file_path' => 'reviews/1/r.pdf', 'review_file_name' => 'r.pdf', 'due_at' => now()->addDay(),
        ]);
        Storage::disk('local')->put('reviews/1/r.pdf', '%PDF-1.4');

        $this->actingAs($this->user('Editor', [$this->a]))->get(route('files.review', $review))->assertForbidden();
        $this->actingAs($this->user('Editor', [$this->b]))->get(route('files.review', $review))->assertOk();
    }

    // ---------------------------------------------------------------- reviewer per jurnal

    public function test_assignable_reviewers_are_members_of_the_manuscript_journal(): void
    {
        $author = $this->user('Author', [$this->a]);
        $inA    = $this->user('Reviewer', [$this->a]);
        $inB    = $this->user('Reviewer', [$this->b]);
        $m      = $this->manuscript($this->a, $author);

        $this->actingAs($this->user('Editor', [$this->a]));
        $service = app(EditorService::class);

        $this->assertSame([$inA->id], $service->getAssignableReviewers([], $m)->pluck('id')->all());
        $this->assertSame([$inA->id], $service->getRecommendedReviewers($m)->pluck('id')->all());
        $this->assertSame([$inA->id], $service->getReviewerDirectory()->pluck('id')->all());
        $this->assertNotContains($inB->id, $service->getAssignableReviewers()->pluck('id')->all());
    }

    public function test_reviewer_from_another_journal_cannot_be_assigned(): void
    {
        $author  = $this->user('Author', [$this->a]);
        $editor  = $this->user('Editor', [$this->a]);
        $members = [$this->user('Reviewer', [$this->a]), $this->user('Reviewer', [$this->a])];
        $outsider = $this->user('Reviewer', [$this->b]);
        $m = $this->manuscript($this->a, $author);

        $this->actingAs($editor)
            ->postJson(route('editor.manuscripts.assign', $m), [
                'decision' => 'diterima', 'reviewer_ids' => [$members[0]->id, $members[1]->id, $outsider->id],
            ])->assertStatus(422);

        $this->assertSame('pending', $m->fresh()->status);
        $this->assertSame(0, Review::count());
    }

    public function test_editor_cannot_view_reviewer_profile_outside_own_journals(): void
    {
        $inA = $this->user('Reviewer', [$this->a]);
        $inB = $this->user('Reviewer', [$this->b]);

        $this->actingAs($this->user('Editor', [$this->a]));
        $this->get(route('editor.reviewers.profile', $inA))->assertOk();
        $this->get(route('editor.reviewers.profile', $inB))->assertNotFound();
    }

    // ---------------------------------------------------------------- notifikasi

    public function test_submission_notifies_only_editors_of_that_journal(): void
    {
        $author  = $this->user('Author', [$this->a]);
        $editorA = $this->user('Editor', [$this->a]);
        $editorB = $this->user('Editor', [$this->b]);

        app(AuthorService::class)->submit($author, [
            'journal_id' => $this->a->id, 'title' => 'Judul Uji', 'research_field_id' => $this->field->id,
            'abstract' => 'A', 'keywords' => 'k',
        ], $this->pdf());

        $this->assertSame(1, $editorA->notifications()->count());
        $this->assertSame(0, $editorB->notifications()->count());
    }

    // ---------------------------------------------------------------- edisi per jurnal

    public function test_issues_belong_to_a_journal_and_numbers_are_unique_per_journal(): void
    {
        $editorA = $this->user('Editor', [$this->a]);
        $editorB = $this->user('Editor', [$this->b]);
        $data    = ['volume' => 1, 'number' => 1, 'year' => 2026];

        $this->actingAs($editorA)->post(route('editor.issues.store'), $data)->assertSessionHasNoErrors();
        $this->actingAs($editorA)->post(route('editor.issues.store'), $data)->assertSessionHasErrors('number'); // kembar di jurnal yang sama

        $this->actingAs($editorB)->post(route('editor.issues.store'), $data)->assertSessionHasNoErrors();      // jurnal lain: boleh

        $this->assertSame(1, Issue::where('journal_id', $this->a->id)->count());
        $this->assertSame(1, Issue::where('journal_id', $this->b->id)->count());
    }

    public function test_editor_cannot_touch_issue_of_another_journal_or_choose_foreign_journal(): void
    {
        $editorA = $this->user('Editor', [$this->a]);
        $issueB  = Issue::create(['journal_id' => $this->b->id, 'volume' => 1, 'number' => 1, 'year' => 2026, 'status' => 'draft']);

        $this->actingAs($editorA)->put(route('editor.issues.update', $issueB), ['volume' => 2, 'number' => 1, 'year' => 2026])->assertNotFound();
        $this->delete(route('editor.issues.destroy', $issueB))->assertNotFound();

        $this->post(route('editor.issues.store'), ['journal_id' => $this->b->id, 'volume' => 3, 'number' => 1, 'year' => 2026])
            ->assertSessionHasErrors('journal_id');
    }

    public function test_manuscript_from_another_journal_cannot_be_attached_to_issue(): void
    {
        $author = $this->user('Author', [$this->b]);
        $editor = $this->user('Editor', [$this->a, $this->b]);
        $issueA = Issue::create(['journal_id' => $this->a->id, 'volume' => 1, 'number' => 1, 'year' => 2026, 'status' => 'draft']);
        $fromB  = $this->manuscript($this->b, $author, 'disetujui');

        $this->actingAs($editor)
            ->post(route('editor.issues.manuscripts.attach', $issueA), ['manuscript_id' => $fromB->id, 'final_file' => $this->pdf()])
            ->assertSessionHas('error');

        $this->assertNull($fromB->fresh()->issue_id);

        $this->get(route('editor.issues.index', ['issue' => $issueA->id]))->assertOk()->assertDontSee($fromB->title);
    }

    // ---------------------------------------------------------------- admin: jurnal & anggota

    public function test_admin_creates_journal_and_appoints_editor(): void
    {
        $admin  = $this->user('Administrator');
        $editor = $this->user('Editor');

        $this->actingAs($admin)->post(route('admin.journals.store'), ['name' => 'Jurnal Kebijakan', 'abbreviation' => 'JK'])
            ->assertSessionHasNoErrors();

        $journal = Journal::where('name', 'Jurnal Kebijakan')->firstOrFail();
        $this->assertSame('jurnal-kebijakan', $journal->slug);

        $this->post(route('admin.journals.members.add', $journal), ['user_id' => $editor->id, 'role' => 'Editor'])->assertSessionHas('success');
        $this->assertTrue($editor->isMemberOf($journal, Journal::EDITOR));

        $this->get(route('admin.journals.index'))->assertOk()->assertSee('Jurnal Kebijakan');
        $this->get(route('admin.journals.show', $journal))->assertOk()->assertSee($editor->name);
    }

    public function test_member_role_must_match_account_type(): void
    {
        $admin  = $this->user('Administrator');
        $author = $this->user('Author');

        $this->actingAs($admin)->post(route('admin.journals.members.add', $this->a), ['user_id' => $author->id, 'role' => 'Editor'])
            ->assertSessionHas('error');

        $this->assertFalse($author->isMemberOf($this->a));
    }

    public function test_editor_manages_reviewers_of_own_journal_only(): void
    {
        $editorA  = $this->user('Editor', [$this->a]);
        $reviewer = $this->user('Reviewer');

        $this->actingAs($editorA)->post(route('editor.journals.members.add', $this->a), ['user_id' => $reviewer->id, 'role' => 'Reviewer'])
            ->assertSessionHas('success');
        $this->assertTrue($reviewer->isMemberOf($this->a, Journal::REVIEWER));

        // jurnal lain: tidak ditemukan; dan editor tidak boleh menunjuk editor lain
        $this->post(route('editor.journals.members.add', $this->b), ['user_id' => $reviewer->id, 'role' => 'Reviewer'])->assertNotFound();
        $this->post(route('editor.journals.members.add', $this->a), ['user_id' => $this->user('Editor')->id, 'role' => 'Editor'])
            ->assertSessionHasErrors('role');

        $this->get(route('editor.journals.index'))->assertOk()->assertSee($reviewer->name);
    }

    public function test_reviewer_with_active_review_cannot_be_removed_from_journal(): void
    {
        $editor   = $this->user('Editor', [$this->a]);
        $reviewer = $this->user('Reviewer', [$this->a]);
        $m        = $this->manuscript($this->a, $this->user('Author', [$this->a]), 'ditinjau');
        Review::create(['manuscript_id' => $m->id, 'reviewer_id' => $reviewer->id, 'assigned_by' => $editor->id, 'status' => 'diterima', 'due_at' => now()->addDay()]);

        $this->actingAs($editor)->delete(route('editor.journals.members.remove', [$this->a, $reviewer, 'Reviewer']))
            ->assertSessionHas('error');

        $this->assertTrue($reviewer->isMemberOf($this->a, Journal::REVIEWER));
    }

    public function test_journal_management_pages_are_role_protected(): void
    {
        $this->actingAs($this->user('Author', [$this->a]))->get(route('admin.journals.index'))->assertForbidden();
        $this->actingAs($this->user('Editor', [$this->a]))->get(route('admin.journals.index'))->assertForbidden();
        $this->actingAs($this->user('Author', [$this->a]))->get(route('editor.journals.index'))->assertForbidden();
    }
}
