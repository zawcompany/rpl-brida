<?php

namespace App\Http\Controllers;

use App\Models\Manuscript;
use App\Services\Files\ManuscriptFileStore;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Satu-satunya jalur akses berkas naskah/revisi/final. Berkas disimpan di disk privat,
 * tidak pernah menjadi public asset; setiap permintaan melewati auth + ManuscriptPolicy.
 */
class ManuscriptFileController extends Controller
{
    /** type di URL => [kolom path, kolom nama asli] */
    private const TYPES = [
        'original' => ['file_path', 'file_original_name'],
        'revision' => ['revision_file_path', 'revision_original_name'],
        'final'    => ['final_file_path', 'final_original_name'],
    ];

    public function __construct(private readonly ManuscriptFileStore $files)
    {
    }

    public function show(Manuscript $manuscript, string $type): StreamedResponse
    {
        abort_unless(isset(self::TYPES[$type]), 404);
        Gate::authorize('viewFile', $manuscript);

        [$pathColumn, $nameColumn] = self::TYPES[$type];
        $path = $manuscript->{$pathColumn};

        abort_unless($this->files->exists($path), 404, 'Berkas tidak ditemukan.');

        return $this->files->response($path, $manuscript->{$nameColumn} ?: basename($path), inline: true);
    }
}
