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
use Illuminate\Support\Facades\Schema;

class SqliteDemoSeeder extends Seeder
{
    /**
     * Seed a small SQLite-first demo dataset for local development.
     */
    public function run(): void
    {
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
                'subdivision_name' => 'Dona Maria Dizon',
                'country' => 'Philippines',
                'street' => 'Buenlag',
                'city' => 'Calasiao',
                'province' => 'Pangasinan',
                'zip' => '2418',
                'contact_person' => 'Clara Mendoza',
                'contact_number' => '09171234567',
                'email' => 'maplegrove@example.com',
                'status' => 'Active',
            ]);

            $house1 = House::create([
                'subdivision_id' => $subdivision->subdivision_id,
                'street' => 'Imperial Street',
                'block' => '1',
                'lot' => '7',
            ]);

            $house2 = House::create([
                'subdivision_id' => $subdivision->subdivision_id,
                'street' => 'Plaza Boulevard',
                'block' => '2',
                'lot' => '3',
            ]);

            $adminUser = User::create([
                'surname' => 'Administrator',
                'first_name' => 'System',
                'middle_name' => null,
                'extension' => null,
                'email' => 'admin@example.com',
                'password' => 'password',
                'role' => 'admin',
                'subdivision_id' => null,
            ]);

            $staffUser = User::create([
                'surname' => 'Lopez',
                'first_name' => 'Tina',
                'middle_name' => null,
                'extension' => null,
                'email' => 'staff@example.com',
                'password' => 'password',
                'role' => 'staff',
                'subdivision_id' => $subdivision->subdivision_id,
            ]);

            $staffUser2 = User::create([
                'surname' => 'Ramos',
                'first_name' => 'Erin',
                'middle_name' => null,
                'extension' => null,
                'email' => 'staff2@example.com',
                'password' => 'password',
                'role' => 'staff',
                'subdivision_id' => $subdivision->subdivision_id,
            ]);

            $securityUser = User::create([
                'surname' => 'Navarro',
                'first_name' => 'Sam',
                'middle_name' => null,
                'extension' => null,
                'email' => 'security@example.com',
                'password' => 'password',
                'role' => 'security',
                'subdivision_id' => $subdivision->subdivision_id,
            ]);

            $securityUser2 = User::create([
                'surname' => 'Cortez',
                'first_name' => 'Leo',
                'middle_name' => null,
                'extension' => null,
                'email' => 'security2@example.com',
                'password' => 'password',
                'role' => 'security',
                'subdivision_id' => $subdivision->subdivision_id,
            ]);

            $residentData = [
                ['full_name' => 'Rina M. Dela Cruz', 'phone' => '09179998877', 'email' => 'resident1@example.com', 'house' => $house1, 'code' => 'E7FC90'],
                ['full_name' => 'Marco Reyes', 'phone' => '09181112222', 'email' => 'resident2@example.com', 'house' => $house1, 'code' => null],
                ['full_name' => 'Liza B. Santos', 'phone' => '09192223333', 'email' => 'resident3@example.com', 'house' => $house2, 'code' => null],
                ['full_name' => 'Carlos Bautista', 'phone' => '09203334444', 'email' => 'resident4@example.com', 'house' => $house2, 'code' => null],
                ['full_name' => 'Ana R. Villanueva', 'phone' => '09214445555', 'email' => 'resident5@example.com', 'house' => $house2, 'code' => null],
                ['full_name' => 'Roberto Pascual', 'phone' => '09221116666', 'email' => 'roberto.pascual@email.com', 'house' => $house1, 'code' => null],
                ['full_name' => 'Grace Domingo', 'phone' => '09232227777', 'email' => 'grace.domingo@email.com', 'house' => $house1, 'code' => null],
                ['full_name' => 'Felix Soriano', 'phone' => '09243338888', 'email' => 'felix.soriano@email.com', 'house' => $house2, 'code' => null],
                ['full_name' => 'Marites Ocampo', 'phone' => '09254449999', 'email' => 'marites.o@email.com', 'house' => $house2, 'code' => null],
            ];

            $residents = [];

            foreach ($residentData as $data) {
                $resident = Resident::create([
                    'subdivision_id' => $subdivision->subdivision_id,
                    'house_id' => $data['house']->house_id,
                    'full_name' => $data['full_name'],
                    'phone' => $data['phone'],
                    'email' => $data['email'],
                    'address_or_unit' => $data['house']->display_address,
                    'status' => 'Active',
                ]);

                if (!empty($data['code'])) {
                    $resident->forceFill(['resident_code' => $data['code']])->save();
                }

                $residents[] = $resident;
            }

            $visitors = [
                [
                    'surname' => 'Mendoza',
                    'first_name' => 'Mia',
                    'middle_initials' => 'L.',
                    'phone' => '09170001111',
                    'plate_number' => 'ABC-1234',
                    'passenger_count' => 2,
                    'purpose' => 'Delivery for homeowner',
                    'host_employee' => $residents[0]->full_name,
                    'house_address_or_unit' => $house1->display_address,
                    'check_in' => now()->subMinutes(30),
                    'check_out' => null,
                    'status' => 'Inside',
                ],
                [
                    'surname' => 'Cruz',
                    'first_name' => 'Ben',
                    'middle_initials' => null,
                    'phone' => '09181230000',
                    'plate_number' => null,
                    'passenger_count' => null,
                    'purpose' => 'Family visit',
                    'host_employee' => $residents[2]->full_name,
                    'house_address_or_unit' => $house2->display_address,
                    'check_in' => now()->subHour(),
                    'check_out' => null,
                    'status' => 'Inside',
                ],
                [
                    'surname' => 'Garcia',
                    'first_name' => 'Noel',
                    'middle_initials' => null,
                    'phone' => '09175554444',
                    'plate_number' => 'XYZ-7788',
                    'passenger_count' => 1,
                    'purpose' => 'Meter inspection',
                    'host_employee' => 'Admin Office',
                    'house_address_or_unit' => $house1->display_address,
                    'check_in' => now()->subHours(3),
                    'check_out' => now()->subHours(2),
                    'status' => 'Checked Out',
                ],
                [
                    'surname' => 'Tan',
                    'first_name' => 'Joyce',
                    'middle_initials' => 'P.',
                    'phone' => '09209876543',
                    'plate_number' => 'PLT-5566',
                    'passenger_count' => 3,
                    'purpose' => 'Plumbing repair',
                    'host_employee' => $residents[3]->full_name,
                    'house_address_or_unit' => $house2->display_address,
                    'check_in' => now()->subHours(5),
                    'check_out' => now()->subHours(4),
                    'status' => 'Checked Out',
                ],
            ];

            foreach ($visitors as $visitorData) {
                $payload = [
                    'subdivision_id' => $subdivision->subdivision_id,
                    'surname' => $visitorData['surname'],
                    'first_name' => $visitorData['first_name'],
                    'middle_initials' => $visitorData['middle_initials'],
                    'phone' => $visitorData['phone'],
                    'purpose' => $visitorData['purpose'],
                    'host_employee' => $visitorData['host_employee'],
                    'house_address_or_unit' => $visitorData['house_address_or_unit'],
                    'check_in' => $visitorData['check_in'],
                    'check_out' => $visitorData['check_out'],
                    'status' => $visitorData['status'],
                ];

                if (Schema::hasColumn('visitors', 'plate_number')) {
                    $payload['plate_number'] = $visitorData['plate_number'];
                }

                if (Schema::hasColumn('visitors', 'passenger_count')) {
                    $payload['passenger_count'] = $visitorData['passenger_count'];
                }

                Visitor::create($payload);
            }

            Incident::create([
                'subdivision_id' => $subdivision->subdivision_id,
                'house_id' => $house1->house_id,
                'description' => 'Lamp post beside the clubhouse entrance has been off since last night.',
                'category' => 'Safety',
                'location' => $house1->display_address,
                'incident_date' => now()->subHours(6),
                'reported_at' => now()->subHours(5),
                'status' => 'Open',
                'reported_by' => $securityUser->user_id,
                'assigned_to' => $staffUser->user_id,
            ]);

            Incident::create([
                'subdivision_id' => $subdivision->subdivision_id,
                'house_id' => $house2->house_id,
                'description' => 'Suspicious vehicle parked outside Block 2 for over 24 hours.',
                'category' => 'Security',
                'location' => $house2->display_address,
                'incident_date' => now()->subDays(1),
                'reported_at' => now()->subDays(1),
                'status' => 'Under Investigation',
                'reported_by' => $staffUser->user_id,
                'assigned_to' => $securityUser->user_id,
                'verified_by_staff_id' => $securityUser->user_id,
                'verified_on_site_at' => now()->subHours(20),
            ]);

            Incident::create([
                'subdivision_id' => $subdivision->subdivision_id,
                'house_id' => $house1->house_id,
                'description' => 'Broken gate latch on the main entrance repaired.',
                'category' => 'Maintenance',
                'location' => $house1->display_address,
                'incident_date' => now()->subDays(3),
                'reported_at' => now()->subDays(3),
                'resolved_at' => now()->subDays(2),
                'status' => 'Resolved',
                'reported_by' => $adminUser->user_id,
                'assigned_to' => $staffUser2->user_id,
                'verified_by_staff_id' => $staffUser2->user_id,
                'verified_on_site_at' => now()->subDays(3),
            ]);

            Incident::create([
                'subdivision_id' => $subdivision->subdivision_id,
                'house_id' => $house2->house_id,
                'description' => 'Noise complaint from Block 2 Lot 3 during late hours. Resolved after warning.',
                'category' => 'Noise',
                'location' => $house2->display_address,
                'incident_date' => now()->subDays(7),
                'reported_at' => now()->subDays(7),
                'resolved_at' => now()->subDays(6),
                'status' => 'Closed',
                'reported_by' => $securityUser2->user_id,
                'assigned_to' => $securityUser->user_id,
                'verified_by_staff_id' => $securityUser->user_id,
                'verified_on_site_at' => now()->subDays(7),
            ]);

            DB::commit();
        } catch (\Throwable $exception) {
            DB::rollBack();

            throw $exception;
        } finally {
            DB::statement('PRAGMA foreign_keys = ON');
        }
    }
}
