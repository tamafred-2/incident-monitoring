<?php

namespace Database\Seeders;

use App\Support\MySqlToSqliteSync;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (DB::getDefaultConnection() === 'sqlite') {
            app(MySqlToSqliteSync::class)->sync();

            return;
        }

        $this->call([
            XamppIncidentDbSeeder::class,
        ]);
    }
}
