<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visitor_profiles', function (Blueprint $table) {
            $table->bigIncrements('profile_id');
            $table->unsignedInteger('subdivision_id')->index();
            $table->string('surname', 100)->nullable();
            $table->string('first_name', 100)->nullable();
            $table->string('middle_initials', 20)->nullable();
            $table->string('extension', 20)->nullable();
            $table->string('full_name', 255)->index();
            $table->string('phone', 40)->nullable()->index();
            $table->string('id_photo_path')->nullable();
            $table->timestamps();
        });
        Schema::table('visitors', function (Blueprint $table) {
            $table->unsignedBigInteger('visitor_profile_id')->nullable()->index();
            $table->foreign('visitor_profile_id')->references('profile_id')->on('visitor_profiles')->restrictOnDelete();
        });

        // A name alone is not an identity: reuse only exact name + nonempty phone within a subdivision.
        $profiles = [];
        DB::table('visitors')->orderBy('visitor_id')->chunkById(200, function ($visits) use (&$profiles): void {
            foreach ($visits as $visit) {
                $phone = trim((string) $visit->phone);
                $key = $phone === '' ? 'visit:'.$visit->visitor_id : json_encode([$visit->subdivision_id, mb_strtolower(trim($visit->full_name)), $phone]);
                if (! isset($profiles[$key])) {
                    $profiles[$key] = DB::table('visitor_profiles')->insertGetId([
                        'subdivision_id' => $visit->subdivision_id,
                        'surname' => $visit->surname, 'first_name' => $visit->first_name,
                        'middle_initials' => $visit->middle_initials, 'extension' => $visit->extension,
                        'full_name' => $visit->full_name, 'phone' => $visit->phone, 'id_photo_path' => $visit->id_photo_path,
                        'created_at' => now(), 'updated_at' => now(),
                    ], 'profile_id');
                }
                DB::table('visitors')->where('visitor_id', $visit->visitor_id)->update(['visitor_profile_id' => $profiles[$key]]);
            }
        }, 'visitor_id');
    }

    public function down(): void
    {
        Schema::table('visitors', function (Blueprint $table) {
            $table->dropForeign(['visitor_profile_id']);
            $table->dropIndex(['visitor_profile_id']);
            $table->dropColumn('visitor_profile_id');
        });
        Schema::dropIfExists('visitor_profiles');
    }
};
