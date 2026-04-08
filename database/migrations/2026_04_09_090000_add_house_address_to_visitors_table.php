<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('visitors') || Schema::hasColumn('visitors', 'house_address_or_unit')) {
            return;
        }

        Schema::table('visitors', function (Blueprint $table) {
            $table->string('house_address_or_unit', 120)->nullable()->after('host_employee');
            $table->index('house_address_or_unit');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('visitors') || !Schema::hasColumn('visitors', 'house_address_or_unit')) {
            return;
        }

        Schema::table('visitors', function (Blueprint $table) {
            $table->dropIndex(['house_address_or_unit']);
            $table->dropColumn('house_address_or_unit');
        });
    }
};
