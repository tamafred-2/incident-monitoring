<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visitors', function (Blueprint $table) {
            if (!Schema::hasColumn('visitors', 'vehicle_type')) {
                $table->string('vehicle_type', 30)->nullable()->after('passenger_count');
            }
            if (!Schema::hasColumn('visitors', 'vehicle_color')) {
                $table->string('vehicle_color', 30)->nullable()->after('vehicle_type');
            }
        });

        Schema::table('visitor_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('visitor_requests', 'vehicle_type')) {
                $table->string('vehicle_type', 30)->nullable()->after('passenger_count');
            }
            if (!Schema::hasColumn('visitor_requests', 'vehicle_color')) {
                $table->string('vehicle_color', 30)->nullable()->after('vehicle_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('visitor_requests', function (Blueprint $table) {
            $columns = [];
            if (Schema::hasColumn('visitor_requests', 'vehicle_type')) {
                $columns[] = 'vehicle_type';
            }
            if (Schema::hasColumn('visitor_requests', 'vehicle_color')) {
                $columns[] = 'vehicle_color';
            }
            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });

        Schema::table('visitors', function (Blueprint $table) {
            $columns = [];
            if (Schema::hasColumn('visitors', 'vehicle_type')) {
                $columns[] = 'vehicle_type';
            }
            if (Schema::hasColumn('visitors', 'vehicle_color')) {
                $columns[] = 'vehicle_color';
            }
            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
