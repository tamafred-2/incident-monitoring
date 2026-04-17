<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        if (!Schema::hasColumn('users', 'resident_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unsignedInteger('resident_id')->nullable()->after('subdivision_id');
                $table->unique('resident_id');
                $table->index('resident_id');
            });
        }

        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            $this->rebuildSqliteUsersTable(true);

            return;
        }

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE users MODIFY role ENUM('admin','security','staff','resident') NOT NULL");
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }

        if (Schema::hasColumn('users', 'resident_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropUnique(['resident_id']);
                $table->dropIndex(['resident_id']);
                $table->dropColumn('resident_id');
            });
        }

        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            $this->rebuildSqliteUsersTable(false);

            return;
        }

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE users MODIFY role ENUM('admin','security','staff') NOT NULL");
        }
    }

    private function rebuildSqliteUsersTable(bool $includeResidentRole): void
    {
        $roleValues = $includeResidentRole
            ? "'admin','security','staff','resident'"
            : "'admin','security','staff'";

        DB::statement('PRAGMA foreign_keys = OFF');
        DB::statement('ALTER TABLE users RENAME TO users_old');

        DB::statement("
            CREATE TABLE users (
                user_id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                full_name VARCHAR(100) NOT NULL,
                email VARCHAR(100) NOT NULL,
                password VARCHAR(255) NOT NULL,
                role VARCHAR NOT NULL CHECK (role IN ({$roleValues})),
                subdivision_id INTEGER NULL,
                resident_id INTEGER NULL,
                created_at DATETIME NULL,
                surname VARCHAR(100) NULL,
                first_name VARCHAR(100) NULL,
                middle_name VARCHAR(100) NULL,
                extension VARCHAR(20) NULL,
                deleted_at DATETIME NULL,
                visitor_notifications_read_at DATETIME NULL,
                visitor_notifications_cleared_at DATETIME NULL,
                visitor_notification_read_keys TEXT NULL
            )
        ");

        DB::statement("
            INSERT INTO users (
                user_id, full_name, email, password, role, subdivision_id, resident_id, created_at,
                surname, first_name, middle_name, extension, deleted_at,
                visitor_notifications_read_at, visitor_notifications_cleared_at, visitor_notification_read_keys
            )
            SELECT
                user_id, full_name, email, password, role, subdivision_id, resident_id, created_at,
                surname, first_name, middle_name, extension, deleted_at,
                visitor_notifications_read_at, visitor_notifications_cleared_at, visitor_notification_read_keys
            FROM users_old
        ");

        DB::statement('DROP TABLE users_old');
        DB::statement('CREATE UNIQUE INDEX users_email_unique ON users (email)');
        DB::statement('CREATE UNIQUE INDEX users_resident_id_unique ON users (resident_id)');
        DB::statement('CREATE INDEX users_email_index ON users (email)');
        DB::statement('CREATE INDEX users_role_index ON users (role)');
        DB::statement('CREATE INDEX users_subdivision_id_index ON users (subdivision_id)');
        DB::statement('CREATE INDEX users_resident_id_index ON users (resident_id)');
        DB::statement('CREATE INDEX users_deleted_at_index ON users (deleted_at)');
        DB::statement('PRAGMA foreign_keys = ON');
    }
};
