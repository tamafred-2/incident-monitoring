<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visitors', function (Blueprint $table) {
            $table->unsignedSmallInteger('allowed_minutes')->nullable();
            $table->text('overdue_action')->nullable();
            $table->string('overdue_action_by')->nullable();
            $table->timestamp('overdue_action_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('visitors', fn (Blueprint $table) => $table->dropColumn(['allowed_minutes', 'overdue_action', 'overdue_action_by', 'overdue_action_at']));
    }
};
