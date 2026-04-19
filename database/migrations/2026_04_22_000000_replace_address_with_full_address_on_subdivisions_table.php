<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subdivisions', function (Blueprint $table) {
            $table->string('street', 255)->nullable()->after('subdivision_name');
            $table->string('city', 100)->nullable()->after('street');
            $table->string('province', 100)->nullable()->after('city');
            $table->string('zip', 20)->nullable()->after('province');
        });

        // Migrate existing address value into street
        DB::table('subdivisions')->whereNotNull('address')->get()->each(function ($row) {
            DB::table('subdivisions')->where('subdivision_id', $row->subdivision_id)->update([
                'street' => $row->address,
            ]);
        });

        Schema::table('subdivisions', function (Blueprint $table) {
            $table->dropColumn('address');
        });
    }

    public function down(): void
    {
        Schema::table('subdivisions', function (Blueprint $table) {
            $table->string('address', 255)->nullable()->after('subdivision_name');
        });

        DB::table('subdivisions')->whereNotNull('street')->get()->each(function ($row) {
            DB::table('subdivisions')->where('subdivision_id', $row->subdivision_id)->update([
                'address' => $row->street,
            ]);
        });

        Schema::table('subdivisions', function (Blueprint $table) {
            $table->dropColumn(['street', 'city', 'province', 'zip']);
        });
    }
};
