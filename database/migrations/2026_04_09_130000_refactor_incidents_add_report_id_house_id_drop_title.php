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

        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            $this->rebuildSqliteIncidentsTable(true);
            return;
        }

        Schema::table('incidents', function (Blueprint $table) {
            if (!Schema::hasColumn('incidents', 'report_id')) {
                $table->string('report_id', 20)->unique()->after('incident_id');
            }
            if (!Schema::hasColumn('incidents', 'house_id')) {
                $table->unsignedInteger('house_id')->nullable()->after('subdivision_id');
                $table->index('house_id');
            }
        });

        // Generate report_id for existing rows
        DB::table('incidents')->whereNull('report_id')->orWhere('report_id', '')->get()->each(function ($row) {
            DB::table('incidents')->where('incident_id', $row->incident_id)->update([
                'report_id' => strtoupper(str_pad(dechex((int) $row->incident_id), 8, '0', STR_PAD_LEFT)),
            ]);
        });

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE incidents DROP COLUMN title');
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('incidents')) {
            return;
        }

        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            $this->rebuildSqliteIncidentsTable(false);
            return;
        }

        Schema::table('incidents', function (Blueprint $table) {
            if (!Schema::hasColumn('incidents', 'title')) {
                $table->string('title', 150)->nullable()->after('report_id');
            }
            if (Schema::hasColumn('incidents', 'house_id')) {
                $table->dropIndex(['house_id']);
                $table->dropColumn('house_id');
            }
            if (Schema::hasColumn('incidents', 'report_id')) {
                $table->dropUnique(['report_id']);
                $table->dropColumn('report_id');
            }
        });
    }

    private function rebuildSqliteIncidentsTable(bool $newSchema): void
    {
        DB::statement('PRAGMA foreign_keys = OFF');
        DB::statement('ALTER TABLE incidents RENAME TO incidents_old');

        if ($newSchema) {
            DB::statement("
                CREATE TABLE incidents (
                    incident_id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                    report_id VARCHAR(20) NOT NULL DEFAULT '',
                    subdivision_id INTEGER NOT NULL,
                    house_id INTEGER NULL,
                    description TEXT NULL,
                    category VARCHAR(100) NULL,
                    location VARCHAR(150) NULL,
                    incident_date DATETIME NOT NULL,
                    reported_at DATETIME NULL,
                    resolved_at DATETIME NULL,
                    status VARCHAR NOT NULL CHECK (status IN ('Open','Under Investigation','Resolved','Closed')) DEFAULT 'Open',
                    proof_photo_path VARCHAR(255) NULL,
                    reported_by INTEGER NOT NULL,
                    assigned_to INTEGER NULL,
                    verified_resident_id INTEGER NULL,
                    verification_method VARCHAR(50) NULL,
                    verified_at DATETIME NULL,
                    created_at DATETIME NULL,
                    deleted_at DATETIME NULL
                )
            ");

            DB::statement("
                INSERT INTO incidents (
                    incident_id, report_id, subdivision_id, house_id, description, category, location,
                    incident_date, reported_at, resolved_at, status, proof_photo_path,
                    reported_by, assigned_to, verified_resident_id, verification_method, verified_at,
                    created_at, deleted_at
                )
                SELECT
                    incident_id,
                    upper(printf('%08X', incident_id)),
                    subdivision_id,
                    NULL,
                    description, category, location,
                    incident_date, reported_at, resolved_at, status, proof_photo_path,
                    reported_by, assigned_to, verified_resident_id, verification_method, verified_at,
                    created_at, deleted_at
                FROM incidents_old
            ");
        } else {
            DB::statement("
                CREATE TABLE incidents (
                    incident_id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                    subdivision_id INTEGER NOT NULL,
                    title VARCHAR(150) NOT NULL DEFAULT '',
                    description TEXT NULL,
                    category VARCHAR(100) NULL,
                    location VARCHAR(150) NULL,
                    incident_date DATETIME NOT NULL,
                    reported_at DATETIME NULL,
                    resolved_at DATETIME NULL,
                    status VARCHAR NOT NULL CHECK (status IN ('Open','Under Investigation','Resolved','Closed')) DEFAULT 'Open',
                    proof_photo_path VARCHAR(255) NULL,
                    reported_by INTEGER NOT NULL,
                    assigned_to INTEGER NULL,
                    verified_resident_id INTEGER NULL,
                    verification_method VARCHAR(50) NULL,
                    verified_at DATETIME NULL,
                    created_at DATETIME NULL,
                    deleted_at DATETIME NULL
                )
            ");

            DB::statement("
                INSERT INTO incidents (
                    incident_id, subdivision_id, title, description, category, location,
                    incident_date, reported_at, resolved_at, status, proof_photo_path,
                    reported_by, assigned_to, verified_resident_id, verification_method, verified_at,
                    created_at, deleted_at
                )
                SELECT
                    incident_id, subdivision_id, COALESCE(report_id, ''), description, category, location,
                    incident_date, reported_at, resolved_at, status, proof_photo_path,
                    reported_by, assigned_to, verified_resident_id, verification_method, verified_at,
                    created_at, deleted_at
                FROM incidents_old
            ");
        }

        DB::statement('DROP TABLE incidents_old');
        DB::statement('CREATE UNIQUE INDEX incidents_report_id_unique ON incidents (report_id)') ;
        DB::statement('CREATE INDEX incidents_subdivision_id_index ON incidents (subdivision_id)');
        DB::statement('CREATE INDEX incidents_house_id_index ON incidents (house_id)');
        DB::statement('CREATE INDEX incidents_status_index ON incidents (status)');
        DB::statement('CREATE INDEX incidents_incident_date_index ON incidents (incident_date)');
        DB::statement('CREATE INDEX incidents_reported_by_index ON incidents (reported_by)');
        DB::statement('CREATE INDEX incidents_verified_resident_id_index ON incidents (verified_resident_id)');
        DB::statement('CREATE INDEX incidents_deleted_at_index ON incidents (deleted_at)');
        DB::statement('PRAGMA foreign_keys = ON');
    }
};
