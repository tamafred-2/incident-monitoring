<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('users') || !Schema::hasColumn('users', 'resident_id')) {
            return;
        }

        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('DROP INDEX IF EXISTS users_resident_id_unique');
            DB::statement('CREATE UNIQUE INDEX users_resident_id_active_unique ON users (resident_id) WHERE deleted_at IS NULL');

            return;
        }

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            try {
                DB::statement('ALTER TABLE users DROP INDEX users_resident_id_unique');
            } catch (\Throwable $e) {
                // Index may already be missing in some environments.
            }

            try {
                DB::statement('ALTER TABLE users DROP INDEX users_resident_id_index');
            } catch (\Throwable $e) {
                // Index may already be missing in some environments.
            }

            DB::statement('CREATE INDEX users_resident_id_index ON users (resident_id)');
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('users') || !Schema::hasColumn('users', 'resident_id')) {
            return;
        }

        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('DROP INDEX IF EXISTS users_resident_id_active_unique');
            DB::statement('CREATE UNIQUE INDEX users_resident_id_unique ON users (resident_id)');

            return;
        }

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            try {
                DB::statement('ALTER TABLE users DROP INDEX users_resident_id_index');
            } catch (\Throwable $e) {
                // Index may already be missing in some environments.
            }

            DB::statement('ALTER TABLE users ADD UNIQUE users_resident_id_unique (resident_id)');
            DB::statement('CREATE INDEX users_resident_id_index ON users (resident_id)');
        }
    }
};
