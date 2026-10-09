<?php

namespace Tests\Feature;

use App\Models\Manuscript;
use App\Models\ResearchField;
use App\Models\Review;
use App\Models\User;
use App\Notifications\WorkflowNotification;
use App\Services\AuthorService;
use App\Services\EditorService;
use App\Services\NotificationService;
use App\Notifications\QueuedVerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\Concerns\MakesDocuments;
use Tests\TestCase;

class ProfileAndNotificationTest extends TestCase
{
    use RefreshDatabase, MakesDocuments;

    private function user(string $role, ?string $email = null, array $extra = []): User
    {
        return User::create($extra + [
            'name' => "Tes {$role}", 'email' => $email ?? strtolower($role) . '@t.test',
            'password' => 'password', 'role' => $role,
        ]);
    }

    // ------------------------------------------------------------ logout

    public function test_logout_invalidates_session_and_is_idempotent_for_guests(): void
    {
        $this->actingAs($this->user('Author'))->post('/logout')->assertRedirect('/');
        $this->assertGuest();

        $this->post('/logout')->assertRedirect('/'); // sesi sudah habis: tetap bersih, bukan 302 ke login/419/500
    }

    public function test_get_logout_does_not_error(): void
    {
        $this->get('/logout')->assertRedirect('/dashboard');
        $this->actingAs($this->user('Author'))->get('/logout')->assertRedirect('/dashboard');
        $this->assertAuthenticated(); // GET tidak boleh mengeluarkan user (anti CSRF-logout)
    }

    // ------------------------------------------------------------ profil

    public function test_profile_page_adapts_to_every_role(): void
    {
        $expect = [
            'Author'        => 'Total Naskah',
            'Editor'        => 'Naskah Baru',
            'Reviewer'      => 'Review Aktif',
            'Administrator' => 'Total Pengguna',
        ];

        foreach ($expect as $role => $text) {
            $this->actingAs($this->user($role))->get(route('profile.show'))
                ->assertOk()->assertSee('Profil Saya')->assertSee($text);
        }

        // bidang keahlian hanya untuk reviewer
        $this->actingAs($this->user('Author', 'x@t.test'))->get(route('profile.show'))->assertDontSee('Bidang Keahlian');
        $this->actingAs($this->user('Reviewer', 'r@t.test'))->get(route('profile.show'))->assertSee('Bidang Keahlian');

        $this->app['auth']->forgetGuards();
        $this->get(route('profile.show'))->assertRedirect(route('login'));
    }

    public function test_user_updates_own_profile_without_touching_role_or_status(): void
    {
        $user = $this->user('Author');

        $this->actingAs($user)->put(route('profile.update'), [
            'name' => 'Nama Baru', 'email' => $user->email, 'phone' => '0411-123456', 'institution' => 'BRIDA',
            'role' => 'Administrator', 'is_active' => false, 'id' => 999, // mass assignment: harus diabaikan
        ])->assertRedirect(route('profile.show'))->assertSessionHas('success');

        $user->refresh();
        $this->assertSame('Nama Baru', $user->name);
        $this->assertSame('BRIDA', $user->institution);
        $this->assertSame('Author', $user->role);
        $this->assertTrue($user->is_active);
    }

    public function test_profile_validation(): void
    {
        $other = $this->user('Editor');
        $user = $this->user('Author');
        $this->actingAs($user);

        $this->put(route('profile.update'), [])->assertSessionHasErrors(['name', 'email']);
        $this->put(route('profile.update'), ['name' => 'A', 'email' => $other->email])->assertSessionHasErrors('email');
        $this->put(route('profile.update'), ['name' => 'A', 'email' => $user->email, 'phone' => 'abc'])->assertSessionHasErrors('phone');
        $this->put(route('profile.update'), ['name' => '<script>', 'email' => 'bukan-email'])->assertSessionHasErrors('email');
    }

