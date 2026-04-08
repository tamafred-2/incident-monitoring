<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('incidents')) {
            return;
        }

        Schema::table('incidents', function (Blueprint $table) {
            if (!Schema::hasColumn('incidents', 'reported_at')) {
                $table->dateTime('reported_at')->nullable()->after('incident_date');
            }

            if (!Schema::hasColumn('incidents', 'resolved_at')) {
                $table->dateTime('resolved_at')->nullable()->after('reported_at');
            }
        });

        DB::table('incidents')
            ->whereNull('reported_at')
            ->update([
                'reported_at' => DB::raw('COALESCE(created_at, incident_date)'),
            ]);

        DB::table('incidents')
            ->whereIn('status', ['Resolved', 'Closed'])
            ->whereNull('resolved_at')
            ->update([
                'resolved_at' => DB::raw('COALESCE(reported_at, incident_date, created_at)'),
            ]);
    }

    public function down(): void
    {
        if (!Schema::hasTable('incidents')) {
            return;
        }

        Schema::table('incidents', function (Blueprint $table) {
            if (Schema::hasColumn('incidents', 'resolved_at')) {
                $table->dropColumn('resolved_at');
            }

            if (Schema::hasColumn('incidents', 'reported_at')) {
                $table->dropColumn('reported_at');
            }
        });
    }
};
