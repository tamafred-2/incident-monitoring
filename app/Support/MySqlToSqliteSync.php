<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class MySqlToSqliteSync
{
    private const SOURCE_CONNECTION = 'import_mysql';

    private const TARGET_CONNECTION = 'sqlite';

    /**
     * Tables to copy in parent-to-child order.
     *
     * @var array<int, array{name: string, key: string}>
     */
    private const TABLES = [
        ['name' => 'subdivisions', 'key' => 'subdivision_id'],
        ['name' => 'residents', 'key' => 'resident_id'],
        ['name' => 'users', 'key' => 'user_id'],
        ['name' => 'visitors', 'key' => 'visitor_id'],
        ['name' => 'gate_visitor_logs', 'key' => 'gate_log_id'],
        ['name' => 'incidents', 'key' => 'incident_id'],
        ['name' => 'incident_photos', 'key' => 'incident_photo_id'],
    ];

    /**
     * Copy the current MySQL data set into SQLite.
     *
     * @param  null|callable(string): void  $progress
     * @return array<string, int>
     */
    public function sync(?callable $progress = null): array
    {
        $source = DB::connection(self::SOURCE_CONNECTION);
        $target = DB::connection(self::TARGET_CONNECTION);
        $counts = [];

        $target->statement('PRAGMA foreign_keys = OFF');

        try {
            $target->beginTransaction();

            foreach (array_reverse(self::TABLES) as $table) {
                $target->table($table['name'])->delete();
            }

            $quotedTableNames = implode(
                ', ',
                array_map(
                    static fn (array $table): string => "'" . str_replace("'", "''", $table['name']) . "'",
                    self::TABLES
                )
            );

            $target->statement("DELETE FROM sqlite_sequence WHERE name IN ($quotedTableNames)");

            foreach (self::TABLES as $table) {
                $rows = $source->table($table['name'])
                    ->orderBy($table['key'])
                    ->get()
                    ->map(static fn (object $row): array => (array) $row)
                    ->all();

                $counts[$table['name']] = count($rows);

                if ($progress !== null) {
                    $progress("Syncing {$table['name']} ({$counts[$table['name']]} rows)");
                }

                foreach (array_chunk($rows, 200) as $chunk) {
                    $target->table($table['name'])->insert($chunk);
                }
            }

            $target->commit();
        } catch (\Throwable $exception) {
            $target->rollBack();

            throw $exception;
        } finally {
            $target->statement('PRAGMA foreign_keys = ON');
        }

        return $counts;
    }
}
