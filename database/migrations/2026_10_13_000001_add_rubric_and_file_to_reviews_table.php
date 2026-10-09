<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rubrik penilaian tambahan (selevel dengan relevansi_topik & metodologi_penelitian)
     * dan lampiran catatan review dari reviewer (opsional).
     */
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->string('kebaruan', 20)->nullable()->after('metodologi_penelitian');
            $table->string('kualitas_penulisan', 20)->nullable()->after('kebaruan');
            $table->string('review_file_path')->nullable()->after('comments');
            $table->string('review_file_name')->nullable()->after('review_file_path');
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropColumn(['kebaruan', 'kualitas_penulisan', 'review_file_path', 'review_file_name']);
        });
    }
};
