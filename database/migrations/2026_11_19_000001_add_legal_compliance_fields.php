<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kepatuhan hukum: persetujuan privasi (UU PDP), e-ISSN & lisensi jurnal (Juknis akreditasi, UU Hak Cipta),
 * serta pernyataan penulis saat mengajukan naskah (keaslian, hak cipta, konflik kepentingan).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('privacy_consented_at')->nullable();
            $table->string('consent_version', 20)->nullable();
        });

        Schema::table('journals', function (Blueprint $table) {
            $table->string('e_issn', 9)->nullable();
            $table->string('license', 30)->default('CC-BY-4.0');
        });

        Schema::table('manuscripts', function (Blueprint $table) {
            $table->string('license', 30)->nullable();                 // salinan lisensi jurnal saat naskah diajukan
            $table->timestamp('declarations_accepted_at')->nullable(); // keaslian + hak cipta + etika disetujui
            $table->boolean('conflict_of_interest')->default(false);
            $table->text('conflict_note')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('manuscripts', function (Blueprint $table) {
            $table->dropColumn(['license', 'declarations_accepted_at', 'conflict_of_interest', 'conflict_note']);
        });

        Schema::table('journals', function (Blueprint $table) {
            $table->dropColumn(['e_issn', 'license']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['privacy_consented_at', 'consent_version']);
        });
    }
};
