<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('guard_devices')) {
            Schema::create('guard_devices', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users', 'user_id')->cascadeOnDelete();
                $table->string('token_hash', 64);
                $table->string('device_name', 150)->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->string('status')->default('pending'); // pending | approved
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamps();
                $table->unique(['token_hash', 'user_id']);
                $table->index(['user_id', 'status']);
            });
        }

        if (Schema::hasTable('subdivisions') && ! Schema::hasColumn('subdivisions', 'premises_radius_m')) {
            Schema::table('subdivisions', function (Blueprint $table) {
                $table->unsignedInteger('premises_radius_m')->nullable()->after('longitude');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('guard_devices');

        if (Schema::hasColumn('subdivisions', 'premises_radius_m')) {
            Schema::table('subdivisions', function (Blueprint $table) {
                $table->dropColumn('premises_radius_m');
            });
        }
    }
};
