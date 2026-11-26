<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lampiran keputusan editorial untuk penulis (opsional): berkas yang disiapkan editor sendiri, misalnya rangkuman
 * catatan perbaikan dari para reviewer yang sudah dibersihkan dari identitas reviewer. Hanya editor dan penulis
 * naskah yang dapat membukanya; berkas catatan milik reviewer tidak pernah diteruskan langsung.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('manuscripts', function (Blueprint $table): void {
            $table->string('decision_file_path')->nullable()->after('editorial_note');
            $table->string('decision_file_name')->nullable()->after('decision_file_path');
        });
    }

    public function down(): void
    {
        Schema::table('manuscripts', function (Blueprint $table): void {
            $table->dropColumn(['decision_file_path', 'decision_file_name']);
        });
    }
};
