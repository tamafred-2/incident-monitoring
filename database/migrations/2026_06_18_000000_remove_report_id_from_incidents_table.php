<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('incidents') || !Schema::hasColumn('incidents', 'report_id')) {
            return;
        }

        Schema::table('incidents', function (Blueprint $table) {
            $table->dropUnique('incidents_report_id_unique');
            $table->dropColumn('report_id');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('incidents') || Schema::hasColumn('incidents', 'report_id')) {
            return;
        }

        Schema::table('incidents', function (Blueprint $table) {
            $table->string('report_id', 20)->nullable()->unique()->after('incident_id');
        });
    }
};
