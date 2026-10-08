<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('research_fields', function (Blueprint $table) {
            $table->id();
            $table->string('name');           // e.g. "Ilmu Komputer"
            $table->string('slug')->unique(); // e.g. "ilmu-komputer"
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('research_fields');
    }
};
