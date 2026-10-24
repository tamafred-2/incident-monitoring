<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visitors', fn (Blueprint $table) => $table->json('overdue_action_history')->nullable());
    }

    public function down(): void
    {
        Schema::table('visitors', fn (Blueprint $table) => $table->dropColumn('overdue_action_history'));
    }
};
