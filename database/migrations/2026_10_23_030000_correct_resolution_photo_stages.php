<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // The edit form's resolution input previously saved supporting photos
        // when the user saved notes before changing the incident to Resolved.
        DB::table('incidents')->whereNotNull('resolution_description')->where('resolution_description', '!=', '')
            ->orderBy('incident_id')->chunkById(100, function ($incidents) {
                foreach ($incidents as $incident) {
                    $lastInvestigation = DB::table('incident_photos')->where('incident_id', $incident->incident_id)
                        ->where('stage', 'investigation')->max('created_at');
                    if ($lastInvestigation) {
                        DB::table('incident_photos')->where('incident_id', $incident->incident_id)
                            ->where('stage', 'supporting')->where('created_at', '>', $lastInvestigation)
                            ->update(['stage' => 'resolution']);
                    }
                }
            }, 'incident_id');
    }

    public function down(): void
    {
        // Original stages cannot be reconstructed reliably; preserve corrected evidence.
    }
};
