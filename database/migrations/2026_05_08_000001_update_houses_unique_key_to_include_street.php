<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('houses', function (Blueprint $table) {
            $table->dropUnique('houses_subdivision_id_block_lot_unique');
            $table->unique(
                ['subdivision_id', 'street', 'block', 'lot'],
                'houses_subdivision_id_street_block_lot_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('houses', function (Blueprint $table) {
            $table->dropUnique('houses_subdivision_id_street_block_lot_unique');
            $table->unique(
                ['subdivision_id', 'block', 'lot'],
                'houses_subdivision_id_block_lot_unique'
            );
        });
    }
};

