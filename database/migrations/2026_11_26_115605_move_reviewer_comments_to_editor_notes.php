<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Reviewer hanya berkomunikasi dengan editor, tidak pernah langsung dengan penulis.
 *
 * Kolom `comments` dulu berisi komentar reviewer untuk penulis (dan alasan menolak penugasan). Komentar pada review
 * yang sudah dikirim digabungkan ke `notes_for_editor` supaya tidak hilang dan tetap dibaca editor, lalu kolomnya
 * diganti nama menjadi `decline_reason` karena hanya alasan penolakan penugasan yang tersisa di sana.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('reviews')
            ->where('status', '!=', 'ditolak_reviewer')
            ->whereNotNull('comments')
            ->where('comments', '!=', '')
            ->orderBy('id')
            ->each(function (object $review): void {
                $notes = trim((string) $review->notes_for_editor);

                DB::table('reviews')->where('id', $review->id)->update([
                    'notes_for_editor' => $notes === '' ? $review->comments : $review->comments."\n\n".$notes,
                    'comments' => null,
                ]);
            });

        DB::table('reviews')->where('status', '!=', 'ditolak_reviewer')->update(['comments' => null]);

        Schema::table('reviews', function (Blueprint $table): void {
            $table->renameColumn('comments', 'decline_reason');
        });
    }

    /** Hanya nama kolom yang dikembalikan; teks yang sudah digabung ke catatan editor tidak dipisah lagi. */
    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table): void {
            $table->renameColumn('decline_reason', 'comments');
        });
    }
};
