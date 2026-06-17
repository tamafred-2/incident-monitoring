<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('password_reset_requests') || Schema::hasColumn('password_reset_requests', 'expires_at')) {
            return;
        }

        Schema::table('password_reset_requests', function (Blueprint $table) {
            $table->timestamp('expires_at')->nullable()->after('resolved_at');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('password_reset_requests') || !Schema::hasColumn('password_reset_requests', 'expires_at')) {
            return;
        }

        Schema::table('password_reset_requests', function (Blueprint $table) {
            $table->dropColumn('expires_at');
        });
    }
};
