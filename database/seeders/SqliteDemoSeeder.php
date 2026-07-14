<?php

namespace Database\Seeders;

use App\Enums\ActiveStatus;
use App\Enums\IncidentStatus;
use App\Enums\UserRole;
use App\Enums\VisitorRequestStatus;
use App\Enums\VisitorStatus;
use App\Models\House;
use App\Models\Incident;
use App\Models\IncidentPhoto;
use App\Models\Resident;
use App\Models\Subdivision;
use App\Models\User;
use App\Models\Visitor;
use App\Models\VisitorRequest;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
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
                'subdivision_name' => 'Doña Maria Dizon',
                'country' => 'Philippines',
                'street' => 'Buenlag',
                'city' => 'Calasiao',
                'province' => 'Pangasinan',
                'zip' => '2418',
                'contact_person' => 'Clara Mendoza',
                'contact_number' => '09171234567',
                'email' => 'maplegrove@example.com',
                'status' => ActiveStatus::Active->value,
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

            $house1Address = House::formatDisplayAddress($house1->street, $house1->block, $house1->lot);
            $house2Address = House::formatDisplayAddress($house2->street, $house2->block, $house2->lot);

            $adminUser = User::create([
                'surname' => 'Administrator',
                'first_name' => 'System',
                'middle_name' => null,
                'extension' => null,
                'email' => 'admin@example.com',
                'password' => 'password',
                'role' => UserRole::Admin->value,
                'subdivision_id' => null,
            ]);

            $staffUser = User::create([
                'surname' => 'Lopez',
                'first_name' => 'Tina',
                'middle_name' => null,
                'extension' => null,
                'email' => 'staff@example.com',
                'password' => 'password',
                'role' => UserRole::Staff->value,
                'subdivision_id' => $subdivision->subdivision_id,
            ]);

            $staffUser2 = User::create([
                'surname' => 'Ramos',
                'first_name' => 'Erin',
                'middle_name' => null,
                'extension' => null,
                'email' => 'staff2@example.com',
                'password' => 'password',
                'role' => UserRole::Staff->value,
                'subdivision_id' => $subdivision->subdivision_id,
            ]);

            $securityUser = User::create([
                'surname' => 'Navarro',
                'first_name' => 'Sam',
                'middle_name' => null,
                'extension' => null,
                'email' => 'security@example.com',
                'password' => 'password',
                'role' => UserRole::Security->value,
                'subdivision_id' => $subdivision->subdivision_id,
            ]);

            $securityUser2 = User::create([
                'surname' => 'Cortez',
                'first_name' => 'Leo',
                'middle_name' => null,
                'extension' => null,
                'email' => 'security2@example.com',
                'password' => 'password',
                'role' => UserRole::Security->value,
                'subdivision_id' => $subdivision->subdivision_id,
            ]);

            $residentData = [
                ['full_name' => 'Rina M. Dela Cruz', 'phone' => '09179998877', 'email' => 'resident1@example.com', 'house' => $house1, 'relation_to_owner' => 'Owner'],
                ['full_name' => 'Marco Reyes', 'phone' => '09181112222', 'email' => 'resident2@example.com', 'house' => $house1, 'relation_to_owner' => 'Husband'],
                ['full_name' => 'Liza B. Santos', 'phone' => '09192223333', 'email' => 'resident3@example.com', 'house' => $house2, 'relation_to_owner' => 'Owner'],
                ['full_name' => 'Carlos Bautista', 'phone' => '09203334444', 'email' => 'resident4@example.com', 'house' => $house2, 'relation_to_owner' => 'Husband'],
                ['full_name' => 'Ana R. Villanueva', 'phone' => '09214445555', 'email' => 'resident5@example.com', 'house' => $house2, 'relation_to_owner' => 'Child'],
                ['full_name' => 'Roberto Pascual', 'phone' => '09221116666', 'email' => 'roberto.pascual@email.com', 'house' => $house1, 'relation_to_owner' => 'Child'],
                ['full_name' => 'Grace Domingo', 'phone' => '09232227777', 'email' => 'grace.domingo@email.com', 'house' => $house1, 'relation_to_owner' => 'Relative'],
                ['full_name' => 'Felix Soriano', 'phone' => '09243338888', 'email' => 'felix.soriano@email.com', 'house' => $house2, 'relation_to_owner' => 'Helper'],
                ['full_name' => 'Marites Ocampo', 'phone' => '09254449999', 'email' => 'marites.o@email.com', 'house' => $house2, 'relation_to_owner' => 'Friend'],
            ];

            $residents = [];
            $hasRelationToOwnerColumn = Schema::hasColumn('residents', 'relation_to_owner');

            foreach ($residentData as $data) {
                $residentPayload = [
                    'subdivision_id' => $subdivision->subdivision_id,
                    'house_id' => $data['house']->house_id,
                    'full_name' => $data['full_name'],
                    'phone' => $data['phone'],
                    'email' => $data['email'],
                    'address_or_unit' => House::formatDisplayAddress($data['house']->street, $data['house']->block, $data['house']->lot),
                    'status' => ActiveStatus::Active->value,
                ];

                if ($hasRelationToOwnerColumn) {
                    $residentPayload['relation_to_owner'] = $data['relation_to_owner'] ?? null;
                }

                $resident = Resident::create($residentPayload);

                $residents[] = $resident;
            }

            User::create([
                'surname' => 'Dela Cruz',
                'first_name' => 'Rina',
                'middle_name' => 'M.',
                'extension' => null,
                'email' => 'resident.portal@example.com',
                'password' => 'password',
                'role' => UserRole::Resident->value,
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
                    'house_address_or_unit' => $house1Address,
                    'status' => VisitorRequestStatus::Pending->value,
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
                    'house_address_or_unit' => $house1Address,
                    'status' => VisitorRequestStatus::Approved->value,
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
                    'house_address_or_unit' => $house2Address,
                    'status' => VisitorRequestStatus::Declined->value,
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
                    'house_address_or_unit' => $house2Address,
                    'status' => VisitorRequestStatus::Approved->value,
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
                'house_address_or_unit' => $house1Address,
                'check_in' => now()->subHours(2)->addMinutes(10),
                'check_out' => null,
                'status' => VisitorStatus::Inside->value,
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
                'house_address_or_unit' => $house2Address,
                'check_in' => now()->subHours(6)->addMinutes(20),
                'check_out' => now()->subHours(5)->addMinutes(30),
                'status' => VisitorStatus::CheckedOut->value,
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
                'location' => $house1Address,
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
                'location' => $house2Address,
                'incident_date' => now()->subDays(1),
                'reported_at' => now()->subDays(1),
                'status' => $incidentStatuses['pending_secondary'],
                'reported_by' => $staffUser->user_id,
            ]);

            $resolvedIncidentOne = Incident::create([
                'subdivision_id' => $subdivision->subdivision_id,
                'house_id' => $house1->house_id,
                'description' => 'Broken gate latch on the main entrance repaired.',
                'category' => 'Property Damage',
                'location' => $house1Address,
                'incident_date' => now()->subDays(3),
                'reported_at' => now()->subDays(3),
                'resolved_at' => now()->subDays(2),
                'status' => $incidentStatuses['resolved_primary'],
                'reported_by' => $adminUser->user_id,
            ]);

            $resolvedIncidentTwo = Incident::create([
                'subdivision_id' => $subdivision->subdivision_id,
                'house_id' => $house2->house_id,
                'description' => 'Noise complaint from Block 2 Lot 3 during late hours. Resolved after warning.',
                'category' => 'Noise Complaint',
                'location' => $house2Address,
                'incident_date' => now()->subDays(7),
                'reported_at' => now()->subDays(7),
                'resolved_at' => now()->subDays(6),
                'status' => $incidentStatuses['resolved_secondary'],
                'reported_by' => $securityUser2->user_id,
            ]);

            $this->seedResolvedIncidentProofPhotos($resolvedIncidentOne, $resolvedIncidentTwo);

            $this->seedBulkData($subdivision, [$house1, $house2], $residents, [
                $adminUser, $staffUser, $staffUser2, $securityUser, $securityUser2,
            ], $incidentStatuses);

            DB::commit();
        } catch (\Throwable $exception) {
            DB::rollBack();

            throw $exception;
        } finally {
            DB::statement('PRAGMA foreign_keys = ON');
        }
    }

    private function seedBulkData(
        Subdivision $subdivision,
        array $houses,
        array $residents,
        array $users,
        array $incidentStatuses
    ): void {
        $firstNames  = ['Jose', 'Maria', 'Juan', 'Ana', 'Pedro', 'Rosa', 'Carlo', 'Lea', 'Mark', 'Nina', 'Luis', 'Clara', 'Ben', 'Grace', 'Felix', 'Iris', 'Dan', 'Ella', 'Roy', 'May', 'Kevin', 'Jenny', 'Roel', 'Hazel', 'Nico'];
        $surnames    = ['Santos', 'Reyes', 'Cruz', 'Bautista', 'Garcia', 'Torres', 'Ramos', 'Flores', 'Villanueva', 'Mendoza', 'Pascual', 'Domingo', 'Soriano', 'Ocampo', 'Luna', 'Rivera', 'Aguilar', 'Dela Cruz', 'Salazar', 'Navarro', 'Castillo', 'Morales', 'Diaz', 'Lim', 'Tan'];
        $middleInits = ['A.', 'B.', 'C.', 'D.', 'E.', 'F.', 'G.', 'H.', 'J.', 'L.', 'M.', 'P.', 'R.', 'S.', 'T.'];
        $relations   = ['Owner', 'Husband', 'Wife', 'Child', 'Relative', 'Helper', 'Friend', 'Tenant'];
        $streets     = ['Imperial Street', 'Plaza Boulevard', 'Sunrise Lane', 'Heritage Avenue', 'Maple Drive'];
        $vehicleTypes  = ['Car', 'Motorcycle', 'Van', 'Truck', null, null];
        $vehicleColors = ['White', 'Black', 'Silver', 'Red', 'Blue', 'Gray', 'Yellow', null];
        $platePrefixes = ['ABC', 'XYZ', 'BAG', 'PAN', 'ILO', 'CEB', 'DAV', 'MNL'];
        $purposes    = ['Family visit', 'Delivery', 'Repair service', 'Plumbing repair', 'Electrical work', 'Grocery delivery', 'Medical visit', 'Catering', 'Business meeting', 'Social visit', 'Maintenance check', 'Moving in assistance'];
        $reqStatuses = [
            VisitorRequestStatus::Pending->value,
            VisitorRequestStatus::Approved->value,
            VisitorRequestStatus::Approved->value,
            VisitorRequestStatus::Approved->value,
            VisitorRequestStatus::Declined->value,
        ];
        $incCategories = ['Safety', 'Security', 'Property Damage', 'Noise Complaint', 'Trespassing', 'Vandalism', 'Fire Hazard', 'Flooding'];
        $incDescriptions = [
            'Safety'          => ['Lamp post along the street has been broken for several days.', 'Uneven pavement near the park causing trip hazards.', 'Broken playground equipment posing risk to children.', 'Fallen tree branch blocking the footpath.', 'Open manhole cover near the entrance gate.'],
            'Security'        => ['Suspicious individual loitering near the perimeter fence.', 'Unidentified vehicle parked outside for over 48 hours.', 'Gate padlock found tampered with early morning.', 'Unknown person seen climbing over the back fence.', 'Security camera near Block 5 appears to have been vandalized.'],
            'Property Damage' => ['Concrete fence along the east side has a visible crack.', 'Streetlight pole knocked down, possibly by a vehicle.', 'Water pipe burst near the clubhouse flooding the walkway.', 'Roof gutter of the guardhouse is detached.', 'Glass panel on the entrance gate shattered.'],
            'Noise Complaint' => ['Loud music coming from a residence past midnight.', 'Construction noise starting before allowed hours.', 'Dog barking continuously disrupting neighbors.', 'Residents reported loud party until early morning.', 'Generator noise from a nearby house causing disturbance.'],
            'Trespassing'     => ['Unauthorized individual found inside the amenity area.', 'Non-resident vehicle entered without proper clearance.', 'Children from outside the subdivision using the pool area.', 'Vendor entered without signing the visitor log.', 'Person found inside the parking area without a sticker.'],
            'Vandalism'       => ['Graffiti found on the perimeter wall near the east gate.', 'Trash bins were overturned and scattered along the road.', 'Mailboxes near Block 3 were forcibly opened.', 'Street signs have been defaced with spray paint.', 'Potted plants at the entrance were destroyed.'],
            'Fire Hazard'     => ['Residents burning trash near the perimeter wall.', 'Electrical wires exposed near the water pump station.', 'Smoke detected from an unoccupied lot.', 'Gas smell reported near the back road area.', 'Dry leaves and debris piled up near a transformer.'],
            'Flooding'        => ['Standing water in the main road after heavy rain.', 'Drainage along Heritage Avenue is clogged.', 'Floodwater entering the lower section of the subdivision.', 'Mud and debris blocking the drainage canal.', 'Rainwater overflow reaching the ground floor of several units.'],
        ];
        $incStatuses = [
            $incidentStatuses['pending_primary'],
            $incidentStatuses['pending_secondary'],
            $incidentStatuses['resolved_primary'],
            $incidentStatuses['resolved_secondary'],
        ];

        $hasRelationCol  = Schema::hasColumn('residents', 'relation_to_owner');
        $hasPassengerCol = Schema::hasColumn('visitors', 'passenger_count');
        $hasVehicleCols  = Schema::hasColumn('visitors', 'vehicle_type');
        $hasReqPassenger = Schema::hasColumn('visitor_requests', 'passenger_count');
        $hasReqVehicle   = Schema::hasColumn('visitor_requests', 'vehicle_type');
        $hasPlateCol     = Schema::hasColumn('visitors', 'plate_number');

        $startTs = mktime(0, 0, 0, 1, 1, 2026);
        $nowTs   = time();
        $rand    = static function (array $arr) { return $arr[mt_rand(0, count($arr) - 1)]; };
        $randTs  = function () use ($startTs, $nowTs) { return mt_rand($startTs, $nowTs); };
        $plate   = function () use ($platePrefixes) { return $platePrefixes[mt_rand(0, count($platePrefixes) - 1)] . ' ' . str_pad((string) mt_rand(1, 9999), 4, '0', STR_PAD_LEFT); };
        $phone   = static function (int $seed) { return '09' . str_pad((string)(100000000 + $seed), 9, '0', STR_PAD_LEFT); };

        // 200 extra houses: 5 streets × blocks 10–19 × lots 1–4
        $bulkHouses = [];
        foreach ($streets as $street) {
            for ($b = 3; $b <= 12; $b++) {
                for ($l = 1; $l <= 4; $l++) {
                    $bulkHouses[] = House::create([
                        'subdivision_id' => $subdivision->subdivision_id,
                        'street' => $street,
                        'block'  => (string) $b,
                        'lot'    => (string) $l,
                    ]);
                }
            }
        }

        $allHouses    = array_merge($houses, $bulkHouses);
        $houseCount   = count($allHouses);

        // Residents: every bulk house gets 1-10, weighted toward small households
        // (mean ≈ 2.7) so the total lands at 500+ and Avg. Residents / House ≈ 2.7.
        $householdSize = static function (): int {
            $r = mt_rand(1, 100);
            return match (true) {
                $r <= 30 => 1,
                $r <= 58 => 2,
                $r <= 76 => 3,
                $r <= 86 => 4,
                $r <= 92 => 5,
                $r <= 95 => 6,
                $r <= 97 => 7,
                $r <= 98 => 8,
                $r <= 99 => 9,
                default  => 10,
            };
        };

        $bulkResidents = [];
        $residentSeq = 0;
        $makeResident = function (House $house, bool $isOwner) use (
            &$residentSeq, $subdivision, $firstNames, $surnames, $middleInits, $relations, $hasRelationCol, $rand, $phone
        ): Resident {
            $residentSeq++;
            $fn       = $rand($firstNames);
            $sn       = $rand($surnames);
            $mi       = mt_rand(0, 1) ? $rand($middleInits) : null;
            $fullName = $mi ? "{$fn} {$mi} {$sn}" : "{$fn} {$sn}";
            $payload  = [
                'subdivision_id'  => $subdivision->subdivision_id,
                'house_id'        => $house->house_id,
                'full_name'       => $fullName,
                'phone'           => $phone(100000 + $residentSeq * 13),
                'email'           => 'resident.bulk.' . $residentSeq . '@example.com',
                'address_or_unit' => $house->display_address,
                // The first resident of a house stays Active so no house looks vacant.
                'status'          => ($isOwner || mt_rand(0, 9) > 0) ? ActiveStatus::Active->value : ActiveStatus::Inactive->value,
            ];
            if ($hasRelationCol) {
                $payload['relation_to_owner'] = $isOwner ? 'Owner' : $rand(array_values(array_diff($relations, ['Owner'])));
            }

            return Resident::create($payload);
        };

        foreach ($bulkHouses as $house) {
            $size = $householdSize();
            for ($j = 0; $j < $size; $j++) {
                $bulkResidents[] = $makeResident($house, $j === 0);
            }
        }

        // Top up to guarantee 500+ residents overall.
        while (count($bulkResidents) + count($residents) < 500) {
            $bulkResidents[] = $makeResident($allHouses[mt_rand(0, $houseCount - 1)], false);
        }

        $allResidents  = array_merge($residents, $bulkResidents);
        $residentCount = count($allResidents);

        // 426 bulk incidents (430 with the 4 handcrafted ones above):
        // 11 Open + 21 Under Investigation pending (12 Open / 34 pending overall)
        // and 394 resolved, giving a 396/430 ≈ 92% resolution rate.
        for ($i = 0; $i < 426; $i++) {
            $house      = $allHouses[mt_rand(0, $houseCount - 1)];
            $category   = $rand($incCategories);
            $status     = match (true) {
                $i < 11 => $incidentStatuses['pending_primary'],
                $i < 32 => $incidentStatuses['pending_secondary'],
                default => $rand([$incidentStatuses['resolved_primary'], $incidentStatuses['resolved_secondary']]),
            };
            $reporter   = $users[mt_rand(0, count($users) - 1)];
            $isResolved = $i >= 32;
            // Pending incidents cluster in the last 30 days; resolved ones spread
            // from Jan 1 and leave 36h of headroom so resolution isn't clipped by "now".
            $incidentTs = $isResolved
                ? mt_rand($startTs, max($startTs, $nowTs - 133200))
                : mt_rand($nowTs - 2592000, $nowTs - 3600);
            $reportedTs = min($incidentTs + mt_rand(600, 7200), $nowTs);
            // 12-36h to resolve → averages out to ~1 day.
            $resolvedTs = $isResolved ? min($reportedTs + mt_rand(43200, 129600), $nowTs) : null;

            Incident::create([
                'subdivision_id' => $subdivision->subdivision_id,
                'house_id'       => $house->house_id,
                'description'    => $rand($incDescriptions[$category]),
                'category'       => $category,
                'location'       => $house->display_address,
                'incident_date'  => date('Y-m-d H:i:s', $incidentTs),
                'reported_at'    => date('Y-m-d H:i:s', $reportedTs),
                'resolved_at'    => $resolvedTs ? date('Y-m-d H:i:s', $resolvedTs) : null,
                'status'         => $status,
                'reported_by'    => $reporter->user_id,
            ]);
        }

        // 100 visitor requests
        for ($i = 0; $i < 100; $i++) {
            $resident   = $allResidents[mt_rand(0, $residentCount - 1)];
            $house      = $allHouses[mt_rand(0, $houseCount - 1)];
            $fn         = $rand($firstNames);
            $sn         = $rand($surnames);
            $mi         = mt_rand(0, 2) === 0 ? $rand($middleInits) : null;
            $reqStatus  = $rand($reqStatuses);
            $requestedTs = $randTs();
            $respondedTs = $reqStatus !== VisitorRequestStatus::Pending->value ? min($requestedTs + mt_rand(120, 1800), $nowTs) : null;
            $hasPlate   = mt_rand(0, 1);

            $payload = [
                'visitor_id'            => null,
                'resident_id'           => $resident->resident_id,
                'subdivision_id'        => $subdivision->subdivision_id,
                'visitor_name'          => "{$fn} {$sn}",
                'surname'               => $sn,
                'first_name'            => $fn,
                'middle_initials'       => $mi,
                'extension'             => null,
                'phone'                 => $phone(200000 + $i * 17),
                'plate_number'          => $hasPlate ? $plate() : null,
                'id_photo_path'         => null,
                'house_address_or_unit' => $house->display_address,
                'purpose'               => $rand($purposes),
                'status'                => $reqStatus,
                'requested_at'          => date('Y-m-d H:i:s', $requestedTs),
                'responded_at'          => $respondedTs ? date('Y-m-d H:i:s', $respondedTs) : null,
            ];
            if ($hasReqPassenger) {
                $payload['passenger_count'] = $hasPlate ? mt_rand(1, 5) : null;
            }
            if ($hasReqVehicle) {
                $payload['vehicle_type']  = $hasPlate ? $rand($vehicleTypes) : null;
                $payload['vehicle_color'] = $hasPlate ? $rand($vehicleColors) : null;
            }

            VisitorRequest::create($payload);
        }

        // 1,198 bulk visitors (1,200 with the 2 handcrafted ones above);
        // the first 49 stay Inside (50 overall) with recent check-ins.
        for ($i = 0; $i < 1198; $i++) {
            $resident  = $allResidents[mt_rand(0, $residentCount - 1)];
            $house     = $allHouses[mt_rand(0, $houseCount - 1)];
            $fn        = $rand($firstNames);
            $sn        = $rand($surnames);
            $mi        = mt_rand(0, 2) === 0 ? $rand($middleInits) : null;
            $isInside  = $i < 49;
            $checkInTs = $isInside ? mt_rand($nowTs - 172800, $nowTs - 900) : $randTs();
            $checkOutTs = $isInside ? null : min($checkInTs + mt_rand(1800, 14400), $nowTs);
            $hasPlate  = mt_rand(0, 1);

            $payload = [
                'subdivision_id'        => $subdivision->subdivision_id,
                'surname'               => $sn,
                'first_name'            => $fn,
                'middle_initials'       => $mi,
                'phone'                 => $phone(300000 + $i * 19),
                'purpose'               => $rand($purposes),
                'host_employee'         => $resident->full_name,
                'house_address_or_unit' => $house->display_address,
                'check_in'              => date('Y-m-d H:i:s', $checkInTs),
                'check_out'             => $checkOutTs ? date('Y-m-d H:i:s', $checkOutTs) : null,
                'status'                => $isInside ? VisitorStatus::Inside->value : VisitorStatus::CheckedOut->value,
            ];
            if ($hasPassengerCol) {
                $payload['passenger_count'] = $hasPlate ? mt_rand(1, 5) : null;
            }
            if ($hasVehicleCols) {
                $payload['vehicle_type']  = $hasPlate ? $rand($vehicleTypes) : null;
                $payload['vehicle_color'] = $hasPlate ? $rand($vehicleColors) : null;
            }
            if ($hasPlateCol) {
                $payload['plate_number'] = $hasPlate ? $plate() : null;
            }

            Visitor::create($payload);
        }
    }

    private function incidentStatusPreset(): array
    {
        if (DB::connection()->getDriverName() !== 'sqlite') {
            return [
                'pending_primary' => IncidentStatus::Open->value,
                'pending_secondary' => IncidentStatus::UnderInvestigation->value,
                'resolved_primary' => IncidentStatus::Resolved->value,
                'resolved_secondary' => IncidentStatus::Resolved->value,
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
            'pending_primary' => IncidentStatus::Open->value,
            'pending_secondary' => IncidentStatus::UnderInvestigation->value,
            'resolved_primary' => IncidentStatus::Resolved->value,
            'resolved_secondary' => IncidentStatus::Resolved->value,
        ];
    }

    private function seedResolvedIncidentProofPhotos(Incident ...$resolvedIncidents): void
    {
        $exampleImageAbsolutePath = public_path('uploads/incidents/incident-image-example.png');

        if (!File::exists($exampleImageAbsolutePath)) {
            return;
        }

        $seedStorageDirectory = storage_path('app/public/uploads/incidents');
        if (!File::isDirectory($seedStorageDirectory)) {
            File::makeDirectory($seedStorageDirectory, 0755, true);
        }

        $seedFileOne = $seedStorageDirectory . DIRECTORY_SEPARATOR . 'seed_incident_example_1.png';
        $seedFileTwo = $seedStorageDirectory . DIRECTORY_SEPARATOR . 'seed_incident_example_2.png';
        File::copy($exampleImageAbsolutePath, $seedFileOne);
        File::copy($exampleImageAbsolutePath, $seedFileTwo);

        $relativePaths = [
            'uploads/incidents/seed_incident_example_1.png',
            'uploads/incidents/seed_incident_example_2.png',
        ];

        foreach ($resolvedIncidents as $incident) {
            IncidentPhoto::query()->where('incident_id', $incident->incident_id)->delete();

            foreach ($relativePaths as $order => $relativePath) {
                IncidentPhoto::create([
                    'incident_id' => $incident->incident_id,
                    'photo_path' => $relativePath,
                    'sort_order' => $order,
                ]);
            }

            $incident->forceFill([
                'proof_photo_path' => $relativePaths[0],
            ])->save();
        }
    }
}
