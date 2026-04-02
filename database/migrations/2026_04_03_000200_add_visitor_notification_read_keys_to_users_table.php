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
        if (!Schema::hasTable('users') || Schema::hasColumn('users', 'visitor_notification_read_keys')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->json('visitor_notification_read_keys')->nullable()->after('visitor_notifications_cleared_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('users') || !Schema::hasColumn('users', 'visitor_notification_read_keys')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('visitor_notification_read_keys');
        });
    }
};
