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
        Schema::table('subdivisions', function (Blueprint $table) {
            if (!Schema::hasColumn('subdivisions', 'boundary')) {
                $table->text('boundary')->nullable()->after('longitude');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subdivisions', function (Blueprint $table) {
            if (Schema::hasColumn('subdivisions', 'boundary')) {
                $table->dropColumn('boundary');
            }
        });
    }
};
