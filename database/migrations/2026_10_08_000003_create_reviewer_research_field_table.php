<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pivot table: many-to-many antara users(reviewer) dan research_fields.
     * Digunakan untuk mencocokkan kualifikasi reviewer dengan bidang naskah.
     */
    public function up(): void
    {
        Schema::create('reviewer_research_field', function (Blueprint $table) {
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('research_field_id')->constrained('research_fields')->cascadeOnDelete();
            $table->primary(['user_id', 'research_field_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviewer_research_field');
    }
};
