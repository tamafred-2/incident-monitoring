<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('visitors') && !Schema::hasColumn('visitors', 'passenger_count')) {
            Schema::table('visitors', function (Blueprint $table) {
                $table->unsignedTinyInteger('passenger_count')->nullable()->after('plate_number');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('visitors') && Schema::hasColumn('visitors', 'passenger_count')) {
            Schema::table('visitors', function (Blueprint $table) {
                $table->dropColumn('passenger_count');
            });
        }
    }
};
