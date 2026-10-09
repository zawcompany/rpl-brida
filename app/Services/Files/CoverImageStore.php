<?php

namespace App\Services\Files;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * CoverImageStore — penyimpanan gambar sampul (aset publik, bukan dokumen naskah).
 * Disk dari config('simpil.cover_disk') (COVER_DISK di .env): 'public' di lokal, 's3' di AWS.
 * Pemanggil tidak pernah menyebut nama disk, jadi pindah ke S3 tidak mengubah logika bisnis.
 * Tidak memakai ACL per-objek: di S3 keterbacaan publik diatur oleh CloudFront/kebijakan bucket.
 */
class CoverImageStore
{
    public const DIRECTORY = 'issues/covers';
    public const PLACEHOLDER = 'images/issue-cover-placeholder.svg';

    /** Simpan dengan nama acak; mengembalikan path relatif untuk kolom cover_image. */
    public function put(UploadedFile $image): string
    {
        return $image->store(self::DIRECTORY, ['disk' => $this->diskName()]);
    }

    public function delete(?string $path): void
    {
        if ($path) {
            Storage::disk($this->diskName())->delete($path);
        }
    }

    /** URL gambar, atau placeholder bawaan sistem bila belum ada sampul. */
    public function url(?string $path): string
    {
        return $path ? Storage::disk($this->diskName())->url($path) : asset(self::PLACEHOLDER);
    }

    private function diskName(): string
    {
        return config('simpil.cover_disk');
    }
}
