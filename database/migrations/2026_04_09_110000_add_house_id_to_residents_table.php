<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('residents') || Schema::hasColumn('residents', 'house_id')) {
            return;
        }

        Schema::table('residents', function (Blueprint $table) {
            $table->unsignedBigInteger('house_id')->nullable()->after('subdivision_id');
            $table->foreign('house_id')->references('house_id')->on('houses')->nullOnDelete();
            $table->index('house_id');
        });

        $housesBySubdivision = DB::table('houses')
            ->select('house_id', 'subdivision_id', 'block', 'lot')
            ->get()
            ->groupBy('subdivision_id');

        DB::table('residents')
            ->select('resident_id', 'subdivision_id', 'address_or_unit')
            ->orderBy('resident_id')
            ->get()
            ->each(function ($resident) use ($housesBySubdivision): void {
                $address = strtoupper(trim((string) $resident->address_or_unit));

                if ($address === '' || !$housesBySubdivision->has($resident->subdivision_id)) {
                    return;
                }

                $matchedHouse = $housesBySubdivision[$resident->subdivision_id]
                    ->first(function ($house) use ($address) {
                        $displayAddress = strtoupper(sprintf('Block %s Lot %s', trim((string) $house->block), trim((string) $house->lot)));

                        return $displayAddress === $address;
                    });

                if ($matchedHouse) {
                    DB::table('residents')
                        ->where('resident_id', $resident->resident_id)
                        ->update(['house_id' => $matchedHouse->house_id]);
                }
            });
    }

    public function down(): void
    {
        if (!Schema::hasTable('residents') || !Schema::hasColumn('residents', 'house_id')) {
            return;
        }

        Schema::table('residents', function (Blueprint $table) {
            $table->dropForeign(['house_id']);
            $table->dropIndex(['house_id']);
            $table->dropColumn('house_id');
        });
    }
};
