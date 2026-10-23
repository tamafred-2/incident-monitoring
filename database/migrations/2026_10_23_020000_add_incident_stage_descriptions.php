<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('incidents', function (Blueprint $table) {
            $table->text('investigation_description')->nullable();
            $table->text('resolution_description')->nullable();
        });
    }
    public function down(): void {
        Schema::table('incidents', fn (Blueprint $table) => $table->dropColumn(['investigation_description', 'resolution_description']));
    }
};
