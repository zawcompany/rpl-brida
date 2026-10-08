<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manuscript_id')->constrained('manuscripts')->cascadeOnDelete();
            $table->foreignId('reviewer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('assigned_by')->constrained('users'); // editor yang menugaskan
            $table->text('comments')->nullable();
            $table->enum('recommendation', [
                'diterima',
                'revisi_minor',
                'revisi_mayor',
                'ditolak',
            ])->nullable();
            $table->enum('status', [
                'ditugaskan',
                'diterima',
                'selesai',
                'ditolak_reviewer',
            ])->default('ditugaskan');
            $table->timestamp('due_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
