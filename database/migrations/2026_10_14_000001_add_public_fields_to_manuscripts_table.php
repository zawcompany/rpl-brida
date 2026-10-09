<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Penghitung unduhan publik dan DOI artikel terbit (opsional). */
    public function up(): void
    {
        Schema::table('manuscripts', function (Blueprint $table) {
            $table->unsignedInteger('download_count')->default(0)->after('published_at');
            $table->string('doi')->nullable()->after('download_count');

            // katalog publik: filter status + urut tanggal terbit
            $table->index(['status', 'published_at'], 'manuscripts_public_idx');
        });
    }

    public function down(): void
    {
        Schema::table('manuscripts', function (Blueprint $table) {
            $table->dropIndex('manuscripts_public_idx');
            $table->dropColumn(['download_count', 'doi']);
        });
    }
};
