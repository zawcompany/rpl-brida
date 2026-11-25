<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pengaduan dan keberatan pengguna (UU 25/2009 Pasal 36: sarana pengaduan, pengelolaan dalam batas waktu, tindak lanjut).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('complaints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();     // pengadu
            $table->foreignId('manuscript_id')->nullable()->constrained()->nullOnDelete(); // naskah terkait (opsional)
            $table->string('category', 30);
            $table->string('subject');
            $table->text('description');
            $table->string('status', 20)->default('diterima')->index();
            $table->text('response')->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complaints');
    }
};
