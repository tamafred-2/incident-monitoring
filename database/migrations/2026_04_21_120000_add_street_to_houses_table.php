<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('houses', 'street')) {
            Schema::table('houses', function (Blueprint $table) {
                $table->string('street', 120)->nullable()->after('subdivision_id');
                $table->index('street');
            });
        }

        DB::table('houses')
            ->select('house_id', 'subdivision_id')
            ->whereNull('street')
            ->orderBy('house_id')
            ->chunkById(200, function ($houses): void {
                $subdivisionIds = $houses->pluck('subdivision_id')->unique()->values()->all();

                $streetsBySubdivision = DB::table('subdivisions')
                    ->whereIn('subdivision_id', $subdivisionIds)
                    ->pluck('street', 'subdivision_id');

                foreach ($houses as $house) {
                    $street = $streetsBySubdivision[$house->subdivision_id] ?? null;

                    if ($street !== null && trim((string) $street) !== '') {
                        DB::table('houses')
                            ->where('house_id', $house->house_id)
                            ->update(['street' => $street]);
                    }
                }
            }, 'house_id');
    }

    public function down(): void
    {
        if (Schema::hasColumn('houses', 'street')) {
            Schema::table('houses', function (Blueprint $table) {
                $table->dropIndex(['street']);
                $table->dropColumn('street');
            });
        }
    }
};
