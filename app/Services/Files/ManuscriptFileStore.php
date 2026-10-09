<?php

namespace App\Services\Files;

use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * ManuscriptFileStore — satu-satunya pintu akses penyimpanan dokumen.
 * Service lain tidak boleh menyebut nama disk; pindah local -> S3 cukup mengubah MANUSCRIPT_DISK.
 */
class ManuscriptFileStore
{
    public function disk(): FilesystemAdapter
    {
        return Storage::disk(config('simpil.manuscript_disk'));
    }

    /** Simpan dengan nama acak di $directory; mengembalikan path relatif. */
    public function put(UploadedFile $file, string $directory): string
    {
        return $file->store($directory, ['disk' => config('simpil.manuscript_disk')]);
    }

    public function delete(?string $path): void
    {
        if ($path) {
            $this->disk()->delete($path);
        }
    }

    public function exists(?string $path): bool
    {
        return $path !== null && $this->disk()->exists($path);
    }

    /** Respons terstream; inline untuk pratinjau PDF di browser, attachment untuk unduhan. */
    public function response(string $path, string $name, bool $inline): StreamedResponse
    {
        return $this->disk()->response($path, $name, [
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control'          => 'private, no-store',
        ], $inline ? 'inline' : 'attachment');
    }
}
