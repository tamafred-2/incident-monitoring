<?php

namespace Database\Seeders;

use App\Models\VisitorProfile;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Random\Engine\Mt19937;
use Random\Randomizer;

class VisitorProfileDatesSeeder extends Seeder
{
    public function run(): void
    {
        $now = CarbonImmutable::now(config('app.timezone'));
        $start = $now->startOfYear();
        $random = new Randomizer(new Mt19937(2026));
        $phones = ['09181230000', '09209876543'];
        for ($i = 0; $i < 1198; $i++) {
            $phones[] = '09'.(100000000 + 300000 + $i * 19);
        }
        for ($i = 0; $i < 8; $i++) {
            $phones[] = '09990000'.str_pad((string) $i, 3, '0', STR_PAD_LEFT);
        }

        // Only the known demo identities using the shared sample ID are changed.
        $profiles = VisitorProfile::where('id_photo_path', 'uploads/visitors/id-photos/sample-id.png')
            ->whereIn('phone', $phones)
            ->withMin(['visits' => fn ($query) => $query->withTrashed()], 'check_in')
            ->orderBy('profile_id')->get();

        DB::transaction(function () use ($profiles, $start, $now, $random): void {
            foreach ($profiles as $profile) {
                $latest = $profile->visits_min_check_in
                    ? CarbonImmutable::parse($profile->visits_min_check_in, config('app.timezone'))->min($now)
                    : $now;
                if ($latest->lt($start)) continue;
                $earliest = $profile->visits_min_check_in ? $latest->subDays(7)->max($start) : $start;
                $created = CarbonImmutable::createFromTimestamp($random->getInt($earliest->timestamp, $latest->timestamp), config('app.timezone'));
                DB::table('visitor_profiles')->where('profile_id', $profile->getKey())->update(['created_at' => $created]);
            }
        });
        $this->command?->info('Demo visitor profile creation dates spread from January through the current date.');
    }
}
