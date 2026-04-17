<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visitor_requests', function (Blueprint $table) {
            $table->string('surname', 100)->nullable()->after('visitor_name');
            $table->string('first_name', 100)->nullable()->after('surname');
            $table->string('middle_initials', 20)->nullable()->after('first_name');
            $table->string('extension', 20)->nullable()->after('middle_initials');
            $table->string('plate_number', 30)->nullable()->after('phone');
            $table->string('id_photo_path', 255)->nullable()->after('plate_number');
            $table->string('house_address_or_unit', 120)->nullable()->after('id_photo_path');
            $table->unsignedBigInteger('visitor_id')->nullable()->after('request_id');
        });
    }

    public function down(): void
    {
        Schema::table('visitor_requests', function (Blueprint $table) {
            $table->dropColumn([
                'surname', 'first_name', 'middle_initials', 'extension',
                'plate_number', 'id_photo_path', 'house_address_or_unit', 'visitor_id',
            ]);
        });
    }
};
