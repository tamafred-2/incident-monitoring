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
        if (Schema::hasTable('visitors')) {
            return;
        }

        Schema::create('visitors', function (Blueprint $table) {
            $table->unsignedInteger('visitor_id', true);
            $table->unsignedInteger('subdivision_id');
            $table->string('full_name', 100);
            $table->string('phone', 20)->nullable();
            $table->string('id_number', 50)->nullable();
            $table->string('company', 100)->nullable();
            $table->text('purpose')->nullable();
            $table->string('host_employee', 100)->nullable();
            $table->dateTime('check_in');
            $table->dateTime('check_out')->nullable();
            $table->enum('status', ['Inside', 'Checked Out'])->default('Inside');

            $table->index('subdivision_id');
            $table->index('status');
            $table->index('check_in');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visitors');
    }
};
