<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('incidents')) {
            Schema::create('incidents', function (Blueprint $table) {
                $table->unsignedInteger('incident_id', true);
                $table->unsignedInteger('subdivision_id');
                $table->string('title', 150);
                $table->text('description')->nullable();
                $table->string('category', 100)->nullable();
                $table->string('location', 150)->nullable();
                $table->dateTime('incident_date');
                $table->enum('status', ['Open', 'Under Investigation', 'Resolved', 'Closed'])->default('Open');
                $table->string('proof_photo_path', 255)->nullable();
                $table->unsignedInteger('reported_by');
                $table->unsignedInteger('assigned_to')->nullable();
                $table->unsignedInteger('verified_resident_id')->nullable();
                $table->string('verification_method', 50)->nullable();
                $table->dateTime('verified_at')->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->index('subdivision_id');
                $table->index('status');
                $table->index('incident_date');
                $table->index('reported_by');
                $table->index('verified_resident_id');
                $table->index('proof_photo_path');
            });

            return;
        }

        Schema::table('incidents', function (Blueprint $table) {
            if (!Schema::hasColumn('incidents', 'proof_photo_path')) {
                $table->string('proof_photo_path', 255)->nullable()->after('status');
            }
            if (!Schema::hasColumn('incidents', 'verified_resident_id')) {
                $table->unsignedInteger('verified_resident_id')->nullable()->after('assigned_to');
            }
            if (!Schema::hasColumn('incidents', 'verification_method')) {
                $table->string('verification_method', 50)->nullable()->after('verified_resident_id');
            }
            if (!Schema::hasColumn('incidents', 'verified_at')) {
                $table->dateTime('verified_at')->nullable()->after('verification_method');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incidents');
    }
};
