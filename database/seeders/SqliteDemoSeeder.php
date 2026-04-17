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
                'visitor_requests',
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

            DB::statement("DELETE FROM sqlite_sequence WHERE name IN ('visitor_requests', 'incident_photos', 'incidents', 'visitors', 'gate_visitor_logs', 'users', 'residents', 'houses', 'subdivisions')");

            // ── Subdivision ──────────────────────────────────────────────────
            $subdivision = Subdivision::create([
                'subdivision_name' => 'Maple Grove Residences',
                'address'          => '101 Maple Grove Avenue, San Jose',
                'contact_person'   => 'Clara Mendoza',
                'contact_number'   => '09171234567',
                'email'            => 'maplegrove@example.com',
                'status'           => 'Active',
            ]);

            // ── Houses ───────────────────────────────────────────────────────
            $house1 = House::create(['subdivision_id' => $subdivision->subdivision_id, 'block' => '1', 'lot' => '7']);
            $house2 = House::create(['subdivision_id' => $subdivision->subdivision_id, 'block' => '2', 'lot' => '3']);

            // ── Staff accounts ───────────────────────────────────────────────
            User::create([
                'surname' => 'Administrator', 'first_name' => 'System', 'middle_name' => null, 'extension' => null,
                'email' => 'admin@example.com', 'password' => 'password', 'role' => 'admin', 'subdivision_id' => null,
            ]);

            User::create([
                'surname' => 'Lopez', 'first_name' => 'Tina', 'middle_name' => null, 'extension' => null,
                'email' => 'staff@example.com', 'password' => 'password', 'role' => 'staff',
                'subdivision_id' => $subdivision->subdivision_id,
            ]);

            User::create([
                'surname' => 'Navarro', 'first_name' => 'Sam', 'middle_name' => null, 'extension' => null,
                'email' => 'security@example.com', 'password' => 'password', 'role' => 'security',
                'subdivision_id' => $subdivision->subdivision_id,
            ]);

            // ── Residents (5) ────────────────────────────────────────────────
            $residentData = [
                ['first' => 'Rina',   'middle' => 'M.',  'surname' => 'Dela Cruz', 'phone' => '09179998877', 'email' => 'resident1@example.com', 'house' => $house1, 'code' => 'E7FC90'],
                ['first' => 'Marco',  'middle' => null,  'surname' => 'Reyes',     'phone' => '09181112222', 'email' => 'resident2@example.com', 'house' => $house1, 'code' => null],
                ['first' => 'Liza',   'middle' => 'B.',  'surname' => 'Santos',    'phone' => '09192223333', 'email' => 'resident3@example.com', 'house' => $house2, 'code' => null],
                ['first' => 'Carlos', 'middle' => null,  'surname' => 'Bautista',  'phone' => '09203334444', 'email' => 'resident4@example.com', 'house' => $house2, 'code' => null],
                ['first' => 'Ana',    'middle' => 'R.',  'surname' => 'Villanueva','phone' => '09214445555', 'email' => 'resident5@example.com', 'house' => $house2, 'code' => null],
            ];

            $residents = [];
            foreach ($residentData as $i => $data) {
                $fullName = trim("{$data['first']} {$data['middle']} {$data['surname']}");
                $resident = Resident::create([
                    'subdivision_id'  => $subdivision->subdivision_id,
                    'house_id'        => $data['house']->house_id,
                    'full_name'       => $fullName,
                    'phone'           => $data['phone'],
                    'email'           => $data['email'],
                    'address_or_unit' => $data['house']->display_address,
                    'status'          => 'Active',
                ]);

                if ($data['code']) {
                    $resident->forceFill(['resident_code' => $data['code']])->save();
                }

                User::create([
                    'surname'        => $data['surname'],
                    'first_name'     => $data['first'],
                    'middle_name'    => $data['middle'],
                    'extension'      => null,
                    'email'          => 'user.' . $data['email'],
                    'password'       => 'password',
                    'role'           => 'resident',
                    'subdivision_id' => $subdivision->subdivision_id,
                    'resident_id'    => $resident->resident_id,
                ]);

                $residents[] = $resident;
            }

            // ── Residents without accounts ────────────────────────────────────
            foreach ([
                ['full_name' => 'Roberto Pascual',   'phone' => '09221116666', 'email' => null,                       'house' => $house1],
                ['full_name' => 'Grace Domingo',     'phone' => '09232227777', 'email' => 'grace.domingo@email.com',  'house' => $house1],
                ['full_name' => 'Felix Soriano',     'phone' => null,          'email' => null,                       'house' => $house2],
                ['full_name' => 'Marites Ocampo',    'phone' => '09254449999', 'email' => 'marites.o@email.com',      'house' => $house2],
            ] as $data) {
                $residents[] = Resident::create([
                    'subdivision_id'  => $subdivision->subdivision_id,
                    'house_id'        => $data['house']->house_id,
                    'full_name'       => $data['full_name'],
                    'phone'           => $data['phone'],
                    'email'           => $data['email'],
                    'address_or_unit' => $data['house']->display_address,
                    'status'          => 'Active',
                ]);
            }

            // ── Visitors ─────────────────────────────────────────────────────
            // Currently inside (checked in, not yet checked out)
            Visitor::create([
                'subdivision_id'      => $subdivision->subdivision_id,
                'surname'             => 'Mendoza',
                'first_name'          => 'Mia',
                'middle_initials'     => 'L.',
                'phone'               => '09170001111',
                'purpose'             => 'Delivery for homeowner',
                'host_employee'       => $residents[0]->full_name,
                'house_address_or_unit' => $house1->display_address,
                'check_in'            => now()->subMinutes(30),
                'check_out'           => null,
                'status'              => 'Inside',
            ]);

            Visitor::create([
                'subdivision_id'      => $subdivision->subdivision_id,
                'surname'             => 'Cruz',
                'first_name'          => 'Ben',
                'middle_initials'     => null,
                'phone'               => '09181230000',
                'purpose'             => 'Family visit',
                'host_employee'       => $residents[2]->full_name,
                'house_address_or_unit' => $house2->display_address,
                'check_in'            => now()->subHour(),
                'check_out'           => null,
                'status'              => 'Inside',
            ]);

            // Completed check-in & check-out
            Visitor::create([
                'subdivision_id'      => $subdivision->subdivision_id,
                'surname'             => 'Garcia',
                'first_name'          => 'Noel',
                'middle_initials'     => null,
                'phone'               => '09175554444',
                'purpose'             => 'Meter inspection',
                'host_employee'       => 'Admin Office',
                'house_address_or_unit' => $house1->display_address,
                'check_in'            => now()->subHours(3),
                'check_out'           => now()->subHours(2),
                'status'              => 'Checked Out',
            ]);

            Visitor::create([
                'subdivision_id'      => $subdivision->subdivision_id,
                'surname'             => 'Tan',
                'first_name'          => 'Joyce',
                'middle_initials'     => 'P.',
                'phone'               => '09209876543',
                'purpose'             => 'Plumbing repair',
                'host_employee'       => $residents[3]->full_name,
                'house_address_or_unit' => $house2->display_address,
                'check_in'            => now()->subHours(5),
                'check_out'           => now()->subHours(4),
                'status'              => 'Checked Out',
            ]);

            // ── Incidents ────────────────────────────────────────────────────
            // Open
            Incident::create([
                'subdivision_id'       => $subdivision->subdivision_id,
                'house_id'             => $house1->house_id,
                'description'          => 'Lamp post beside the clubhouse entrance has been off since last night.',
                'category'             => 'Safety',
                'location'             => 'Clubhouse entrance',
                'incident_date'        => now()->subHours(6),
                'reported_at'          => now()->subHours(5),
                'status'               => 'Open',
                'reported_by'          => $residents[0]->user->user_id,
                'verified_resident_id' => $residents[0]->resident_id,
                'verification_method'  => 'resident_account',
                'verified_at'          => now()->subHours(5),
            ]);

            // Under Investigation
            Incident::create([
                'subdivision_id'       => $subdivision->subdivision_id,
                'house_id'             => $house2->house_id,
                'description'          => 'Suspicious vehicle parked outside Block 2 for over 24 hours.',
                'category'             => 'Security',
                'location'             => 'Block 2 street',
                'incident_date'        => now()->subDays(1),
                'reported_at'          => now()->subDays(1),
                'status'               => 'Under Investigation',
                'reported_by'          => $residents[2]->user->user_id,
                'verified_resident_id' => $residents[2]->resident_id,
                'verification_method'  => 'resident_account',
                'verified_at'          => now()->subDays(1),
            ]);

            // Resolved
            Incident::create([
                'subdivision_id'       => $subdivision->subdivision_id,
                'house_id'             => $house1->house_id,
                'description'          => 'Broken gate latch on the main entrance repaired.',
                'category'             => 'Maintenance',
                'location'             => 'Main gate',
                'incident_date'        => now()->subDays(3),
                'reported_at'          => now()->subDays(3),
                'resolved_at'          => now()->subDays(2),
                'status'               => 'Resolved',
                'reported_by'          => $residents[1]->user->user_id,
                'verified_resident_id' => $residents[1]->resident_id,
                'verification_method'  => 'resident_account',
                'verified_at'          => now()->subDays(3),
            ]);

            // Closed
            Incident::create([
                'subdivision_id'       => $subdivision->subdivision_id,
                'house_id'             => $house2->house_id,
                'description'          => 'Noise complaint from Block 2 Lot 3 during late hours. Resolved after warning.',
                'category'             => 'Noise',
                'location'             => 'Block 2 Lot 3',
                'incident_date'        => now()->subDays(7),
                'reported_at'          => now()->subDays(7),
                'resolved_at'          => now()->subDays(6),
                'status'               => 'Closed',
                'reported_by'          => $residents[3]->user->user_id,
                'verified_resident_id' => $residents[3]->resident_id,
                'verification_method'  => 'resident_account',
                'verified_at'          => now()->subDays(7),
            ]);

            // ── Visitor Requests ─────────────────────────────────────────────
            // Pending
            DB::table('visitor_requests')->insert([
                'resident_id'          => $residents[0]->resident_id,
                'subdivision_id'       => $subdivision->subdivision_id,
                'visitor_name'         => 'Pedro Ramos',
                'phone'                => '09161112222',
                'house_address_or_unit'=> $house1->display_address,
                'purpose'              => 'Birthday celebration guest',
                'status'               => 'Pending',
                'requested_at'         => now()->subMinutes(15),
                'responded_at'         => null,
            ]);

            DB::table('visitor_requests')->insert([
                'resident_id'          => $residents[2]->resident_id,
                'subdivision_id'       => $subdivision->subdivision_id,
                'visitor_name'         => 'Maria Flores',
                'phone'                => '09172223333',
                'house_address_or_unit'=> $house2->display_address,
                'purpose'              => 'Package pickup',
                'status'               => 'Pending',
                'requested_at'         => now()->subMinutes(5),
                'responded_at'         => null,
            ]);

            // Approved
            DB::table('visitor_requests')->insert([
                'resident_id'          => $residents[1]->resident_id,
                'subdivision_id'       => $subdivision->subdivision_id,
                'visitor_name'         => 'Tony Aquino',
                'phone'                => '09183334444',
                'house_address_or_unit'=> $house1->display_address,
                'purpose'              => 'Friend visit',
                'status'               => 'Approved',
                'requested_at'         => now()->subHours(2),
                'responded_at'         => now()->subHours(1),
            ]);

            // Declined
            DB::table('visitor_requests')->insert([
                'resident_id'          => $residents[4]->resident_id,
                'subdivision_id'       => $subdivision->subdivision_id,
                'visitor_name'         => 'Unknown Caller',
                'phone'                => null,
                'house_address_or_unit'=> $house2->display_address,
                'purpose'              => 'Unspecified',
                'status'               => 'Declined',
                'requested_at'         => now()->subHours(3),
                'responded_at'         => now()->subHours(2),
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
                role VARCHAR NOT NULL CHECK (role IN ('admin','security','staff','resident')),
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
        DB::statement('CREATE UNIQUE INDEX users_resident_id_active_unique ON users (resident_id) WHERE deleted_at IS NULL');
        DB::statement('CREATE INDEX users_email_index ON users (email)');
        DB::statement('CREATE INDEX users_role_index ON users (role)');
        DB::statement('CREATE INDEX users_subdivision_id_index ON users (subdivision_id)');
        DB::statement('CREATE INDEX users_resident_id_index ON users (resident_id)');
        DB::statement('CREATE INDEX users_deleted_at_index ON users (deleted_at)');
        DB::statement('PRAGMA foreign_keys = ON');
    }
}
