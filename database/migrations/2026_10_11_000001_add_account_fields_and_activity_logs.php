<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('institution')->nullable()->after('role');
            $table->string('phone', 30)->nullable()->after('institution');
            $table->boolean('is_active')->default(true)->after('phone'); // false = suspend
        });

        // Jejak audit sistem (registrasi, perubahan role/status, reset password, dst.)
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // pelaku
            $table->string('action', 60)->index();
            $table->string('description');
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['institution', 'phone', 'is_active']);
        });
    }
};
