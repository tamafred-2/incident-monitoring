<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\House;
use App\Models\Incident;
use App\Models\Resident;
use App\Models\Subdivision;
use App\Models\User;
use App\Models\Visitor;
use App\Models\VisitorRequest;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Random\Engine\Mt19937;
use Random\Randomizer;
use RuntimeException;

class ConsultationActivitySeeder extends Seeder
{
    /** Add clearly marked consultation examples without rebuilding existing data. */
    public function run(): void
    {
        $subdivision = Subdivision::where('status', 'Active')->orderBy('subdivision_id')->first();
        if (! $subdivision) {
            throw new RuntimeException('Consultation examples need an existing active subdivision.');
        }
        $houses = House::where('subdivision_id', $subdivision->getKey())->orderBy('house_id')->get();
        $reporter = User::where('subdivision_id', $subdivision->getKey())->where('role', UserRole::Security)
            ->where('is_active', true)->first() ?? User::where('role', UserRole::Admin)->where('is_active', true)->first();
        if ($houses->isEmpty() || ! $reporter) {
            throw new RuntimeException('Consultation examples need an existing house and an active guard or admin.');
        }
        $staff = User::where('subdivision_id', $subdivision->getKey())->where('role', UserRole::Staff)->where('is_active', true)->first();
        $residents = Resident::where('subdivision_id', $subdivision->getKey())->where('status', 'Active')
            ->orderBy('resident_id')->get();
        $legacy = DB::connection()->getDriverName() === 'sqlite'
            && str_contains((string) DB::table('sqlite_master')->where('name', 'incidents')->value('sql'), "'Reported'");
        $start = CarbonImmutable::parse('2026-10-01', config('app.timezone'));
        $end = CarbonImmutable::parse('2026-10-08', config('app.timezone'));
        $names = [
            ['Elena', 'Cruz'], ['Marco', 'Reyes'], ['Mia', 'Santos'], ['Paolo', 'Ramos'],
            ['Joyce', 'Tan'], ['Luis', 'Mendoza'], ['Clara', 'Reyes'], ['Ben', 'Flores'],
        ];
        $examples = [
            ['Security', 'The entrance gate lock was sticking and would not close properly.', 'Resolved'],
            ['Property Damage', 'A streetlight was flickering near the house.', 'Resolved'],
            ['Safety', 'A loose drainage cover needs inspection.', $legacy ? 'Investigating' : 'Under Investigation'],
            ['Noise Complaint', 'A resident reported loud music near the house.', $legacy ? 'Reported' : 'Open'],
        ];
        $added = ['incidents' => 0, 'visitors' => 0, 'visitor requests' => 0];
        $daily = [];

        DB::transaction(function () use ($subdivision, $houses, $reporter, $staff, $residents, $start, $end, $names, $examples, &$added, &$daily): void {
            $incidentKeys = $visitorKeys = $requestKeys = [];
            for ($day = $start; $day->lte($end); $day = $day->addDay()) {
                // Local, reproducible randomness keeps reruns stable without affecting Faker or the app.
                $random = new Randomizer(new Mt19937((int) $day->format('Ymd')));
                $busyFactor = $random->getInt(1, 4) === 1 ? 3 : 1;
                $incidentCount = $random->getInt(2, 7) * $busyFactor;
                $visitorCount = $random->getInt(6, 18) * $busyFactor;
                $requestCount = $residents->isEmpty() ? 0 : $random->getInt(1, min(9, $visitorCount));
                $daily[] = [$day->toDateString(), $incidentCount, $visitorCount, $requestCount];
                $dayVisitors = [];

                for ($index = 0; $index < $incidentCount; $index++) {
                    [$category, $description] = $examples[$random->getInt(0, count($examples) - 1)];
                    $house = $houses[$random->getInt(0, $houses->count() - 1)];
                    $reportedAt = $day->setTime($random->getInt(6, 18), $random->getInt(0, 59));
                    $roll = $index === 0 ? 1 : $random->getInt(1, 100);
                    $status = $roll <= 55 ? 'Resolved' : ($roll <= 85 ? $examples[2][2] : $examples[3][2]);
                    $description = '[Consultation demo '.$day->toDateString().' #'.($index + 1).'] '.$description;
                    $incidentKeys[] = $description;
                    $incident = Incident::withTrashed()->updateOrCreate([
                        'subdivision_id' => $subdivision->getKey(), 'description' => $description,
                    ], [
                        'house_id' => $house->getKey(), 'category' => $category, 'location' => $house->display_address,
                        'incident_date' => $reportedAt, 'reported_at' => $reportedAt,
                        'resolved_at' => $status === 'Resolved' ? $reportedAt->addMinutes($random->getInt(35, 260)) : null,
                        'status' => $status, 'reported_by' => $reporter->getKey(),
                        'assigned_to' => $roll <= 85 ? $staff?->getKey() : null,
                    ]);
                    $added['incidents'] += (int) $incident->wasRecentlyCreated;
                }

                $purposes = ['Family visit', 'Parcel delivery', 'Plumbing repair', 'Electrical maintenance', 'Homeowner meeting', 'Food delivery'];
                for ($index = 0; $index < $visitorCount; $index++) {
                    [$firstName, $surname] = $names[$random->getInt(0, count($names) - 1)];
                    $resident = $residents->isNotEmpty() ? $residents[$random->getInt(0, $residents->count() - 1)] : null;
                    $house = $houses[$random->getInt(0, $houses->count() - 1)];
                    $checkIn = $day->setTime($random->getInt(6, 19), $random->getInt(0, 59));
                    $inside = $day->gte($end->subDay()) && ($index === $visitorCount - 1 || $random->getInt(1, 100) <= 20);
                    $purpose = '[Consultation demo '.$day->toDateString().' #'.($index + 1).'] '.$purposes[$random->getInt(0, count($purposes) - 1)];
                    $visitorKeys[] = $purpose;
                    $visitor = Visitor::withTrashed()->updateOrCreate([
                        'subdivision_id' => $subdivision->getKey(), 'purpose' => $purpose,
                    ], [
                        'first_name' => $firstName, 'surname' => $surname,
                        'phone' => '0917'.str_pad((string) $random->getInt(1, 9999999), 7, '0', STR_PAD_LEFT),
                        'host_employee' => $resident?->full_name,
                        'house_address_or_unit' => $resident?->address_or_unit ?: $house->display_address,
                        'check_in' => $checkIn, 'check_out' => $inside ? null : $checkIn->addMinutes($random->getInt(20, 180)),
                        'status' => $inside ? 'Inside' : 'Checked Out',
                    ]);
                    $dayVisitors[] = $visitor;
                    $added['visitors'] += (int) $visitor->wasRecentlyCreated;
                }

                for ($index = 0; $index < $requestCount; $index++) {
                    $visitor = $dayVisitors[$index];
                    $resident = $residents[$random->getInt(0, $residents->count() - 1)];
                    $pending = $day->gte($end->subDay()) && ($index === $requestCount - 1 || $random->getInt(1, 100) <= 25);
                    $approved = ! $pending && $random->getInt(1, 100) <= 75;
                    $purpose = '[Consultation demo '.$day->toDateString().' #'.($index + 1).'] Approval request';
                    $requestKeys[] = $purpose;
                    $request = VisitorRequest::updateOrCreate([
                        'subdivision_id' => $subdivision->getKey(), 'purpose' => $purpose,
                    ], [
                        'resident_id' => $resident->getKey(), 'visitor_id' => $approved ? $visitor->getKey() : null,
                        'visitor_name' => $approved ? $visitor->full_name : 'Nina Dela Cruz',
                        'first_name' => $approved ? $visitor->first_name : 'Nina', 'surname' => $approved ? $visitor->surname : 'Dela Cruz',
                        'phone' => $visitor->phone, 'house_address_or_unit' => $resident->address_or_unit ?: $houses->first()->display_address,
                        'requested_at' => $visitor->check_in->copy()->subMinutes($random->getInt(10, 25)),
                        'responded_at' => $pending ? null : $visitor->check_in->copy()->subMinutes($random->getInt(1, 9)),
                        'status' => $approved ? 'Approved' : ($pending ? 'Pending' : 'Declined'),
                    ]);
                    $added['visitor requests'] += (int) $request->wasRecentlyCreated;
                }
            }

            // Replace only obsolete rows from this explicitly marked demo dataset.
            VisitorRequest::where('subdivision_id', $subdivision->getKey())->where('purpose', 'like', '[Consultation demo 2026-10-%')
                ->whereNotIn('purpose', $requestKeys)->delete();
            Visitor::withTrashed()->where('subdivision_id', $subdivision->getKey())->where('purpose', 'like', '[Consultation demo 2026-10-%')
                ->whereNotIn('purpose', $visitorKeys)->get()->each->forceDelete();
            Incident::withTrashed()->where('subdivision_id', $subdivision->getKey())->where('description', 'like', '[Consultation demo 2026-10-%')
                ->whereNotIn('description', $incidentKeys)->get()->each->forceDelete();
        });

        $this->command?->table(['Date', 'Incidents', 'Visitors', 'Requests'], $daily);
        $this->command?->info('Updated consultation demo data for October 1–8, 2026: '.collect($added)
            ->map(fn ($count, $type) => $count.' '.$type)->implode(', ').'. Records outside the consultation demo dataset were retained.');
    }
}
