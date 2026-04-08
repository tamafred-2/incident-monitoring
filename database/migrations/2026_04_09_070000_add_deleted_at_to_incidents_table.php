<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('incidents') || Schema::hasColumn('incidents', 'deleted_at')) {
            return;
        }

        Schema::table('incidents', function (Blueprint $table) {
            $table->softDeletes()->after('created_at');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('incidents') || !Schema::hasColumn('incidents', 'deleted_at')) {
            return;
        }

        Schema::table('incidents', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
