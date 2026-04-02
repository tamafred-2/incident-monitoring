<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('gate_visitor_logs')) {
            return;
        }

        Schema::table('gate_visitor_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('gate_visitor_logs', 'surname')) {
                $table->string('surname', 100)->nullable()->after('full_name');
            }

            if (!Schema::hasColumn('gate_visitor_logs', 'first_name')) {
                $table->string('first_name', 100)->nullable()->after('surname');
            }

            if (!Schema::hasColumn('gate_visitor_logs', 'middle_name')) {
                $table->string('middle_name', 100)->nullable()->after('first_name');
            }

            if (!Schema::hasColumn('gate_visitor_logs', 'extension')) {
                $table->string('extension', 20)->nullable()->after('middle_name');
            }
        });

        DB::table('gate_visitor_logs')
            ->select(['gate_log_id', 'full_name'])
            ->orderBy('gate_log_id')
            ->get()
            ->each(function (object $log): void {
                $parts = preg_split('/\s+/', trim((string) $log->full_name)) ?: [];
                $parts = array_values(array_filter($parts, static fn (string $value): bool => $value !== ''));

                $extension = null;
                if ($parts !== []) {
                    $lastPart = strtolower(rtrim(end($parts), '.'));
                    if (in_array($lastPart, ['jr', 'sr', 'ii', 'iii', 'iv', 'v'], true)) {
                        $extension = array_pop($parts);
                    }
                }

                $firstName = $parts[0] ?? null;
                $surname = count($parts) > 1 ? array_pop($parts) : null;
                $middleName = count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : null;

                DB::table('gate_visitor_logs')
                    ->where('gate_log_id', $log->gate_log_id)
                    ->update([
                        'first_name' => $firstName,
                        'middle_name' => $middleName !== '' ? $middleName : null,
                        'surname' => $surname,
                        'extension' => $extension,
                    ]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('gate_visitor_logs')) {
            return;
        }

        Schema::table('gate_visitor_logs', function (Blueprint $table) {
            foreach (['extension', 'middle_name', 'first_name', 'surname'] as $column) {
                if (Schema::hasColumn('gate_visitor_logs', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
