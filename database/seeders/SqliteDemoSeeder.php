<?php

namespace Database\Seeders;

use App\Models\House;
use App\Models\Incident;
use App\Models\Resident;
use App\Models\Subdivision;
use App\Models\User;
use App\Models\Visitor;
use App\Models\VisitorRequest;
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
            $incidentStatuses = $this->incidentStatusPreset();

            foreach ([
                'incident_photos',
                'incidents',
                'visitor_requests',
                'visitors',
                'gate_visitor_logs',
                'users',
                'residents',
                'houses',
                'subdivisions',
            ] as $table) {
                if (Schema::hasTable($table)) {
                    DB::table($table)->delete();
                }
            }

            DB::statement("DELETE FROM sqlite_sequence WHERE name IN ('incident_photos', 'incidents', 'visitor_requests', 'visitors', 'gate_visitor_logs', 'users', 'residents', 'houses', 'subdivisions')");

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
                'block' => 'BLK 001',
                'lot' => 'LOT-007',
            ]);

            $house2 = House::create([
                'subdivision_id' => $subdivision->subdivision_id,
                'street' => 'Plaza Boulevard',
                'block' => 'Block 2',
                'lot' => '03',
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
                ['full_name' => 'Rina M. Dela Cruz', 'phone' => '09179998877', 'email' => 'resident1@example.com', 'house' => $house1],
                ['full_name' => 'Marco Reyes', 'phone' => '09181112222', 'email' => 'resident2@example.com', 'house' => $house1],
                ['full_name' => 'Liza B. Santos', 'phone' => '09192223333', 'email' => 'resident3@example.com', 'house' => $house2],
                ['full_name' => 'Carlos Bautista', 'phone' => '09203334444', 'email' => 'resident4@example.com', 'house' => $house2],
                ['full_name' => 'Ana R. Villanueva', 'phone' => '09214445555', 'email' => 'resident5@example.com', 'house' => $house2],
                ['full_name' => 'Roberto Pascual', 'phone' => '09221116666', 'email' => 'roberto.pascual@email.com', 'house' => $house1],
                ['full_name' => 'Grace Domingo', 'phone' => '09232227777', 'email' => 'grace.domingo@email.com', 'house' => $house1],
                ['full_name' => 'Felix Soriano', 'phone' => '09243338888', 'email' => 'felix.soriano@email.com', 'house' => $house2],
                ['full_name' => 'Marites Ocampo', 'phone' => '09254449999', 'email' => 'marites.o@email.com', 'house' => $house2],
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

                $residents[] = $resident;
            }

            User::create([
                'surname' => 'Dela Cruz',
                'first_name' => 'Rina',
                'middle_name' => 'M.',
                'extension' => null,
                'email' => 'resident.portal@example.com',
                'password' => 'password',
                'role' => 'resident',
                'subdivision_id' => $subdivision->subdivision_id,
                'resident_id' => $residents[0]->resident_id,
            ]);

            $visitorRequests = [
                [
                    'visitor_name' => 'Elena Cruz',
                    'surname' => 'Cruz',
                    'first_name' => 'Elena',
                    'middle_initials' => null,
                    'phone' => '09170001111',
                    'purpose' => 'Family visit',
                    'plate_number' => null,
                    'passenger_count' => null,
                    'resident' => $residents[0],
                    'house_address_or_unit' => $house1->display_address,
                    'status' => 'Pending',
                    'requested_at' => now()->subMinutes(20),
                    'responded_at' => null,
                ],
                [
                    'visitor_name' => 'Mia Mendoza',
                    'surname' => 'Mendoza',
                    'first_name' => 'Mia',
                    'middle_initials' => 'L.',
                    'phone' => '09181230000',
                    'purpose' => 'Delivery for homeowner',
                    'plate_number' => 'ABC 1234',
                    'passenger_count' => 2,
                    'resident' => $residents[0],
                    'house_address_or_unit' => $house1->display_address,
                    'status' => 'Approved',
                    'requested_at' => now()->subHours(2),
                    'responded_at' => now()->subHours(2)->addMinutes(5),
                ],
                [
                    'visitor_name' => 'Ben Santos',
                    'surname' => 'Santos',
                    'first_name' => 'Ben',
                    'middle_initials' => null,
                    'phone' => '09175554444',
                    'purpose' => 'Unannounced visit',
                    'plate_number' => null,
                    'passenger_count' => null,
                    'resident' => $residents[2],
                    'house_address_or_unit' => $house2->display_address,
                    'status' => 'Declined',
                    'requested_at' => now()->subHour(),
                    'responded_at' => now()->subMinutes(50),
                ],
                [
                    'visitor_name' => 'Joyce Tan',
                    'surname' => 'Tan',
                    'first_name' => 'Joyce',
                    'middle_initials' => 'P.',
                    'phone' => '09209876543',
                    'purpose' => 'Plumbing repair',
                    'plate_number' => 'XYZ 9876',
                    'passenger_count' => 1,
                    'resident' => $residents[3],
                    'house_address_or_unit' => $house2->display_address,
                    'status' => 'Approved',
                    'requested_at' => now()->subHours(6),
                    'responded_at' => now()->subHours(6)->addMinutes(10),
                ],
            ];

            $createdRequests = [];

            foreach ($visitorRequests as $requestData) {
                $visitorRequestPayload = [
                    'visitor_id' => null,
                    'resident_id' => $requestData['resident']->resident_id,
                    'subdivision_id' => $subdivision->subdivision_id,
                    'visitor_name' => $requestData['visitor_name'],
                    'surname' => $requestData['surname'],
                    'first_name' => $requestData['first_name'],
                    'middle_initials' => $requestData['middle_initials'],
                    'extension' => null,
                    'phone' => $requestData['phone'],
                    'plate_number' => $requestData['plate_number'] ?? null,
                    'id_photo_path' => null,
                    'house_address_or_unit' => $requestData['house_address_or_unit'],
                    'purpose' => $requestData['purpose'],
                    'status' => $requestData['status'],
                    'requested_at' => $requestData['requested_at'],
                    'responded_at' => $requestData['responded_at'],
                ];

                if (Schema::hasColumn('visitor_requests', 'passenger_count')) {
                    $visitorRequestPayload['passenger_count'] = $requestData['passenger_count'] ?? null;
                }

                $createdRequests[] = VisitorRequest::create($visitorRequestPayload);
            }

            $approvedInsideVisitorPayload = [
                'subdivision_id' => $subdivision->subdivision_id,
                'surname' => 'Mendoza',
                'first_name' => 'Mia',
                'middle_initials' => 'L.',
                'phone' => '09181230000',
                'purpose' => 'Delivery for homeowner',
                'host_employee' => $residents[0]->full_name,
                'house_address_or_unit' => $house1->display_address,
                'check_in' => now()->subHours(2)->addMinutes(10),
                'check_out' => null,
                'status' => 'Inside',
            ];

            if (Schema::hasColumn('visitors', 'plate_number')) {
                $approvedInsideVisitorPayload['plate_number'] = 'ABC 1234';
            }

            if (Schema::hasColumn('visitors', 'passenger_count')) {
                $approvedInsideVisitorPayload['passenger_count'] = 2;
            }

            $approvedInsideVisitor = Visitor::create($approvedInsideVisitorPayload);

            $approvedCheckedOutVisitorPayload = [
                'subdivision_id' => $subdivision->subdivision_id,
                'surname' => 'Tan',
                'first_name' => 'Joyce',
                'middle_initials' => 'P.',
                'phone' => '09209876543',
                'purpose' => 'Plumbing repair',
                'host_employee' => $residents[3]->full_name,
                'house_address_or_unit' => $house2->display_address,
                'check_in' => now()->subHours(6)->addMinutes(20),
                'check_out' => now()->subHours(5)->addMinutes(30),
                'status' => 'Checked Out',
            ];

            if (Schema::hasColumn('visitors', 'plate_number')) {
                $approvedCheckedOutVisitorPayload['plate_number'] = 'XYZ 9876';
            }

            if (Schema::hasColumn('visitors', 'passenger_count')) {
                $approvedCheckedOutVisitorPayload['passenger_count'] = 1;
            }

            $approvedCheckedOutVisitor = Visitor::create($approvedCheckedOutVisitorPayload);

            foreach ($createdRequests as $requestRecord) {
                if ($requestRecord->visitor_name === 'Mia Mendoza') {
                    $requestRecord->update(['visitor_id' => $approvedInsideVisitor->visitor_id]);
                }

                if ($requestRecord->visitor_name === 'Joyce Tan') {
                    $requestRecord->update(['visitor_id' => $approvedCheckedOutVisitor->visitor_id]);
                }
            }

            Incident::create([
                'subdivision_id' => $subdivision->subdivision_id,
                'house_id' => $house1->house_id,
                'description' => 'Lamp post beside the clubhouse entrance has been off since last night.',
                'category' => 'Safety',
                'location' => $house1->display_address,
                'incident_date' => now()->subHours(6),
                'reported_at' => now()->subHours(5),
                'status' => $incidentStatuses['pending_primary'],
                'reported_by' => $securityUser->user_id,
            ]);

            Incident::create([
                'subdivision_id' => $subdivision->subdivision_id,
                'house_id' => $house2->house_id,
                'description' => 'Suspicious vehicle parked outside Block 2 for over 24 hours.',
                'category' => 'Security',
                'location' => $house2->display_address,
                'incident_date' => now()->subDays(1),
                'reported_at' => now()->subDays(1),
                'status' => $incidentStatuses['pending_secondary'],
                'reported_by' => $staffUser->user_id,
            ]);

            Incident::create([
                'subdivision_id' => $subdivision->subdivision_id,
                'house_id' => $house1->house_id,
                'description' => 'Broken gate latch on the main entrance repaired.',
                'category' => 'Property Damage',
                'location' => $house1->display_address,
                'incident_date' => now()->subDays(3),
                'reported_at' => now()->subDays(3),
                'resolved_at' => now()->subDays(2),
                'status' => $incidentStatuses['resolved_primary'],
                'reported_by' => $adminUser->user_id,
            ]);

            Incident::create([
                'subdivision_id' => $subdivision->subdivision_id,
                'house_id' => $house2->house_id,
                'description' => 'Noise complaint from Block 2 Lot 3 during late hours. Resolved after warning.',
                'category' => 'Noise Complaint',
                'location' => $house2->display_address,
                'incident_date' => now()->subDays(7),
                'reported_at' => now()->subDays(7),
                'resolved_at' => now()->subDays(6),
                'status' => $incidentStatuses['resolved_secondary'],
                'reported_by' => $securityUser2->user_id,
            ]);

            DB::commit();
        } catch (\Throwable $exception) {
            DB::rollBack();

            throw $exception;
        } finally {
            DB::statement('PRAGMA foreign_keys = ON');
        }
    }

    private function incidentStatusPreset(): array
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            return [
                'pending_primary' => 'Open',
                'pending_secondary' => 'Under Investigation',
                'resolved_primary' => 'Resolved',
                'resolved_secondary' => 'Closed',
            ];
        }

        $tableSql = DB::table('sqlite_master')
            ->where('type', 'table')
            ->where('name', 'incidents')
            ->value('sql');

        if (is_string($tableSql)
            && str_contains($tableSql, "'Reported'")
            && str_contains($tableSql, "'Investigating'")
        ) {
            return [
                'pending_primary' => 'Reported',
                'pending_secondary' => 'Investigating',
                'resolved_primary' => 'Resolved',
                'resolved_secondary' => 'Resolved',
            ];
        }

        return [
            'pending_primary' => 'Open',
            'pending_secondary' => 'Under Investigation',
            'resolved_primary' => 'Resolved',
            'resolved_secondary' => 'Closed',
        ];
    }
}
