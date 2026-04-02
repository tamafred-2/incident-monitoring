<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('incident_photos')) {
            Schema::create('incident_photos', function (Blueprint $table) {
                $table->unsignedInteger('incident_photo_id', true);
                $table->unsignedInteger('incident_id');
                $table->string('photo_path', 255);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamp('created_at')->useCurrent();

                $table->index('incident_id');
                $table->index('sort_order');
            });
        }

        $existingPhotos = DB::table('incidents')
            ->select('incident_id', 'proof_photo_path')
            ->whereNotNull('proof_photo_path')
            ->where('proof_photo_path', '<>', '')
            ->get();

        foreach ($existingPhotos as $photo) {
            $alreadyExists = DB::table('incident_photos')
                ->where('incident_id', $photo->incident_id)
                ->where('photo_path', $photo->proof_photo_path)
                ->exists();

            if (!$alreadyExists) {
                DB::table('incident_photos')->insert([
                    'incident_id' => $photo->incident_id,
                    'photo_path' => $photo->proof_photo_path,
                    'sort_order' => 0,
                    'created_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_photos');
    }
};
