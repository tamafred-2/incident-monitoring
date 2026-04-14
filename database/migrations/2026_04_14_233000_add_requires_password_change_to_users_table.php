<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('users') || Schema::hasColumn('users', 'requires_password_change')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('requires_password_change')->default(false)->after('password');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('users') || !Schema::hasColumn('users', 'requires_password_change')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('requires_password_change');
        });
    }
};
