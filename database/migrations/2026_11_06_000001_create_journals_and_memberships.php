<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Platform multijurnal: tabel jurnal, keanggotaan (peran per jurnal), dan penanda jurnal pada naskah & edisi.
 * Data lama dimasukkan ke satu jurnal bawaan (SIMPIL BRIDA) supaya tidak ada yang hilang.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journals', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('abbreviation', 30)->nullable();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('template_path')->nullable();           // berkas template (disk privat)
            $table->string('template_original_name')->nullable();
            $table->unsignedSmallInteger('template_version')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Keanggotaan: satu orang bisa menjadi Author di jurnal A dan Editor di jurnal B.
        Schema::create('journal_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 20);                            // Author | Editor | Reviewer
            $table->timestamps();

            $table->unique(['journal_id', 'user_id', 'role']);
            $table->index(['user_id', 'role']);
        });

        Schema::table('manuscripts', function (Blueprint $table) {
            $table->foreignId('journal_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });

        Schema::table('issues', function (Blueprint $table) {
            $table->foreignId('journal_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->dropUnique(['volume', 'number', 'year']);
            $table->unique(['journal_id', 'volume', 'number', 'year']);
        });

        $this->backfillDefaultJournal();
    }

    public function down(): void
    {
        Schema::table('issues', function (Blueprint $table) {
            // Di MySQL kunci asing memakai indeks unik gabungan; lepaskan kunci asing dulu sebelum indeksnya.
            $table->dropForeign(['journal_id']);
            $table->dropUnique(['journal_id', 'volume', 'number', 'year']);
            $table->unique(['volume', 'number', 'year']);
            $table->dropColumn('journal_id');
        });

        Schema::table('manuscripts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('journal_id');
        });

        Schema::dropIfExists('journal_user');
        Schema::dropIfExists('journals');
    }

    /** Satu jurnal bawaan; semua naskah, edisi, dan pengguna yang sudah ada menjadi anggotanya. */
    private function backfillDefaultJournal(): void
    {
        $now = now();

        $journalId = DB::table('journals')->insertGetId([
            'name'             => config('simpil.journal.name', 'SIMPIL BRIDA Kota Makassar'),
            'abbreviation'     => 'SIMPIL',
            'slug'             => 'simpil-brida',
            'description'      => 'Jurnal bawaan platform. Naskah dan edisi yang sudah ada sebelum fitur multijurnal masuk ke sini.',
            'template_version' => 0, // memakai template bawaan aplikasi sampai diunggah ulang
            'is_active'        => true,
            'created_at'       => $now,
            'updated_at'       => $now,
        ]);

        DB::table('manuscripts')->whereNull('journal_id')->update(['journal_id' => $journalId]);
        DB::table('issues')->whereNull('journal_id')->update(['journal_id' => $journalId]);

        foreach (['Author', 'Editor', 'Reviewer'] as $role) {
            DB::table('users')->where('role', $role)->orderBy('id')->each(function ($user) use ($journalId, $role, $now) {
                DB::table('journal_user')->insertOrIgnore([
                    'journal_id' => $journalId,
                    'user_id'    => $user->id,
                    'role'       => $role,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            });
        }
    }
};
