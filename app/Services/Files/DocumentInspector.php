<?php

namespace App\Services\Files;

use Illuminate\Http\UploadedFile;
use ZipArchive;

/**
 * DocumentInspector — pemeriksaan server-side berkas unggahan (SRS NF-04).
 * Tidak mempercayai input klien: yang diperiksa adalah ekstensi asli, MIME hasil deteksi isi (finfo),
 * dan struktur/magic bytes. Reusable untuk semua form unggah dokumen.
 *
 * @return string|null inspect() mengembalikan pesan alasan penolakan, atau null bila berkas aman.
 */
class DocumentInspector
{
    /**
     * @param string[] $allowed kunci jenis dokumen dari config('simpil.documents'), mis. ['pdf', 'docx']
     */
    public function inspect(UploadedFile $file, array $allowed): ?string
    {
        if (! $file->isValid() || ! is_readable($file->getRealPath())) {
            return 'Berkas gagal diunggah.';
        }

        $types = array_intersect_key(config('simpil.documents'), array_flip($allowed));
        $labels = implode(' atau ', array_column($types, 'label'));

        $extension = strtolower($file->getClientOriginalExtension());
        if (! isset($types[$extension])) {
            return "Berkas harus berformat {$labels}.";
        }

        if ($this->hasBlockedSegment($file->getClientOriginalName())) {
            return 'Nama berkas tidak diperbolehkan (ekstensi ganda terdeteksi).';
        }

        // MIME dideteksi dari isi berkas, bukan dari header yang dikirim klien.
        if (! in_array($file->getMimeType(), $types[$extension]['mimes'], true)) {
            return "Isi berkas tidak sesuai dengan format {$types[$extension]['label']}.";
        }

        $valid = match ($extension) {
            'pdf'   => $this->looksLikePdf($file->getRealPath()),
            'docx'  => $this->looksLikeDocx($file->getRealPath()),
            default => false,
        };

        return $valid ? null : "Isi berkas bukan {$types[$extension]['label']} yang valid.";
    }

    /** Tolak bila ada segmen nama (selain ekstensi terakhir) yang berupa ekstensi berbahaya. */
    private function hasBlockedSegment(string $name): bool
    {
        $segments = array_map('strtolower', explode('.', basename($name)));
        array_shift($segments); // nama dasar bukan ekstensi

        return (bool) array_intersect($segments, config('simpil.blocked_extensions'));
    }

    private function looksLikePdf(string $path): bool
    {
        $handle = fopen($path, 'rb');
        $head = $handle ? fread($handle, 1024) : '';
        $handle && fclose($handle);

        return str_contains((string) $head, '%PDF-');
    }

    /** DOCX = ZIP berisi [Content_Types].xml dan word/document.xml, tanpa makro VBA. */
    private function looksLikeDocx(string $path): bool
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            return false;
        }

        $valid = $zip->locateName('[Content_Types].xml') !== false
            && $zip->locateName('word/document.xml') !== false
            && $zip->locateName('word/vbaProject.bin') === false;

        $zip->close();

        return $valid;
    }
}
