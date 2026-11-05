<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** Cover dan bidang jurnal untuk tampilan publik. Jurnal bawaan diberi bidang awal agar kartunya tidak kosong. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journals', function (Blueprint $table) {
            $table->string('field', 120)->nullable()->after('abbreviation');      // bidang, mis. "Kesehatan Masyarakat"
            $table->string('cover_image')->nullable()->after('description');      // path relatif di disk cover
        });

        DB::table('journals')
            ->where('slug', 'simpil-brida')
            ->whereNull('field')
            ->update(['field' => 'Riset dan Inovasi Daerah']);
    }

    public function down(): void
    {
        Schema::table('journals', function (Blueprint $table) {
            $table->dropColumn(['field', 'cover_image']);
        });
    }
};