    public function test_reviewer_can_edit_expertise_but_others_cannot(): void
    {
        $f1 = ResearchField::create(['name' => 'A', 'slug' => 'a']);
        $f2 = ResearchField::create(['name' => 'B', 'slug' => 'b']);
        $reviewer = $this->user('Reviewer');
        $author = $this->user('Author');

        $this->actingAs($reviewer)->put(route('profile.update'), [
            'name' => 'R', 'email' => $reviewer->email, 'research_field_ids' => [$f1->id, $f2->id],
        ])->assertSessionHasNoErrors();
        $this->assertCount(2, $reviewer->fresh()->researchFields);

        $this->put(route('profile.update'), ['name' => 'R', 'email' => $reviewer->email, 'research_field_ids' => [999]])
            ->assertSessionHasErrors('research_field_ids.0');

        $this->actingAs($author)->put(route('profile.update'), [
            'name' => 'A', 'email' => $author->email, 'research_field_ids' => [$f1->id],
        ]);
        $this->assertCount(0, $author->fresh()->researchFields);
    }

    public function test_change_password_requires_current_password(): void
    {
        $user = $this->user('Author');
        $this->actingAs($user);

        $this->put(route('profile.password'), ['password' => 'barubaru1', 'password_confirmation' => 'barubaru1'])
            ->assertSessionHasErrorsIn('password', 'current_password');
        $this->put(route('profile.password'), ['current_password' => 'salah', 'password' => 'barubaru1', 'password_confirmation' => 'barubaru1'])
            ->assertSessionHasErrorsIn('password', 'current_password');
        $this->put(route('profile.password'), ['current_password' => 'password', 'password' => 'pendek', 'password_confirmation' => 'pendek'])
            ->assertSessionHasErrorsIn('password', 'password');
        $this->put(route('profile.password'), ['current_password' => 'password', 'password' => 'password', 'password_confirmation' => 'password'])
            ->assertSessionHasErrorsIn('password', 'password');

        $this->put(route('profile.password'), ['current_password' => 'password', 'password' => 'barubaru1', 'password_confirmation' => 'barubaru1'])
            ->assertSessionHasNoErrors()->assertSessionHas('success');
        $this->assertTrue(Hash::check('barubaru1', $user->fresh()->password));
        $this->assertStringStartsWith('$2y$', $user->fresh()->password);
    }

    // ------------------------------------------------------------ verifikasi email

