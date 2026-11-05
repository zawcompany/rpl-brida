<?php

namespace App\Services\Files;

use App\Models\Journal;
use Illuminate\Http\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

/**
 * JournalTemplateStore — template naskah per jurnal. Berkas disimpan di disk privat yang sama dengan
 * naskah (lokal atau S3) dan hanya keluar lewat rute yang memeriksa keanggotaan. Jurnal bawaan
 * memakai template bawaan aplikasi sampai template baru diunggah.
 */
class JournalTemplateStore
{
    public function __construct(private readonly ManuscriptFileStore $files)
    {
    }

    /** Ganti template jurnal; versi naik satu dan berkas lama dihapus setelah database berhasil diperbarui. */
    public function replace(Journal $journal, UploadedFile $file): Journal
    {
        $old  = $journal->template_path;
        $path = $this->files->put($file, "templates/{$journal->id}");

        try {
            $journal->update([
                'template_path'          => $path,
                'template_original_name' => $file->getClientOriginalName(),
                'template_version'       => $journal->template_version + 1,
            ]);
        } catch (\Throwable $e) {
            $this->files->delete($path);
            throw $e;
        }

        $this->files->delete($old);

        return $journal;
    }

    /** Unduhan template. 404 bila jurnal belum punya template. */
    public function download(Journal $journal): Response
    {
        if ($journal->template_path !== null) {
            abort_unless($this->files->exists($journal->template_path), 404, 'Berkas template tidak ditemukan.');

            return $this->files->response(
                $journal->template_path,
                $journal->template_original_name ?: "Template-{$journal->slug}.docx",
                inline: false
            );
        }

        abort_unless($journal->usesBundledTemplate(), 404, 'Jurnal ini belum memiliki template.');

        return response()->download(resource_path('templates/template-naskah.docx'), "Template-{$journal->slug}.docx");
    }
}
