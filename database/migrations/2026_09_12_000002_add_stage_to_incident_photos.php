<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('incident_photos', fn (Blueprint $table) => $table->string('stage', 20)->default('legacy'));
    }

    public function down(): void
    {
        Schema::table('incident_photos', fn (Blueprint $table) => $table->dropColumn('stage'));
    }
};
