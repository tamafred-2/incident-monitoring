<?php

use App\Support\MySqlToSqliteSync;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('db:sync-mysql-to-sqlite {--fresh : Rebuild the SQLite schema before importing}', function (MySqlToSqliteSync $sync) {
    if ($this->option('fresh')) {
        $this->comment('Refreshing SQLite schema...');

        Artisan::call('migrate:fresh', [
            '--database' => 'sqlite',
            '--force' => true,
        ], $this->output);
    }

    $this->comment('Copying data from MySQL to SQLite...');

    $counts = $sync->sync(function (string $message): void {
        $this->line($message);
    });

    $this->newLine();
    $this->info('SQLite is now loaded with the current MySQL data.');

    foreach ($counts as $table => $count) {
        $this->line(sprintf('%s: %d', $table, $count));
    }
})->purpose('Copy the current XAMPP MySQL incident data into the local SQLite database');
