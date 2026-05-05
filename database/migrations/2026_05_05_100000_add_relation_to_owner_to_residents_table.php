<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('residents') || Schema::hasColumn('residents', 'relation_to_owner')) {
            return;
        }

        Schema::table('residents', function (Blueprint $table) {
            $table->string('relation_to_owner', 50)->nullable()->after('address_or_unit');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('residents') || !Schema::hasColumn('residents', 'relation_to_owner')) {
            return;
        }

        Schema::table('residents', function (Blueprint $table) {
            $table->dropColumn('relation_to_owner');
        });
    }
};

