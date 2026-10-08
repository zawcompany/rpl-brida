<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->dropLegacyTables();

        Schema::create('issues', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('volume');
            $table->unsignedSmallInteger('number');
            $table->unsignedSmallInteger('year');
            $table->string('title')->nullable();
            $table->enum('status', ['draft', 'published'])->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['volume', 'number', 'year']);
        });
    }

    /**
     * Sisa skema iterasi lama (issues dengan created_by + pivot issue_manuscripts) yang file
     * migration-nya sudah tidak ada. Dibuang HANYA bila kosong; bila berisi data, migrasi berhenti.
     */
    private function dropLegacyTables(): void
    {
        foreach (['issue_manuscripts', 'issues'] as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            if (DB::table($table)->exists()) {
                throw new RuntimeException("Tabel lama '{$table}' berisi data; pindahkan/hapus manual sebelum migrasi.");
            }
        }

        Schema::dropIfExists('issue_manuscripts');
        Schema::dropIfExists('issues');
    }

    public function down(): void
    {
        Schema::dropIfExists('issues');
    }
};
