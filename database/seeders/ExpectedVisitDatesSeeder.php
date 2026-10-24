<?php

namespace Database\Seeders;

use App\Models\ExpectedVisit;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Random\Engine\Mt19937;
use Random\Randomizer;

class ExpectedVisitDatesSeeder extends Seeder
{
    public function run(): void
    {
        $timezone = config('app.timezone');
        $start = CarbonImmutable::parse('2026-10-23 00:00:00', $timezone);
        $end = CarbonImmutable::parse('2026-10-24 23:59:59', $timezone)->min(CarbonImmutable::now($timezone));
        if ($end->lt($start)) return;

        $random = new Randomizer(new Mt19937(20261024));
        $phones = array_map(fn ($i) => '09990000'.str_pad((string) $i, 3, '0', STR_PAD_LEFT), range(0, 7));
        $visits = ExpectedVisit::where('status', 'Expected')
            ->where('purpose', 'like', 'Scheduled family visit to %')
            ->whereHas('profile', fn ($query) => $query->whereIn('phone', $phones)
                ->where('id_photo_path', 'uploads/visitors/id-photos/sample-id.png'))
            ->orderBy('id')->get();

        DB::transaction(function () use ($visits, $random, $start, $end, $timezone): void {
            foreach ($visits as $index => $visit) {
                $created = CarbonImmutable::createFromTimestamp($random->getInt($start->timestamp, $end->timestamp), $timezone);
                $arrival = CarbonImmutable::parse('2026-10-25', $timezone)
                    ->addDays($index % 5)->setTime($random->getInt(9, 17), $random->getInt(0, 3) * 15);
                DB::table('expected_visits')->where('id', $visit->getKey())->update([
                    'created_at' => $created, 'updated_at' => $created, 'expected_at' => $arrival,
                ]);
            }
        });

        $this->command?->info('Updated '.$visits->count().' demo expected visits: created October 23–24, arrivals October 25–29, 2026.');
    }
}
