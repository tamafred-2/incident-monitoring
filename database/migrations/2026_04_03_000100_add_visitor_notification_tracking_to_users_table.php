<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'visitor_notifications_read_at')) {
                $table->timestamp('visitor_notifications_read_at')->nullable()->after('subdivision_id');
            }

            if (!Schema::hasColumn('users', 'visitor_notifications_cleared_at')) {
                $table->timestamp('visitor_notifications_cleared_at')->nullable()->after('visitor_notifications_read_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'visitor_notifications_cleared_at')) {
                $table->dropColumn('visitor_notifications_cleared_at');
            }

            if (Schema::hasColumn('users', 'visitor_notifications_read_at')) {
                $table->dropColumn('visitor_notifications_read_at');
            }
        });
    }
};
