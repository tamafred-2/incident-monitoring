<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subdivisions', function (Blueprint $table) {
            $table->string('secondary_contact_person', 100)->nullable()->after('contact_number');
            $table->string('secondary_contact_number', 20)->nullable()->after('secondary_contact_person');
            $table->string('secondary_email', 100)->nullable()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('subdivisions', function (Blueprint $table) {
            $table->dropColumn(['secondary_contact_person', 'secondary_contact_number', 'secondary_email']);
        });
    }
};
