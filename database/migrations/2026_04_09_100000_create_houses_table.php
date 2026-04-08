<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('houses', function (Blueprint $table) {
            $table->id('house_id');
            $table->unsignedBigInteger('subdivision_id');
            $table->string('block', 30);
            $table->string('lot', 30);
            $table->timestamp('created_at')->nullable();

            $table->foreign('subdivision_id')
                ->references('subdivision_id')
                ->on('subdivisions')
                ->cascadeOnDelete();

            $table->unique(['subdivision_id', 'block', 'lot']);
            $table->index('subdivision_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('houses');
    }
};
