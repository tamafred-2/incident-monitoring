<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('residents', function (Blueprint $table) {
            $table->boolean('is_deceased')->default(false);
        });
        Schema::create('house_ownership_transfers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('house_id');
            $table->unsignedBigInteger('from_resident_id')->unique();
            $table->unsignedBigInteger('to_resident_id');
            $table->string('previous_owner_name');
            $table->string('new_owner_name');
            $table->string('previous_relationship')->nullable();
            $table->unsignedBigInteger('confirmed_by');
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('house_ownership_transfers');
        Schema::table('residents', fn (Blueprint $table) => $table->dropColumn('is_deceased'));
    }
};
