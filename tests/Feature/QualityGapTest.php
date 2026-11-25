<?php

namespace Tests\Feature;

use App\Models\Manuscript;
use App\Models\ResearchField;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Tes pelengkap: matriks akses role, IDOR antar-author, escaping XSS,
 * validasi registrasi, dan penutupan sesi akun yang ditangguhkan.
 */
class QualityGapTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role, ?string $email = null, array $extra = []): User
    {
        return User::create($extra + [
            'name' => "Tes {$role}", 'email' => $email ?? strtolower($role).'@t.test',
            'password' => 'password', 'role' => $role,
        ]);
    }

    private function makeManuscript(User $owner): Manuscript
    {
        $field = ResearchField::firstOrCreate(['slug' => 'ilmu-komputer'], ['name' => 'Ilmu Komputer']);

        return Manuscript::create([
            'author_id' => $owner->id, 'research_field_id' => $field->id,
            'title' => 'Naskah Rahasia', 'abstract' => 'Abstrak', 'keywords' => 'a, b',
            'file_path' => 'manuscripts/x.pdf', 'file_original_name' => 'x.pdf',
            'status' => 'pending', 'submitted_at' => now(),
        ]);
    }

    // ------------------------------------------------------- matriks role

    public static function roleMatrix(): array
    {
        // [role, route, status yang diharapkan]
        $rows = [];
        $modules = [
            'admin.users.index' => 'Administrator',
            'author.manuscripts.index' => 'Author',
            'reviewer.dashboard' => 'Reviewer',
            'editor.dashboard' => 'Editor',
        ];
        foreach (['Administrator', 'Author', 'Editor', 'Reviewer'] as $role) {
            foreach ($modules as $route => $owner) {
                $rows["{$role} -> {$route}"] = [$role, $route, $role === $owner ? 200 : 403];
            }
        }

        return $rows;
    }

    /** @dataProvider roleMatrix */
    #[DataProvider('roleMatrix')]
    public function test_role_access_matrix(string $role, string $route, int $expected): void
    {
        $user = $this->makeUser($role);

        $response = $this->actingAs($user)->get(route($route));

        $response->assertStatus($expected);
    }

    public function test_guests_are_redirected_from_every_protected_area(): void
    {
        foreach (['dashboard', 'profile.show', 'notifications.index', 'admin.users.index',
            'author.manuscripts.index', 'reviewer.dashboard', 'editor.dashboard'] as $route) {
            $this->get(route($route))->assertRedirect(route('login'));
        }
    }

    // --------------------------------------------------------------- IDOR

    public function test_author_cannot_open_another_authors_manuscript(): void
    {
        $owner = $this->makeUser('Author', 'owner@t.test');
        $intruder = $this->makeUser('Author', 'intruder@t.test');
        $manuscript = $this->makeManuscript($owner);

        $this->actingAs($intruder)
            ->get(route('author.manuscripts.show', $manuscript))
            ->assertForbidden()->assertDontSee('Naskah Rahasia');

        $this->actingAs($intruder)
            ->get(route('files.manuscript', [$manuscript, 'original']))
            ->assertForbidden();
    }

    public function test_reviewer_without_assignment_cannot_download_manuscript(): void
    {
        $manuscript = $this->makeManuscript($this->makeUser('Author'));

        $this->actingAs($this->makeUser('Reviewer'))
            ->get(route('files.manuscript', [$manuscript, 'original']))
            ->assertForbidden();
    }

    // ---------------------------------------------------------------- XSS

    public function test_user_input_is_html_escaped_in_admin_user_list(): void
    {
        $this->makeUser('Author', 'xss@t.test', ['name' => '<script>alert(1)</script>']);

        $this->actingAs($this->makeUser('Administrator'))
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }

    // ---------------------------------------------------------- registrasi

    public function test_registration_validation_rules(): void
    {
        $this->makeUser('Author', 'dipakai@t.test');
        $valid = ['name' => 'Budi', 'email' => 'budi@t.test', 'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123', 'consent' => '1'];

        $this->post('/register', array_merge($valid, ['password' => 'pendek', 'password_confirmation' => 'pendek']))
            ->assertSessionHasErrors('password');
        $this->post('/register', array_merge($valid, ['password_confirmation' => 'beda']))
            ->assertSessionHasErrors('password');
        $this->post('/register', array_merge($valid, ['email' => 'dipakai@t.test']))
            ->assertSessionHasErrors('email');
        $this->post('/register', array_merge($valid, ['email' => 'bukan-email']))
            ->assertSessionHasErrors('email');
        $this->post('/register', array_merge($valid, ['consent' => null]))
            ->assertSessionHasErrors('consent');

        $this->assertDatabaseMissing('users', ['email' => 'budi@t.test']);
    }

    public function test_public_registration_cannot_escalate_role(): void
    {
        $this->post('/register', [
            'name' => 'Nakal', 'email' => 'nakal@t.test', 'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123', 'consent' => '1', 'role' => 'Administrator',
        ]);

        $this->assertDatabaseHas('users', ['email' => 'nakal@t.test', 'role' => 'Author']);
    }

    // ------------------------------------------------------ akun suspend

    public function test_suspended_user_session_is_terminated_on_next_request(): void
    {
        $user = $this->makeUser('Author');
        $this->actingAs($user)->get(route('author.manuscripts.index'))->assertOk();

        $user->update(['is_active' => false]);

        $this->get(route('author.manuscripts.index'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_suspended_user_cannot_log_in(): void
    {
        $this->makeUser('Author', 'off@t.test', ['is_active' => false]);

        // Login diterima lalu langsung ditutup oleh EnsureAccountActive pada request berikutnya.
        $this->followingRedirects()
            ->post('/login', ['email' => 'off@t.test', 'password' => 'password'])
            ->assertSee('Akun Anda ditangguhkan');
        $this->assertGuest();
    }

    public function test_unknown_page_returns_404_not_500(): void
    {
        $this->get('/halaman-yang-tidak-ada')->assertNotFound();
    }
}
