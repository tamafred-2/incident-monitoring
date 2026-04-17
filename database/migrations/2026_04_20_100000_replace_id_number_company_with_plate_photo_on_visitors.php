<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visitors', function (Blueprint $table) {
            if (Schema::hasColumn('visitors', 'id_number')) {
                $table->dropColumn('id_number');
            }
            if (Schema::hasColumn('visitors', 'company')) {
                $table->dropColumn('company');
            }
        });

        Schema::table('visitors', function (Blueprint $table) {
            $table->string('plate_number', 30)->nullable()->after('phone');
            $table->string('id_photo_path', 255)->nullable()->after('plate_number');
        });
    }

    public function down(): void
    {
        Schema::table('visitors', function (Blueprint $table) {
            $table->dropColumn(['plate_number', 'id_photo_path']);
            $table->string('id_number', 80)->nullable();
            $table->string('company', 150)->nullable();
        });
    }
};
