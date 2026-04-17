<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            if (!Schema::hasColumn('incidents', 'verified_by_staff_id')) {
                $table->unsignedInteger('verified_by_staff_id')->nullable()->after('verified_at');
            }
            if (!Schema::hasColumn('incidents', 'verified_on_site_at')) {
                $table->dateTime('verified_on_site_at')->nullable()->after('verified_by_staff_id');
            }

            $table->index('verified_by_staff_id');
        });
    }

    public function down(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            if (Schema::hasColumn('incidents', 'verified_on_site_at')) {
                $table->dropColumn('verified_on_site_at');
            }
            if (Schema::hasColumn('incidents', 'verified_by_staff_id')) {
                $table->dropColumn('verified_by_staff_id');
            }
        });
    }
};
