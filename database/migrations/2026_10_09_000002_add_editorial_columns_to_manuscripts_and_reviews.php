<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('manuscripts', function (Blueprint $table) {
            $table->enum('status', [
                'pending',
                'pemeriksaan_awal',
                'ditinjau',
                'menunggu_keputusan',
                'revisi',
                'disetujui',
                'ditolak',
                'diterbitkan',
            ])->default('pending')->change();

            $table->text('editorial_note')->nullable();               // Catatan keputusan akhir editor
            $table->timestamp('decided_at')->nullable();
            $table->string('revision_file_path')->nullable();         // Berkas revisi dari author
            $table->string('revision_original_name')->nullable();
            $table->string('final_file_path')->nullable();            // PDF camera-ready dari editor
            $table->string('final_original_name')->nullable();
            $table->foreignId('issue_id')->nullable()->constrained('issues')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->timestamp('reminded_at')->nullable();
            $table->unsignedSmallInteger('reminder_count')->default(0);
            $table->timestamp('superseded_at')->nullable();           // Terisi saat reviewer diganti editor
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropColumn(['reminded_at', 'reminder_count', 'superseded_at']);
        });

        Schema::table('manuscripts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('issue_id');
            $table->dropColumn([
                'editorial_note', 'decided_at', 'revision_file_path', 'revision_original_name',
                'final_file_path', 'final_original_name', 'published_at',
            ]);
        });
    }
};
