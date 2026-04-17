<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visitor_requests', function (Blueprint $table) {
            $table->id('request_id');
            $table->unsignedInteger('resident_id');
            $table->unsignedInteger('subdivision_id');
            $table->string('visitor_name', 150);
            $table->string('phone', 40)->nullable();
            $table->text('purpose')->nullable();
            $table->enum('status', ['Pending', 'Approved', 'Declined'])->default('Pending');
            $table->timestamp('requested_at')->useCurrent();
            $table->timestamp('responded_at')->nullable();

            $table->index('resident_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visitor_requests');
    }
};
