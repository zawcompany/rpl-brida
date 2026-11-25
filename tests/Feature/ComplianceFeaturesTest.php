<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Complaint;
use App\Models\Journal;
use App\Models\Manuscript;
use App\Models\ResearchField;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fitur kepatuhan tahap 2: akreditasi jurnal, pengaduan, jejak audit lengkap,
 * hak atas data pribadi, persetujuan ulang kebijakan, pemberitahuan massal, dan informasi publik jurnal.
 */
class ComplianceFeaturesTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role, array $journals = []): User
    {
        $user = User::create([
            'name' => $role.' '.uniqid(),
            'email' => uniqid().'@example.com',
            'password' => 'password123',
            'role' => $role,
        ]);

        foreach ($journals as $journal) {
            $user->joinJournal($journal, $role);
        }

        return $user;
    }

    private function manuscript(User $author, array $attrs = []): Manuscript
    {
        $field = ResearchField::firstOrCreate(['slug' => 'umum'], ['name' => 'Umum']);

        return Manuscript::create($attrs + [
            'journal_id' => Journal::defaultId(),
            'author_id' => $author->id,
            'research_field_id' => $field->id,
            'title' => 'Naskah '.uniqid(),
            'status' => 'pending',
        ]);
    }

    private function journalPayload(Journal $journal, array $extra = []): array
    {
        return $extra + ['name' => $journal->name, 'is_active' => '1'];
    }

    // ------------------------------------------------------------------ Akreditasi

    public function test_admin_sets_accreditation_and_it_appears_on_public_pages(): void
    {
        $journal = Journal::default();

        $this->actingAs($this->user('Administrator'))
            ->put(route('admin.journals.update', $journal), $this->journalPayload($journal, [
                'accreditation_rank' => '2',
                'accreditation_decree' => '123/E/KPT/2026',
                'accreditation_valid_until' => now()->addYears(4)->toDateString(),
            ]))
            ->assertSessionHasNoErrors();

        $this->get(route('journals.show', $journal))
            ->assertOk()
            ->assertSee('Terakreditasi Peringkat 2')
            ->assertSee('123/E/KPT/2026');
    }

    public function test_accreditation_rank_requires_valid_until_date(): void
    {
        $journal = Journal::default();

        $this->actingAs($this->user('Administrator'))
            ->put(route('admin.journals.update', $journal), $this->journalPayload($journal, ['accreditation_rank' => '1']))
            ->assertSessionHasErrors('accreditation_valid_until');
    }

    public function test_clearing_rank_removes_accreditation(): void
    {
        $journal = Journal::default();
        $journal->update(['accreditation_rank' => 3, 'accreditation_decree' => 'SK', 'accreditation_valid_until' => now()->addYear()]);

        $this->actingAs($this->user('Administrator'))
            ->put(route('admin.journals.update', $journal), $this->journalPayload($journal, ['accreditation_rank' => '']));

        $journal->refresh();
        $this->assertNull($journal->accreditation_rank);
        $this->assertNull($journal->accreditation_valid_until);
        $this->assertNull($journal->accreditation_decree);
    }

    public function test_expired_accreditation_is_not_shown(): void
    {
        $journal = Journal::default();
        $journal->update(['accreditation_rank' => 2, 'accreditation_valid_until' => now()->subDay()]);

        $this->assertFalse($journal->fresh()->isAccredited());
        $this->get(route('journals.show', $journal))->assertOk()->assertDontSee('Terakreditasi Peringkat');
    }

    public function test_reminder_notifies_admin_and_editor_once_when_accreditation_expires_within_six_months(): void
    {
        $expiring = Journal::create(['name' => 'Jurnal Segera Berakhir']);
        $expiring->update(['accreditation_rank' => 2, 'accreditation_valid_until' => now()->addMonths(5)]);
        $farAway = Journal::default();
        $farAway->update(['accreditation_rank' => 1, 'accreditation_valid_until' => now()->addYears(3)]);

        $admin = $this->user('Administrator');
        $editor = $this->user('Editor', [$expiring]);
        $otherEditor = $this->user('Editor', [$farAway]);

        $this->artisan('simpil:pengingat-akreditasi')->assertSuccessful();
        $this->artisan('simpil:pengingat-akreditasi')->assertSuccessful();

        $this->assertSame(1, $admin->notifications()->count());
        $this->assertSame(1, $editor->notifications()->count());
        $this->assertSame(0, $otherEditor->notifications()->count());
        $this->assertNotNull($expiring->fresh()->accreditation_reminded_at);
    }

    // ------------------------------------------------------------------ Profil jurnal publik

    public function test_journal_page_shows_scope_editorial_board_and_statistics(): void
    {
        $journal = Journal::default();
        $journal->update(['focus_scope' => 'Kebijakan publik daerah']);
        $editor = $this->user('Editor', [$journal]);
        $editor->update(['institution' => 'BRIDA Makassar']);
        $author = $this->user('Author', [$journal]);
        $this->manuscript($author, ['status' => 'ditolak', 'submitted_at' => now()->subDays(10), 'decided_at' => now()]);
        $this->manuscript($author, ['status' => 'disetujui', 'submitted_at' => now()->subDays(20), 'decided_at' => now()]);

        $this->get(route('journals.show', $journal))
            ->assertOk()
            ->assertSee('Kebijakan publik daerah')
            ->assertSee($editor->name)
            ->assertSee('BRIDA Makassar')
            ->assertSee('50%')
            ->assertSee('15 hari');
    }

    // ------------------------------------------------------------------ Pengaduan

    public function test_user_submits_complaint_and_admin_is_notified(): void
    {
        $admin = $this->user('Administrator');
        $author = $this->user('Author');
        $own = $this->manuscript($author);

        $this->actingAs($author)->post(route('complaints.store'), [
            'category' => 'keberatan',
            'subject' => 'Keberatan keputusan',
            'description' => 'Saya keberatan karena alasan penolakan tidak dijelaskan.',
            'manuscript_id' => $own->id,
        ])->assertRedirect();

        $complaint = Complaint::firstOrFail();
        $this->assertSame($author->id, $complaint->user_id);
        $this->assertSame('diterima', $complaint->status);
        $this->assertSame(1, $admin->notifications()->count());
        $this->assertTrue(ActivityLog::where('action', 'complaint.submitted')->exists());
    }

    public function test_complaint_cannot_reference_someone_elses_manuscript(): void
    {
        $author = $this->user('Author');
        $foreign = $this->manuscript($this->user('Author'));

        $this->actingAs($author)->post(route('complaints.store'), [
            'category' => 'layanan',
            'subject' => 'Judul',
            'description' => 'Uraian pengaduan yang cukup panjang.',
            'manuscript_id' => $foreign->id,
        ])->assertSessionHasErrors('manuscript_id');

        $this->assertSame(0, Complaint::count());
    }

    public function test_user_cannot_open_another_users_complaint(): void
    {
        $complaint = Complaint::create([
            'user_id' => $this->user('Author')->id, 'category' => 'layanan', 'subject' => 'S', 'description' => 'D', 'status' => 'diterima',
        ]);

        $this->actingAs($this->user('Author'))->get(route('complaints.show', $complaint))->assertNotFound();
        $this->actingAs($this->user('Editor'))->get(route('admin.complaints.show', $complaint))->assertForbidden();
    }

    public function test_admin_resolves_complaint_and_complainant_is_notified(): void
    {
        $author = $this->user('Author');
        $complaint = Complaint::create([
            'user_id' => $author->id, 'category' => 'layanan', 'subject' => 'S', 'description' => 'D', 'status' => 'diterima',
        ]);
        $admin = $this->user('Administrator');

        $this->actingAs($admin)->put(route('admin.complaints.update', $complaint), ['status' => 'selesai'])
            ->assertSessionHasErrors('response');

        $this->actingAs($admin)->put(route('admin.complaints.update', $complaint), ['status' => 'selesai', 'response' => 'Sudah kami perbaiki.'])
            ->assertSessionHasNoErrors();

        $complaint->refresh();
        $this->assertSame('selesai', $complaint->status);
        $this->assertSame($admin->id, $complaint->handled_by);
        $this->assertSame(1, $author->notifications()->count());
        $this->actingAs($author)->get(route('complaints.show', $complaint))->assertSee('Sudah kami perbaiki.');
    }

    public function test_complaint_past_response_deadline_is_overdue(): void
    {
        $complaint = Complaint::create(['category' => 'layanan', 'subject' => 'S', 'description' => 'D', 'status' => 'diproses']);
        $complaint->created_at = now()->subDays(config('simpil.complaints.response_days') + 1);

        $this->assertTrue($complaint->isOverdue());

        $complaint->status = 'selesai';
        $this->assertFalse($complaint->isOverdue());
    }

    // ------------------------------------------------------------------ Jejak audit

    public function test_login_logout_and_failed_login_are_logged_with_masked_email(): void
    {
        $user = $this->user('Author');

        $this->post('/login', ['email' => $user->email, 'password' => 'salah']);
        $this->post('/login', ['email' => $user->email, 'password' => 'password123']);
        $this->post(route('logout'));

        $failed = ActivityLog::where('action', 'auth.login_failed')->firstOrFail();
        $this->assertStringNotContainsString($user->email, $failed->description);
        $this->assertStringContainsString('***@example.com', $failed->description);
        $this->assertTrue(ActivityLog::where('action', 'auth.login')->where('user_id', $user->id)->exists());
        $this->assertTrue(ActivityLog::where('action', 'auth.logout')->where('user_id', $user->id)->exists());
    }

    public function test_admin_filters_activity_log_by_group(): void
    {
        $admin = $this->user('Administrator');
        ActivityLog::record('auth.login', 'Masuk ke sistem A', $admin->id);
        ActivityLog::record('journal.created', 'Jurnal baru dibuat B', $admin->id);

        $this->actingAs($admin)->get(route('admin.logs.index', ['action' => 'auth']))
            ->assertOk()->assertSee('Masuk ke sistem A')->assertDontSee('Jurnal baru dibuat B');
        $this->actingAs($this->user('Editor'))->get(route('admin.logs.index'))->assertForbidden();
    }

    // ------------------------------------------------------------------ Hak data pribadi

    public function test_user_downloads_only_their_own_personal_data(): void
    {
        $user = $this->user('Author');
        $other = $this->user('Author');
        $this->manuscript($user, ['title' => 'Naskah Milik Saya']);
        $this->manuscript($other, ['title' => 'Naskah Orang Lain']);

        $response = $this->actingAs($user)->get(route('profile.export'))->assertOk();

        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));
        $response->assertJsonPath('akun.email', $user->email)
            ->assertJsonPath('naskah.0.judul', 'Naskah Milik Saya')
            ->assertJsonMissing(['email' => $other->email])
            ->assertJsonMissing(['judul' => 'Naskah Orang Lain']);
        $this->assertTrue(ActivityLog::where('action', 'privacy.export')->where('user_id', $user->id)->exists());
    }

    public function test_user_must_accept_current_policy_before_using_the_system(): void
    {
        config(['simpil.legal.require_consent' => true]);
        $user = $this->user('Author');
        $user->forceFill(['consent_version' => 'versi-lama'])->save();

        $this->actingAs($user)->get(route('author.dashboard'))->assertRedirect(route('consent.show'));
        $this->actingAs($user)->get(route('legal.privacy'))->assertOk();
        $this->actingAs($user)->post(route('consent.accept'))->assertSessionHasErrors('consent');

        $this->actingAs($user)->post(route('consent.accept'), ['consent' => '1'])->assertRedirect();

        $this->assertSame(config('simpil.legal.version'), $user->fresh()->consent_version);
        $this->actingAs($user)->get(route('author.dashboard'))->assertOk();
    }

    // ------------------------------------------------------------------ Pemberitahuan massal

    public function test_admin_announcement_reaches_only_selected_active_role(): void
    {
        $admin = $this->user('Administrator');
        $author = $this->user('Author');
        $inactive = $this->user('Author');
        $inactive->update(['is_active' => false]);
        $editor = $this->user('Editor');

        $this->actingAs($admin)->post(route('admin.announcement.store'), [
            'audience' => 'Author', 'title' => 'Pemberitahuan', 'message' => 'Isi pemberitahuan.',
        ])->assertSessionHasNoErrors();

        $this->assertSame(1, $author->notifications()->count());
        $this->assertSame(0, $inactive->notifications()->count());
        $this->assertSame(0, $editor->notifications()->count());
    }
}
