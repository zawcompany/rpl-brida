<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manuscripts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('research_field_id')->nullable()->constrained('research_fields')->nullOnDelete();
            $table->string('title');
            $table->text('abstract')->nullable();
            $table->string('keywords')->nullable();        // CSV atau JSON
            $table->string('file_path')->nullable();       // Path PDF di storage
            $table->string('file_original_name')->nullable();
            $table->enum('status', [
                'pending',
                'pemeriksaan_awal',
                'ditinjau',
                'menunggu_keputusan',
                'disetujui',
                'ditolak',
                'diterbitkan',
            ])->default('pending');
            $table->text('editor_note')->nullable();       // Catatan dari editor ke reviewer
            $table->timestamp('submitted_at')->nullable(); // Waktu submit resmi
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manuscripts');
    }
};
