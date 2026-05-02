<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('visitor_requests') || Schema::hasColumn('visitor_requests', 'passenger_count')) {
            return;
        }

        Schema::table('visitor_requests', function (Blueprint $table) {
            $table->unsignedTinyInteger('passenger_count')->nullable();
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('visitor_requests') || !Schema::hasColumn('visitor_requests', 'passenger_count')) {
            return;
        }

        Schema::table('visitor_requests', function (Blueprint $table) {
            $table->dropColumn('passenger_count');
        });
    }
};

