<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expected_visits', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('visitor_profile_id');
            $table->unsignedBigInteger('subdivision_id');
            $table->unsignedBigInteger('resident_id');
            $table->unsignedBigInteger('recorded_by');
            $table->dateTime('expected_at');
            $table->text('purpose')->nullable();
            $table->string('status', 20)->default('Expected');
            $table->unsignedBigInteger('visitor_id')->nullable();
            $table->timestamps();
            $table->index(['subdivision_id', 'status', 'expected_at']);
            $table->index('visitor_profile_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expected_visits');
    }
};
