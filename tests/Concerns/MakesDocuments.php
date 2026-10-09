<?php

namespace Tests\Concerns;

use Illuminate\Http\UploadedFile;
use ZipArchive;

/** Pembuat berkas uji dengan ISI yang valid (lolos DocumentInspector), bukan sekadar nama palsu. */
trait MakesDocuments
{
    protected function pdf(string $name = 'naskah.pdf'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, "%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\ntrailer<<>>\n%%EOF");
    }

    protected function docx(string $name = 'naskah.docx', bool $withMacro = false): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'docx');
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', '<Types/>');
        $zip->addFromString('word/document.xml', '<w:document/>');
        if ($withMacro) {
            $zip->addFromString('word/vbaProject.bin', 'macro');
        }
        $zip->close();

        return new UploadedFile($path, $name, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', null, true);
    }
}
