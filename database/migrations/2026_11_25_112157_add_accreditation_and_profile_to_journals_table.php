<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Akreditasi jurnal (Permendiktisaintek 9/2026 Pasal 10: peringkat dan masa berlaku wajib dicantumkan di laman jurnal)
 * dan kelengkapan laman jurnal (Pasal 6 ayat (2) huruf e): fokus & ruang lingkup serta pedoman penulis.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journals', function (Blueprint $table) {
            $table->unsignedTinyInteger('accreditation_rank')->nullable();      // peringkat 1–4; null = belum terakreditasi
            $table->string('accreditation_decree', 120)->nullable();             // nomor SK penetapan akreditasi
            $table->date('accreditation_valid_until')->nullable();
            $table->timestamp('accreditation_reminded_at')->nullable();          // pengingat akreditasi ulang terakhir dikirim
            $table->text('focus_scope')->nullable();
            $table->text('author_guidelines')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('journals', function (Blueprint $table) {
            $table->dropColumn([
                'accreditation_rank', 'accreditation_decree', 'accreditation_valid_until',
                'accreditation_reminded_at', 'focus_scope', 'author_guidelines',
            ]);
        });
    }
};
