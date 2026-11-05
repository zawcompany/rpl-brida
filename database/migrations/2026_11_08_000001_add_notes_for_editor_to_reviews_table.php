<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Catatan rahasia reviewer yang hanya untuk editor (tidak pernah ditampilkan ke author). `comments` tetap untuk author. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->text('notes_for_editor')->nullable()->after('comments');
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropColumn('notes_for_editor');
        });
    }
};
