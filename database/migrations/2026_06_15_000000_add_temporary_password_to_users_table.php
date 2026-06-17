<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('users') || Schema::hasColumn('users', 'temporary_password')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('temporary_password')->nullable()->after('requires_password_change');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('users') || !Schema::hasColumn('users', 'temporary_password')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('temporary_password');
        });
    }
};
