<?php

namespace Tests\Feature;

use App\Models\Manuscript;
use App\Models\ResearchField;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\ResearchFieldSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class EditorialFlowAndSessionTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role, string $email): User
    {
        return User::create(['name' => $role, 'email' => $email, 'password' => 'password', 'role' => $role]);
    }

    // ------------------------------------------------------------ keputusan administrasi: Terima / Tolak

    public function test_initial_decision_accepts_only_terima_or_tolak(): void
    {
        $editor = $this->user('Editor', 'e@t.test');
        $reviewer = $this->user('Reviewer', 'r@t.test');
        $author = $this->user('Author', 'a@t.test');
        $m = Manuscript::create(['author_id' => $author->id, 'title' => 'N', 'status' => 'pending']);

        $this->actingAs($editor);

        // nilai lama tidak lagi valid
        foreach (['ditinjau', 'lanjut', ''] as $old) {
            $this->postJson("/editor/naskah/{$m->id}/assign", ['decision' => $old, 'reviewer_id' => $reviewer->id])
                ->assertUnprocessable()->assertJsonValidationErrors('decision');
        }

        // Terima wajib reviewer
        $this->postJson("/editor/naskah/{$m->id}/assign", ['decision' => 'diterima'])
            ->assertUnprocessable()->assertJsonValidationErrors('reviewer_id');

        $this->postJson("/editor/naskah/{$m->id}/assign", ['decision' => 'diterima', 'reviewer_id' => $reviewer->id])->assertOk();
        $this->assertSame('ditinjau', $m->fresh()->status);
    }

    public function test_tolak_rejects_without_reviewer(): void
    {
        $author = $this->user('Author', 'a@t.test');
        $m = Manuscript::create(['author_id' => $author->id, 'title' => 'N', 'status' => 'pending']);

        $this->actingAs($this->user('Editor', 'e@t.test'))
            ->postJson("/editor/naskah/{$m->id}/assign", ['decision' => 'ditolak'])->assertOk();

        $m->refresh();
        $this->assertSame('ditolak', $m->status);
        $this->assertNotNull($m->decided_at);
        $this->assertSame(0, $m->reviews()->count());
    }

    // ------------------------------------------------------------ bidang keahlian

    public function test_research_field_seeder_is_broad_and_idempotent(): void
    {
        $this->seed(ResearchFieldSeeder::class);
        $count = ResearchField::count();

        $this->assertGreaterThanOrEqual(30, $count);
        foreach (['Teknik Elektro', 'Manajemen', 'Pendidikan', 'Hukum', 'Pertanian', 'Sistem Informasi'] as $name) {
            $this->assertDatabaseHas('research_fields', ['name' => $name]);
        }

        $this->seed(ResearchFieldSeeder::class); // jalankan ulang: tidak menggandakan
        $this->assertSame($count, ResearchField::count());
    }

    public function test_database_seeder_still_works_with_expanded_fields(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertGreaterThanOrEqual(30, ResearchField::count());
        $this->assertSame(6, Manuscript::count());
        $this->assertSame('Ilmu Komputer', ResearchField::orderBy('id')->first()->name); // urutan 5 bidang awal tetap
    }

    // ------------------------------------------------------------ sesi / CSRF kedaluwarsa

    public function test_session_lifetime_is_at_least_two_hours_by_default(): void
    {
        $this->assertGreaterThanOrEqual(240, (int) config('session.lifetime'));
    }

    public function test_csrf_mismatch_redirects_pages_to_login_with_message(): void
    {
        Route::middleware('web')->post('/_uji-419', fn () => throw new TokenMismatchException());

        $this->post('/_uji-419')
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['email' => 'Sesi Anda telah berakhir karena tidak aktif. Silakan masuk kembali.']);
    }

    public function test_csrf_mismatch_returns_json_with_login_redirect_for_ajax(): void
    {
        Route::middleware('web')->post('/_uji-419', fn () => throw new TokenMismatchException());

        $this->postJson('/_uji-419')
            ->assertStatus(419)
            ->assertJson(['redirect' => route('login')])
            ->assertJsonStructure(['message', 'redirect']);
    }

    public function test_unauthenticated_ajax_gets_401_json_with_login_redirect(): void
    {
        $this->getJson(route('editor.decisions.index'))
            ->assertStatus(401)
            ->assertJson(['redirect' => route('login')]);

        $this->get(route('editor.decisions.index'))->assertRedirect(route('login')); // halaman biasa tetap redirect
    }
}
