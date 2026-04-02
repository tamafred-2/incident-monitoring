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
        if (Schema::hasTable('gate_visitor_logs')) {
            return;
        }

        Schema::create('gate_visitor_logs', function (Blueprint $table) {
            $table->unsignedInteger('gate_log_id', true);
            $table->unsignedInteger('subdivision_id');
            $table->unsignedInteger('logged_by_user_id')->nullable();
            $table->string('full_name', 150);
            $table->string('address', 255)->nullable();
            $table->string('contact_number', 40)->nullable();
            $table->string('person_to_visit', 150)->nullable();
            $table->string('house_block_lot', 120)->nullable();
            $table->text('purpose')->nullable();
            $table->dateTime('time_in');
            $table->dateTime('time_out')->nullable();
            $table->enum('status', ['Inside', 'Exited'])->default('Inside');
            $table->enum('source', ['qr', 'ocr', 'manual'])->default('manual');
            $table->text('qr_payload')->nullable();
            $table->string('id_number_ocr', 80)->nullable();
            $table->string('photo_path', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['subdivision_id', 'status']);
            $table->index('time_in');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gate_visitor_logs');
    }
};
