<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Manuscript;
use App\Models\User;
use App\Services\AdminService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminModuleTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = $this->makeUser('Administrator', 'admin@t.test');
    }

    private function makeUser(string $role, ?string $email = null, array $extra = []): User
    {
        return User::create($extra + [
            'name' => "Tes {$role}", 'email' => $email ?? strtolower($role) . '@t.test',
            'password' => 'password', 'role' => $role,
        ]);
    }

    private function userData(array $override = []): array
    {
        return $override + [
            'name' => 'Pengguna Baru', 'email' => 'baru@t.test', 'password' => 'rahasia123',
            'role' => 'Editor', 'institution' => 'BRIDA', 'phone' => '0411-123456', 'is_active' => true,
        ];
    }

    // ---------------------------------------------------------------- akses

    public function test_only_admin_can_access_admin_routes(): void
    {
        $this->get(route('admin.users.index'))->assertRedirect(route('login'));

        foreach (['Author', 'Editor', 'Reviewer'] as $role) {
            $this->actingAs($this->makeUser($role))->get(route('admin.users.index'))->assertForbidden();
        }

        $this->actingAs($this->admin)->get(route('admin.users.index'))->assertOk();
        $this->actingAs($this->admin)->get(route('admin.roles.index'))->assertOk()->assertSee('Mengelola pengguna');
        $this->actingAs($this->admin)->get(route('admin.profile.edit'))->assertOk();
    }

    public function test_dashboard_shows_stats_and_latest_five_logs(): void
    {
        $this->makeUser('Author', 'a@t.test');
        $this->makeUser('Reviewer', 'r@t.test');
        $old = $this->makeUser('Author', 'old@t.test');
        $old->forceFill(['created_at' => now()->subDays(45)])->save();
        Manuscript::create(['author_id' => $old->id, 'title' => 'N', 'status' => 'pending']);

        $stats = app(AdminService::class)->getDashboardStats();
        $this->assertSame(4, $stats['total_users']);
        $this->assertSame(['Author' => 2, 'Editor' => 0, 'Reviewer' => 1, 'Admin' => 1], $stats['roles']);
        $this->assertSame(1, $stats['total_manuscripts']);
        $this->assertSame(3, $stats['new_users']);

        $this->assertCount(4, app(AdminService::class)->getRecentLogs()); // 1 log registrasi per akun
        $this->makeUser('Editor', 'e1@t.test');
        $this->makeUser('Editor', 'e2@t.test');
        $this->assertCount(5, app(AdminService::class)->getRecentLogs());

        $this->actingAs($this->admin)->get('/dashboard')
            ->assertOk()->assertSee('Audit Log Terbaru')->assertSee('Akun baru terdaftar');
    }

    // ---------------------------------------------------------------- Kelola Pengguna

    public function test_user_list_search_and_role_filter(): void
    {
        $this->makeUser('Author', 'budi@t.test', ['name' => 'Budi Santoso']);
        $this->makeUser('Reviewer', 'siti@t.test', ['name' => 'Siti']);

        $json = fn (array $q) => $this->actingAs($this->admin)->getJson(route('admin.users.index', $q))->assertOk()->json();

        $this->assertSame(3, $json([])['total']);
        $this->assertSame(1, $json(['search' => 'Budi'])['total']);
        $this->assertSame(1, $json(['search' => 'siti@t'])['total']);
        $this->assertSame(1, $json(['role' => 'Reviewer'])['total']);
        $this->assertSame(1, $json(['role' => 'Administrator'])['total']);
    }

    public function test_admin_can_create_user_with_hashed_password(): void
    {
        $this->actingAs($this->admin)->postJson(route('admin.users.store'), $this->userData())
            ->assertOk()->assertJson(['success' => true]);

        $user = User::where('email', 'baru@t.test')->firstOrFail();
        $this->assertSame('Editor', $user->role);
        $this->assertSame('BRIDA', $user->institution);
        $this->assertTrue($user->is_active);
        $this->assertTrue(Hash::check('rahasia123', $user->password));
        $this->assertStringStartsWith('$2y$', $user->password); // Bcrypt
    }

    public function test_create_user_validation(): void
    {
        $this->actingAs($this->admin);

        $this->postJson(route('admin.users.store'), [])
            ->assertUnprocessable()->assertJsonValidationErrors(['name', 'email', 'password', 'role']);
        $this->postJson(route('admin.users.store'), $this->userData(['password' => 'pendek']))
            ->assertJsonValidationErrors('password');
        $this->postJson(route('admin.users.store'), $this->userData(['email' => 'admin@t.test']))
            ->assertJsonValidationErrors('email');
        $this->postJson(route('admin.users.store'), $this->userData(['role' => 'Superuser']))
            ->assertJsonValidationErrors('role');
        $this->postJson(route('admin.users.store'), $this->userData(['phone' => 'abc']))
            ->assertJsonValidationErrors('phone');
    }

    public function test_admin_can_update_user_and_password_is_optional(): void
    {
        $user = $this->makeUser('Author', 'u@t.test');
        $oldHash = $user->password;

        $this->actingAs($this->admin)->putJson(route('admin.users.update', $user), $this->userData([
            'email' => 'u@t.test', 'role' => 'Reviewer', 'password' => '', 'name' => 'Nama Baru',
        ]))->assertOk();

        $user->refresh();
        $this->assertSame('Reviewer', $user->role);
        $this->assertSame('Nama Baru', $user->name);
        $this->assertSame($oldHash, $user->password);

        $this->putJson(route('admin.users.update', $user), $this->userData(['email' => 'admin@t.test']))
            ->assertJsonValidationErrors('email'); // bentrok dengan akun lain
    }

    public function test_toggle_status_and_suspended_user_is_logged_out(): void
    {
        $user = $this->makeUser('Author', 'u@t.test');

        $this->actingAs($this->admin)->patchJson(route('admin.users.status', $user))->assertOk();
        $this->assertFalse($user->fresh()->is_active);
        $this->assertDatabaseHas('activity_logs', ['action' => 'user.status']);

        // akun suspend ditolak di request berikutnya
        $this->actingAs($user->fresh())->get(route('author.manuscripts.index'))->assertRedirect(route('login'));
        $this->assertGuest();

        $this->actingAs($this->admin)->patchJson(route('admin.users.status', $user))->assertOk();
        $this->assertTrue($user->fresh()->is_active);
    }

    public function test_reset_password_returns_temporary_password_once(): void
    {
        $user = $this->makeUser('Author', 'u@t.test');

        $res = $this->actingAs($this->admin)->postJson(route('admin.users.reset', $user))->assertOk();
        $temp = $res->json('password');

        $this->assertGreaterThanOrEqual(12, strlen($temp));
        $this->assertTrue(Hash::check($temp, $user->fresh()->password));
        $this->assertFalse(Hash::check('password', $user->fresh()->password));
        $this->assertDatabaseMissing('activity_logs', ['description' => "Password {$user->name} direset oleh administrator " . $temp]);
    }

    // ---------------------------------------------------------------- RBAC

    public function test_admin_can_change_role_and_it_is_logged(): void
    {
        $user = $this->makeUser('Author', 'u@t.test');

        $this->actingAs($this->admin)->patchJson(route('admin.users.role', $user), ['role' => 'Editor'])->assertOk();

        $this->assertSame('Editor', $user->fresh()->role);
        $this->assertDatabaseHas('activity_logs', ['action' => 'user.role', 'user_id' => $this->admin->id]);

        $this->patchJson(route('admin.users.role', $user), ['role' => 'Hacker'])->assertJsonValidationErrors('role');
    }

    public function test_admin_cannot_lock_themselves_or_remove_last_admin(): void
    {
        $this->actingAs($this->admin);

        $this->patchJson(route('admin.users.role', $this->admin), ['role' => 'Author'])
            ->assertUnprocessable()->assertJson(['success' => false]);
        $this->patchJson(route('admin.users.status', $this->admin))->assertUnprocessable();
        $this->assertSame('Administrator', $this->admin->fresh()->role);
        $this->assertTrue($this->admin->fresh()->is_active);

        // admin lain boleh diturunkan selama masih ada admin aktif lain (yaitu pelaku)
        $other = $this->makeUser('Administrator', 'a2@t.test');
        $this->patchJson(route('admin.users.role', $other), ['role' => 'Author'])->assertOk();

        // admin aktif terakhir tidak boleh dihilangkan oleh admin yang sudah nonaktif/bukan dirinya
        $service = app(AdminService::class);
        $this->expectException(\DomainException::class);
        $service->changeRole($this->admin, 'Author', $this->makeUser('Editor', 'e@t.test'));
    }

    // ---------------------------------------------------------------- Profil

    public function test_admin_can_update_profile_and_password(): void
    {
        $this->actingAs($this->admin);

        $this->put(route('admin.profile.update'), ['name' => 'Admin Baru', 'email' => 'admin@t.test'])
            ->assertRedirect(route('admin.profile.edit'))->assertSessionHas('success');
        $this->assertSame('Admin Baru', $this->admin->fresh()->name);

        // ganti password butuh password saat ini
        $this->put(route('admin.profile.update'), [
            'name' => 'Admin Baru', 'email' => 'admin@t.test', 'password' => 'barubaru1', 'password_confirmation' => 'barubaru1',
        ])->assertSessionHasErrors('current_password');

        $this->put(route('admin.profile.update'), [
            'name' => 'Admin Baru', 'email' => 'admin@t.test', 'current_password' => 'password',
            'password' => 'barubaru1', 'password_confirmation' => 'barubaru1',
        ])->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('barubaru1', $this->admin->fresh()->password));
    }

    public function test_activity_log_helper_records_actor_and_ip(): void
    {
        $log = ActivityLog::record('x.test', 'Uji', $this->admin->id);
        $this->assertSame($this->admin->id, $log->user_id);
    }
}
