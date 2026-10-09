<?php

namespace Tests\Feature;

use App\Models\Manuscript;
use App\Models\Review;
use App\Models\User;
use App\Services\Files\DocumentInspector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\MakesDocuments;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase, MakesDocuments;

    private function user(string $role, string $email): User
    {
        return User::create(['name' => $role, 'email' => $email, 'password' => 'password', 'role' => $role]);
    }

    private function manuscript(User $author, array $attrs = []): Manuscript
    {
        return Manuscript::create($attrs + ['author_id' => $author->id, 'title' => 'N', 'status' => 'pending']);
    }

    // ------------------------------------------------------------ rute

    public function test_no_route_uses_a_closure_so_route_cache_works(): void
    {
        $closures = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($r) => $r->getActionName() === 'Closure')
            ->reject(fn ($r) => in_array($r->uri(), ['up', 'storage/{path}'], true)) // rute bawaan framework
            ->map(fn ($r) => $r->uri())->values()->all();

        $this->assertSame([], $closures, 'Route closure menggagalkan route:cache: ' . implode(', ', $closures));
    }

    public function test_dev_login_backdoor_is_not_registered_outside_local(): void
    {
        $this->assertFalse(Route::has('dev.login'));

        $this->get('/dev-login/Administrator')->assertNotFound();
        $this->assertGuest();
    }

    public function test_login_is_throttled(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => 'x@t.test', 'password' => 'salah'])->assertSessionHasErrors('email');
        }

        $this->post('/login', ['email' => 'x@t.test', 'password' => 'salah'])->assertStatus(429);
    }

    // ------------------------------------------------------------ berkas privat

    public function test_file_access_follows_role_policy(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('manuscripts/a.pdf', '%PDF-1.4');

        $owner = $this->user('Author', 'owner@t.test');
        $m = $this->manuscript($owner, ['file_path' => 'manuscripts/a.pdf', 'file_original_name' => 'a.pdf']);

        $editor = $this->user('Editor', 'e@t.test');
        $assigned = $this->user('Reviewer', 'r1@t.test');
        $other = $this->user('Reviewer', 'r2@t.test');
        Review::create(['manuscript_id' => $m->id, 'reviewer_id' => $assigned->id, 'assigned_by' => $editor->id, 'status' => 'ditugaskan']);

        $url = route('files.manuscript', [$m, 'original']);

        $this->get($url)->assertRedirect(route('login'));
        foreach ([$owner, $editor, $assigned] as $allowed) {
            $this->actingAs($allowed)->get($url)->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        }
        foreach ([$other, $this->user('Administrator', 'a@t.test'), $this->user('Author', 'o2@t.test')] as $denied) {
            $this->actingAs($denied)->get($url)->assertForbidden();
        }
    }

    // ------------------------------------------------------------ validasi unggahan ketat

    public function test_document_inspector_accepts_real_pdf_and_docx(): void
    {
        $inspector = app(DocumentInspector::class);

        $this->assertNull($inspector->inspect($this->pdf(), ['pdf', 'docx']));
        $this->assertNull($inspector->inspect($this->docx(), ['pdf', 'docx']));
    }

    public function test_document_inspector_rejects_disguised_and_dangerous_files(): void
    {
        $inspector = app(DocumentInspector::class);

        // skrip PHP berganti nama .pdf
        $php = UploadedFile::fake()->createWithContent('shell.pdf', '<?php system($_GET["c"]); ?>');
        $this->assertNotNull($inspector->inspect($php, ['pdf']));

        // double extension
        $double = UploadedFile::fake()->createWithContent('shell.php.pdf', "%PDF-1.4\n");
        $this->assertStringContainsString('ekstensi ganda', $inspector->inspect($double, ['pdf']));

        // ekstensi tidak diizinkan
        $this->assertNotNull($inspector->inspect(UploadedFile::fake()->createWithContent('a.exe', 'MZ'), ['pdf', 'docx']));

        // DOCX berisi makro VBA / ZIP biasa
        $this->assertNotNull($inspector->inspect($this->docx('m.docx', withMacro: true), ['docx']));
        $this->assertNotNull($inspector->inspect(UploadedFile::fake()->createWithContent('z.docx', 'PK bukan zip'), ['docx']));

        // PDF palsu: MIME teks biasa
        $this->assertNotNull($inspector->inspect(UploadedFile::fake()->createWithContent('t.pdf', 'hanya teks'), ['pdf']));

        // format tidak diizinkan pada konteks tertentu (final layout hanya PDF)
        $this->assertNotNull($inspector->inspect($this->docx(), ['pdf']));
    }

    public function test_submit_endpoint_rejects_disguised_upload(): void
    {
        Storage::fake('local');
        $author = $this->user('Author', 'a@t.test');
        $field = \App\Models\ResearchField::create(['name' => 'X', 'slug' => 'x']);

        $this->actingAs($author)->post(route('author.manuscripts.store'), [
            'title' => 'T', 'research_field_id' => $field->id, 'abstract' => 'A', 'keywords' => 'k',
            'file' => UploadedFile::fake()->createWithContent('naskah.pdf', '<?php echo 1;'),
        ])->assertSessionHasErrors('file');

        $this->assertSame(0, Manuscript::count());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    // ------------------------------------------------------------ cache (route:cache siap produksi)

    public function test_route_cache_command_succeeds(): void
    {
        $this->assertSame(0, Artisan::call('route:cache'));
        Artisan::call('route:clear');
    }
}
