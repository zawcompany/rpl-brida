<?php

namespace Tests\Feature;

use App\Models\Issue;
use App\Models\Manuscript;
use App\Models\User;
use App\Notifications\QueuedVerifyEmail;
use App\Services\AuthorService;
use App\Services\Files\CoverImageStore;
use App\Services\IssueService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\MakesDocuments;
use Tests\TestCase;

/**
 * Bukti kesiapan AWS: beralih local <-> S3 hanya dengan mengubah konfigurasi (.env),
 * tanpa menyentuh kode bisnis. Disk S3 diganti fake agar tes tidak memerlukan AWS.
 */
class StorageDriverSwitchTest extends TestCase
{
    use RefreshDatabase, MakesDocuments;

    private function author(): User
    {
        return User::create(['name' => 'A', 'email' => 'a@t.test', 'password' => 'password', 'role' => 'Author']);
    }

    public function test_manuscript_and_revision_files_follow_manuscript_disk_setting(): void
    {
        config(['simpil.manuscript_disk' => 's3']);
        Storage::fake('s3');
        Storage::fake('local');

        $m = app(AuthorService::class)->submit($this->author(), [
            'title' => 'T', 'research_field_id' => null, 'abstract' => 'A', 'keywords' => 'k',
        ], $this->pdf());

        Storage::disk('s3')->assertExists($m->file_path);
        $this->assertSame([], Storage::disk('local')->allFiles());   // tidak ada yang tertinggal di disk server

        $m->update(['status' => 'revisi']);
        app(AuthorService::class)->submitRevision($m->fresh(), $this->pdf('rev.pdf'), 'Sudah diperbaiki semua.');
        Storage::disk('s3')->assertExists($m->fresh()->revision_file_path);
    }

    public function test_final_pdf_is_served_from_s3_through_authorized_route(): void
    {
        config(['simpil.manuscript_disk' => 's3']);
        Storage::fake('s3');
        $issue = Issue::create(['volume' => 1, 'number' => 1, 'year' => 2026, 'status' => 'published', 'published_at' => now()]);
        $m = Manuscript::create(['author_id' => $this->author()->id, 'title' => 'Terbit', 'status' => 'diterbitkan', 'issue_id' => $issue->id,
            'published_at' => now(), 'final_file_path' => 'issues/1/final.pdf']);
        Storage::disk('s3')->put('issues/1/final.pdf', '%PDF-1.4 isi');

        $this->get(route('articles.download', $m->id))->assertOk();
        $this->assertSame(1, $m->fresh()->download_count);
    }

    public function test_cover_uses_cover_disk_setting_and_its_public_url(): void
    {
        config(['simpil.cover_disk' => 's3_public', 'filesystems.disks.s3_public.url' => 'https://cdn.example.net']);
        Storage::fake('s3_public', ['url' => 'https://cdn.example.net']);

        $issue = app(IssueService::class)->createIssue(
            ['volume' => 1, 'number' => 1, 'year' => 2026],
            UploadedFile::fake()->image('c.jpg', 400, 600)
        );

        Storage::disk('s3_public')->assertExists($issue->cover_image);
        $this->assertStringStartsWith('https://cdn.example.net/issues/covers/', $issue->cover_url);

        app(IssueService::class)->updateIssue($issue, ['volume' => 1, 'number' => 1, 'year' => 2026], UploadedFile::fake()->image('d.png', 400, 600));
        $this->assertCount(1, Storage::disk('s3_public')->allFiles('issues/covers')); // sampul lama terhapus
    }

    public function test_cover_upload_does_not_send_per_object_acl(): void
    {
        // Bucket S3 modern menonaktifkan ACL: store tidak boleh meminta visibility/ACL 'public'.
        $source = file_get_contents(app_path('Services/Files/CoverImageStore.php'));
        $this->assertStringNotContainsString("'visibility'", $source);
        $this->assertSame('public', config('simpil.cover_disk')); // default lokal tetap aman
        $this->assertSame(CoverImageStore::DIRECTORY, 'issues/covers');
    }

    public function test_s3_disks_throw_on_failure_instead_of_silently_returning_false(): void
    {
        foreach (['s3', 's3_public'] as $disk) {
            $this->assertTrue(config("filesystems.disks.{$disk}.throw"), "Disk {$disk} harus melempar exception saat gagal");
            $this->assertSame('s3', config("filesystems.disks.{$disk}.driver"));
        }
    }

    public function test_emails_are_queued_not_sent_inline(): void
    {
        $this->assertTrue(is_subclass_of(QueuedVerifyEmail::class, ShouldQueue::class));

        Notification::fake();
        $user = $this->author();
        $user->sendEmailVerificationNotification();
        Notification::assertSentTo($user, QueuedVerifyEmail::class);

        $this->assertSame('redis', config('queue.connections.redis.driver'));
        $this->assertTrue(config('queue.connections.redis.after_commit'));
    }

    public function test_no_application_code_hardcodes_a_disk_name(): void
    {
        foreach (['Services', 'Http'] as $dir) {
            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(app_path($dir)));
            foreach ($it as $file) {
                if (! $file->isFile() || $file->getExtension() !== 'php') {
                    continue;
                }
                $code = file_get_contents($file->getPathname());
                $this->assertDoesNotMatchRegularExpression(
                    "/Storage::disk\(\s*'(public|local|s3|s3_public)'\s*\)/",
                    $code,
                    $file->getFilename() . ' menulis nama disk langsung; gunakan ManuscriptFileStore / CoverImageStore'
                );
            }
        }
    }
}
