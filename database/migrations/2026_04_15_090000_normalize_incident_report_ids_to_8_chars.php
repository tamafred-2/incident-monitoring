<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!DB::getSchemaBuilder()->hasTable('incidents') || !DB::getSchemaBuilder()->hasColumn('incidents', 'report_id')) {
            return;
        }

        DB::table('incidents')->select(['incident_id', 'report_id'])->orderBy('incident_id')->get()->each(function ($incident): void {
            $normalized = strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', (string) $incident->report_id));

            if ($normalized === '') {
                $normalized = strtoupper(str_pad(dechex((int) $incident->incident_id), 8, '0', STR_PAD_LEFT));
            } elseif (strlen($normalized) < 8) {
                $normalized = str_pad($normalized, 8, '0', STR_PAD_LEFT);
            } elseif (strlen($normalized) > 8) {
                $normalized = substr($normalized, -8);
            }

            DB::table('incidents')
                ->where('incident_id', $incident->incident_id)
                ->update(['report_id' => $normalized]);
        });
    }

    public function down(): void
    {
        if (!DB::getSchemaBuilder()->hasTable('incidents') || !DB::getSchemaBuilder()->hasColumn('incidents', 'report_id')) {
            return;
        }

        DB::table('incidents')->select(['incident_id', 'report_id'])->orderBy('incident_id')->get()->each(function ($incident): void {
            $reportId = strtoupper((string) $incident->report_id);

            if (strlen($reportId) !== 8) {
                return;
            }

            DB::table('incidents')
                ->where('incident_id', $incident->incident_id)
                ->update(['report_id' => 'RPT-' . $reportId]);
        });
    }
};
