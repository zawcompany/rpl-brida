<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('manuscripts', function (Blueprint $table) {
            $table->json('co_authors')->nullable()->after('keywords');                 // [{name, email}]
            $table->text('author_response')->nullable()->after('revision_original_name'); // surat tanggapan revisi
            $table->timestamp('revision_submitted_at')->nullable()->after('author_response');
        });
    }

    public function down(): void
    {
        Schema::table('manuscripts', function (Blueprint $table) {
            $table->dropColumn(['co_authors', 'author_response', 'revision_submitted_at']);
        });
    }
};
