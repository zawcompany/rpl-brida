<?php

namespace Tests\Feature;

use App\Models\Issue;
use App\Models\Manuscript;
use App\Models\User;
use App\Services\Files\CoverImageStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class IssueCoverTest extends TestCase
{
    use RefreshDatabase;

    private User $editor;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->editor = User::create(['name' => 'Ed', 'email' => 'e@t.test', 'password' => 'password', 'role' => 'Editor']);
    }

    private function form(array $override = []): array
    {
        return $override + ['volume' => 1, 'number' => 1, 'year' => 2026, 'title' => 'Edisi Uji'];
    }

    private function image(string $name = 'cover.jpg'): UploadedFile
    {
        return UploadedFile::fake()->image($name, 600, 800);
    }

    // ------------------------------------------------------------ unggah

    public function test_create_issue_with_cover_stores_file_in_covers_directory(): void
    {
        $this->actingAs($this->editor)
            ->post(route('editor.issues.store'), $this->form(['cover_image' => $this->image()]))
            ->assertSessionHasNoErrors();

        $issue = Issue::firstOrFail();
        $this->assertStringStartsWith('issues/covers/', $issue->cover_image);
        Storage::disk('public')->assertExists($issue->cover_image);
        $this->assertSame(Storage::disk('public')->url($issue->cover_image), $issue->cover_url);
    }

    public function test_cover_is_optional_and_placeholder_is_used(): void
    {
        $this->actingAs($this->editor)->post(route('editor.issues.store'), $this->form())->assertSessionHasNoErrors();

        $issue = Issue::firstOrFail();
        $this->assertNull($issue->cover_image);
        $this->assertSame(asset(CoverImageStore::PLACEHOLDER), $issue->cover_url);
        $this->assertFileExists(public_path(CoverImageStore::PLACEHOLDER));
    }

    public function test_update_with_new_cover_replaces_and_deletes_old_file(): void
    {
        $this->actingAs($this->editor);
        $this->post(route('editor.issues.store'), $this->form(['cover_image' => $this->image('a.jpg')]));
        $issue = Issue::firstOrFail();
        $old = $issue->cover_image;

        $this->put(route('editor.issues.update', $issue), $this->form(['title' => 'Baru', 'cover_image' => $this->image('b.png')]))
            ->assertSessionHasNoErrors();

        $issue->refresh();
        $this->assertNotSame($old, $issue->cover_image);
        Storage::disk('public')->assertMissing($old);
        Storage::disk('public')->assertExists($issue->cover_image);
        $this->assertSame('Baru', $issue->title);
    }

    public function test_update_without_cover_keeps_existing_file(): void
    {
        $this->actingAs($this->editor);
        $this->post(route('editor.issues.store'), $this->form(['cover_image' => $this->image()]));
        $issue = Issue::firstOrFail();
        $cover = $issue->cover_image;

        $this->put(route('editor.issues.update', $issue), $this->form(['title' => 'Tanpa Ganti Sampul']));

        $this->assertSame($cover, $issue->fresh()->cover_image);
        Storage::disk('public')->assertExists($cover);
    }

    public function test_deleting_issue_removes_its_cover_file(): void
    {
        $this->actingAs($this->editor);
        $this->post(route('editor.issues.store'), $this->form(['cover_image' => $this->image()]));
        $issue = Issue::firstOrFail();
        $cover = $issue->cover_image;

        $this->delete(route('editor.issues.destroy', $issue));

        $this->assertDatabaseMissing('issues', ['id' => $issue->id]);
        Storage::disk('public')->assertMissing($cover);
    }

    public function test_published_issue_cannot_be_changed_and_cover_is_untouched(): void
    {
        $issue = Issue::create($this->form(['status' => 'published', 'cover_image' => 'issues/covers/x.jpg']));
        Storage::disk('public')->put('issues/covers/x.jpg', 'x');

        $this->actingAs($this->editor)->put(route('editor.issues.update', $issue), $this->form(['cover_image' => $this->image()]))
            ->assertSessionHas('error');

        $this->assertSame('issues/covers/x.jpg', $issue->fresh()->cover_image);
        $this->assertCount(1, Storage::disk('public')->allFiles('issues/covers'));
    }

    // ------------------------------------------------------------ validasi

    public function test_cover_validation(): void
    {
        $this->actingAs($this->editor);

        $cases = [
            'pdf menyamar'   => UploadedFile::fake()->createWithContent('cover.jpg', '%PDF-1.4 bukan gambar'),
            'svg'            => UploadedFile::fake()->createWithContent('cover.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'),
            'gif'            => UploadedFile::fake()->image('cover.gif'),
            'php menyamar'   => UploadedFile::fake()->createWithContent('cover.php.jpg', '<?php system($_GET["c"]); ?>'),
            'terlalu besar'  => UploadedFile::fake()->image('big.jpg')->size(2049),
        ];

        foreach ($cases as $label => $file) {
            try {
                $this->post(route('editor.issues.store'), $this->form(['cover_image' => $file]))->assertSessionHasErrors('cover_image');
            } catch (\PHPUnit\Framework\AssertionFailedError $e) {
                $this->fail("Sampul '{$label}' seharusnya ditolak: " . $e->getMessage());
            }
        }

        $this->assertSame(0, Issue::count());
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_only_editor_can_upload_cover(): void
    {
        $author = User::create(['name' => 'A', 'email' => 'a@t.test', 'password' => 'password', 'role' => 'Author']);

        $this->actingAs($author)->post(route('editor.issues.store'), $this->form(['cover_image' => $this->image()]))->assertForbidden();
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    // ------------------------------------------------------------ tampilan

    public function test_editor_form_has_multipart_cover_input_and_edit_preview(): void
    {
        $issue = Issue::create($this->form(['cover_image' => 'issues/covers/c.jpg']));
        $this->actingAs($this->editor);

        $this->get(route('editor.issues.index'))->assertOk()
            ->assertSee('enctype="multipart/form-data"', false)->assertSee('name="cover_image"', false);

        $this->get(route('editor.issues.index', ['edit' => $issue->id]))->assertOk()
            ->assertSee(Storage::disk('public')->url('issues/covers/c.jpg'), false)->assertSee('Sampul saat ini');
    }

    public function test_public_pages_show_cover_or_placeholder(): void
    {
        $withCover = Issue::create($this->form(['status' => 'published', 'published_at' => now(), 'cover_image' => 'issues/covers/c.jpg']));
        $noCover = Issue::create($this->form(['number' => 2, 'status' => 'published', 'published_at' => now()->subDay()]));
        $author = User::create(['name' => 'A', 'email' => 'a@t.test', 'password' => 'password', 'role' => 'Author']);
        Manuscript::create(['author_id' => $author->id, 'title' => 'Artikel', 'status' => 'diterbitkan', 'issue_id' => $withCover->id, 'published_at' => now()]);

        $coverUrl = Storage::disk('public')->url('issues/covers/c.jpg');
        $placeholder = asset(CoverImageStore::PLACEHOLDER);

        $this->get('/')->assertOk()->assertSee($coverUrl, false)->assertSee($placeholder, false); // terbitan terbaru + arsip
        $this->get(route('archives.index'))->assertOk()->assertSee($coverUrl, false)->assertSee($placeholder, false);
        $this->get(route('archives.show', $noCover))->assertOk()->assertSee($placeholder, false);
        $this->get(route('archives.show', $withCover))->assertOk()->assertSee($coverUrl, false);
    }
}