    public function test_email_verification_flow(): void
    {
        Notification::fake();
        $user = $this->user('Author');
        $this->assertFalse($user->hasVerifiedEmail());

        $this->actingAs($user)->post(route('verification.send'))->assertRedirect(route('profile.show'));
        Notification::assertSentTo($user, QueuedVerifyEmail::class);

        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), ['id' => $user->id, 'hash' => sha1($user->email)]);
        $this->get($url)->assertRedirect(route('profile.show'));
        $this->assertTrue($user->fresh()->hasVerifiedEmail());

        Notification::fake();
        $this->post(route('verification.send')); // sudah terverifikasi: tidak mengirim lagi
        Notification::assertNothingSent();
    }

    public function test_verification_link_of_another_user_or_unsigned_is_rejected(): void
    {
        $victim = $this->user('Editor');
        $attacker = $this->user('Author');

        $forged = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), ['id' => $victim->id, 'hash' => sha1($victim->email)]);
        $this->actingAs($attacker)->get($forged)->assertForbidden();
        $this->assertFalse($victim->fresh()->hasVerifiedEmail());

        $this->get(route('verification.verify', ['id' => $attacker->id, 'hash' => sha1($attacker->email)]))->assertForbidden(); // tanpa signature
    }

    public function test_changing_email_resets_verification_and_resends(): void
    {
        Notification::fake();
        $user = $this->user('Author');
        $user->forceFill(['email_verified_at' => now()])->save();

        $this->actingAs($user)->put(route('profile.update'), ['name' => 'A', 'email' => 'Baru@T.Test'])->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertSame('baru@t.test', $user->email); // dinormalkan
        $this->assertNull($user->email_verified_at);
        Notification::assertSentTo($user, QueuedVerifyEmail::class);
    }

    // ------------------------------------------------------------ notifikasi

    /** @return string[] tipe notifikasi milik user */
    private function typesOf(User $user): array
    {
        return $user->notifications()->get()->map(fn ($n) => $n->data['type'])->all();
    }

    private function notify(User $user, string $title = 'Judul', ?string $url = '/dashboard'): string
    {
        $user->notify(new WorkflowNotification('manuscript.submitted', $title, 'Isi pesan', $url));

        return $user->notifications()->get()->first(fn ($n) => $n->data['title'] === $title)->id;
    }

    public function test_notification_page_lists_own_notifications_with_read_state(): void
    {
        $me = $this->user('Author');
        $other = $this->user('Editor');
        $readId = $this->notify($me, 'Sudah Dibaca');
        $this->notify($me, 'Belum Dibaca');
        $this->notify($other, 'Rahasia Orang Lain');
        $me->notifications()->whereKey($readId)->first()->markAsRead();

        $this->actingAs($me)->get(route('notifications.index'))
            ->assertOk()->assertSee('Sudah Dibaca')->assertSee('Belum Dibaca')->assertDontSee('Rahasia Orang Lain')
            ->assertSee('Tandai Semua Dibaca');

        $this->get(route('notifications.index', ['filter' => 'unread']))
            ->assertOk()->assertSee('Belum Dibaca')->assertDontSee('Sudah Dibaca');

        // lonceng menampilkan jumlah belum dibaca di semua halaman
        $this->get(route('profile.show'))->assertSee('1 belum dibaca');
    }

    public function test_open_marks_read_and_redirects_but_never_to_external_urls(): void
    {
        $me = $this->user('Author');
        $good = $this->notify($me, 'Internal', '/author/naskah-saya');
        $evil = $this->notify($me, 'Eksternal', 'https://evil.example/phish');
        $proto = $this->notify($me, 'Protocol-relative', '//evil.example');
        $this->actingAs($me);

        $this->post(route('notifications.open', $good))->assertRedirect('/author/naskah-saya');
        $this->assertNotNull($me->notifications()->find($good)->read_at);

        $this->post(route('notifications.open', $evil))->assertRedirect(route('notifications.index', absolute: false));
        $this->post(route('notifications.open', $proto))->assertRedirect(route('notifications.index', absolute: false));
    }

    public function test_user_cannot_touch_other_users_notifications(): void
    {
        $owner = $this->user('Author');
        $intruder = $this->user('Editor');
        $id = $this->notify($owner);

        $this->actingAs($intruder);
        $this->post(route('notifications.read', $id))->assertNotFound();
        $this->post(route('notifications.open', $id))->assertNotFound();
        $this->assertNull($owner->notifications()->find($id)->read_at);
    }

    public function test_mark_read_and_mark_all_read(): void
    {
        $me = $this->user('Author');
        $a = $this->notify($me);
        $this->notify($me);
        $this->actingAs($me);

        $this->post(route('notifications.read', $a))->assertRedirect();
        $this->assertSame(1, $me->unreadNotifications()->count());

        $this->post(route('notifications.read-all'))->assertSessionHas('success');
        $this->assertSame(0, $me->unreadNotifications()->count());
    }

    // ------------------------------------------------------------ pemicu notifikasi

    public function test_workflow_events_notify_the_right_people(): void
    {
        $editor = $this->user('Editor');
        $reviewer = $this->user('Reviewer');
        $author = $this->user('Author');
        $field = ResearchField::create(['name' => 'X', 'slug' => 'x']);

        // 1. author mengajukan -> editor
        $m = app(AuthorService::class)->submit($author, [
            'title' => 'Judul Uji', 'research_field_id' => $field->id, 'abstract' => 'A', 'keywords' => 'k',
        ], $this->pdf());
        $this->assertSame(1, $editor->notifications()->count());
        $this->assertSame(0, $author->notifications()->count());

        // 2. editor menerima + tugaskan -> reviewer & author
        app(EditorService::class)->processDecision($m, 'diterima', $reviewer->id, null, $editor->id);
        $this->assertSame('review.assigned', $reviewer->notifications()->first()->data['type']);
        $this->assertSame('manuscript.accepted', $author->notifications()->first()->data['type']);

        // 3. keputusan editorial revisi -> author
        $m->update(['status' => 'menunggu_keputusan']);
        app(EditorService::class)->recordEditorialDecision($m, 'revisi', 'Perbaiki metode');
        $this->assertContains('manuscript.revision', $this->typesOf($author));

        // 4. author unggah revisi -> editor
        app(AuthorService::class)->submitRevision($m->fresh(), $this->pdf('rev.pdf'), 'Sudah diperbaiki semua.');
        $this->assertContains('revision.submitted', $this->typesOf($editor));

        // 5. review selesai -> editor
        $review = Review::first()->setRelation('manuscript', $m);
        app(NotificationService::class)->reviewCompleted($review);
        $this->assertContains('review.completed', $this->typesOf($editor));
    }
}
