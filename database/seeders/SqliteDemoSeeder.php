<?php

namespace Database\Seeders;

use App\Models\House;
use App\Models\Incident;
use App\Models\Resident;
use App\Models\Subdivision;
use App\Models\User;
use App\Models\Visitor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SqliteDemoSeeder extends Seeder
{
    /**
     * Seed a small SQLite-first demo dataset for local development.
     */
    public function run(): void
    {
        $this->ensureSqliteUsersTableSupportsResidentRole();

        DB::statement('PRAGMA foreign_keys = OFF');

        try {
            DB::beginTransaction();

            foreach ([
                'incident_photos',
                'incidents',
                'visitors',
                'gate_visitor_logs',
                'users',
                'residents',
                'houses',
                'subdivisions',
            ] as $table) {
                DB::table($table)->delete();
            }

            DB::statement("DELETE FROM sqlite_sequence WHERE name IN ('incident_photos', 'incidents', 'visitors', 'gate_visitor_logs', 'users', 'residents', 'houses', 'subdivisions')");

            $subdivision = Subdivision::create([
                'subdivision_name' => 'Maple Grove Residences',
                'address' => '101 Maple Grove Avenue, San Jose',
                'contact_person' => 'Clara Mendoza',
                'contact_number' => '09171234567',
                'email' => 'maplegrove@example.com',
                'status' => 'Active',
            ]);

            $house = House::create([
                'subdivision_id' => $subdivision->subdivision_id,
                'block' => '1',
                'lot' => '7',
            ]);

            $resident = Resident::create([
                'subdivision_id' => $subdivision->subdivision_id,
                'house_id' => $house->house_id,
                'full_name' => User::formatFullName('Rina', 'Dela Cruz', 'Mendoza', null),
                'phone' => '09179998877',
                'email' => 'resident@example.com',
                'address_or_unit' => $house->display_address,
                'resident_code' => 'RES-1001',
                'status' => 'Active',
            ]);

            $admin = User::create([
                'surname' => 'Administrator',
                'first_name' => 'System',
                'middle_name' => 'Local',
                'extension' => null,
                'email' => 'admin@example.com',
                'password' => 'password',
                'role' => 'admin',
                'subdivision_id' => null,
            ]);

            User::create([
                'surname' => 'Navarro',
                'first_name' => 'Sam',
                'middle_name' => null,
                'extension' => null,
                'email' => 'security@example.com',
                'password' => 'password',
                'role' => 'security',
                'subdivision_id' => $subdivision->subdivision_id,
            ]);

            User::create([
                'surname' => 'Lopez',
                'first_name' => 'Tina',
                'middle_name' => null,
                'extension' => null,
                'email' => 'staff@example.com',
                'password' => 'password',
                'role' => 'staff',
                'subdivision_id' => $subdivision->subdivision_id,
            ]);

            User::create([
                'surname' => 'Reyes',
                'first_name' => 'Ivan',
                'middle_name' => null,
                'extension' => null,
                'email' => 'investigator@example.com',
                'password' => 'password',
                'role' => 'investigator',
                'subdivision_id' => $subdivision->subdivision_id,
            ]);

            $residentUser = User::create([
                'surname' => 'Dela Cruz',
                'first_name' => 'Rina',
                'middle_name' => 'Mendoza',
                'extension' => null,
                'email' => 'resident.user@example.com',
                'password' => 'password',
                'role' => 'resident',
                'subdivision_id' => $subdivision->subdivision_id,
                'resident_id' => $resident->resident_id,
            ]);

            Incident::create([
                'subdivision_id' => $subdivision->subdivision_id,
                'title' => 'Streetlight outage near clubhouse',
                'description' => 'The lamp post beside the clubhouse entrance has been off since last night.',
                'category' => 'Safety',
                'location' => 'Clubhouse entrance',
                'incident_date' => now()->subHours(6),
                'reported_at' => now()->subHours(5),
                'resolved_at' => null,
                'status' => 'Open',
                'proof_photo_path' => null,
                'reported_by' => $residentUser->user_id,
                'verified_resident_id' => $resident->resident_id,
                'verification_method' => 'resident_account',
                'verified_at' => now()->subHours(5),
            ]);

            Visitor::create([
                'subdivision_id' => $subdivision->subdivision_id,
                'surname' => 'Santos',
                'first_name' => 'Mia',
                'middle_initials' => 'L.',
                'extension' => null,
                'phone' => '09170001111',
                'id_number' => 'VIS-0001',
                'company' => 'Parcel Express',
                'purpose' => 'Delivery for homeowner',
                'host_employee' => 'Rina Dela Cruz',
                'house_address_or_unit' => $house->display_address,
                'check_in' => now()->subMinutes(20),
                'check_out' => null,
                'status' => 'Inside',
            ]);

            Visitor::create([
                'subdivision_id' => $subdivision->subdivision_id,
                'surname' => 'Garcia',
                'first_name' => 'Noel',
                'middle_initials' => null,
                'extension' => null,
                'phone' => '09175554444',
                'id_number' => 'VIS-0002',
                'company' => 'Utility Services',
                'purpose' => 'Meter inspection',
                'host_employee' => 'Admin Office',
                'house_address_or_unit' => $house->display_address,
                'check_in' => now()->subHours(2),
                'check_out' => now()->subHour(),
                'status' => 'Checked Out',
            ]);

            DB::commit();
        } catch (\Throwable $exception) {
            DB::rollBack();

            throw $exception;
        } finally {
            DB::statement('PRAGMA foreign_keys = ON');
        }
    }

    private function ensureSqliteUsersTableSupportsResidentRole(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            return;
        }

        $createStatement = DB::selectOne("SELECT sql FROM sqlite_master WHERE type = 'table' AND name = 'users'");
        $sql = $createStatement->sql ?? '';

        if (str_contains((string) $sql, "'resident'")) {
            return;
        }

        DB::statement('PRAGMA foreign_keys = OFF');
        DB::statement('ALTER TABLE users RENAME TO users_old');

        DB::statement("
            CREATE TABLE users (
                user_id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                full_name VARCHAR(100) NOT NULL,
                email VARCHAR(100) NOT NULL,
                password VARCHAR(255) NOT NULL,
                role VARCHAR NOT NULL CHECK (role IN ('admin','security','staff','investigator','resident')),
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
}
