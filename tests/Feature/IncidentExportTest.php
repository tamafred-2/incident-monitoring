<?php

namespace Tests\Feature;

use App\Models\Incident;
use App\Models\Subdivision;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IncidentExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_pdf_downloads_with_a_webp_photo_when_webp_support_is_missing(): void
    {
        if (function_exists('imagecreatefromwebp')) {
            $this->markTestSkipped('This regression covers servers without WebP support.');
        }
        \Illuminate\Support\Facades\Storage::fake('public');
        $path = 'uploads/incidents/test.webp';
        \Illuminate\Support\Facades\Storage::disk('public')->put($path, base64_decode('UklGRiIAAABXRUJQVlA4IBYAAAAwAQCdASoBAAEADsD+JaQAA3AAAAAA'));
        $this->assertSame(IMAGETYPE_WEBP, getimagesize(\Illuminate\Support\Facades\Storage::disk('public')->path($path))[2]);
        $subdivision = Subdivision::create(['subdivision_name' => 'Export', 'status' => 'Active']);
        $user = User::factory()->create(['role' => 'staff', 'subdivision_id' => $subdivision->getKey()]);
        $this->actingAs($user);
        Incident::create([
            'subdivision_id' => $subdivision->getKey(), 'reported_by' => $user->getKey(),
            'category' => 'Security', 'location' => 'Gate', 'status' => 'Resolved',
            'incident_date' => now()->subDay(), 'reported_at' => now(), 'proof_photo_path' => $path,
        ]);
        $response = $this->get(route('incidents.export', ['format' => 'pdf']))
            ->assertOk()->assertDownload()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $this->assertStringContainsString('/URI', $response->getContent());
    }

    public function test_downloads_select_latest_100_matching_incidents_even_with_ascending_sort(): void
    {
        $this->freezeTime();
        $subdivision = Subdivision::create(['subdivision_name' => 'Export', 'status' => 'Active']);
        $user = User::factory()->create(['role' => 'staff', 'subdivision_id' => $subdivision->getKey()]);
        $this->actingAs($user);
        for ($i = 0; $i < 105; $i++) {
            Incident::create([
                'subdivision_id' => $subdivision->getKey(), 'reported_by' => $user->getKey(),
                'category' => 'Security', 'description' => 'Selected', 'location' => 'Location'.$i,
                'status' => 'Resolved', 'reported_at' => now()->subMinutes(105 - $i),
                'incident_date' => now()->subDay(), 'resolved_at' => now(),
            ]);
        }
        $excluded = Incident::firstOrFail()->replicate();
        $excluded->description = 'Excluded';
        $excluded->reported_at = now();
        $excluded->save();
        $query = ['q' => 'Selected', 'reported_sort' => 'asc'];
        $csv = $this->get(route('incidents.export', $query + ['format' => 'excel']))->assertOk()->assertDownload();
        $rows = array_map('str_getcsv', explode("\n", trim($csv->streamedContent())));
        $this->assertCount(101, $rows);
        $this->assertSame('Location104', $rows[1][1]);
        $this->assertSame('Location5', $rows[100][1]);

        $pdf = \Mockery::mock(\Barryvdh\DomPDF\PDF::class);
        $pdf->shouldReceive('setPaper')->with('a4', 'landscape')->once()->andReturnSelf();
        $pdf->shouldReceive('download')->once()->andReturn(response('%PDF-test'));
        Pdf::shouldReceive('loadView')->once()->withArgs(function ($view, $data) {
            $this->assertSame('incidents.report-print', $view);
            $this->assertCount(100, $data['reportRows']);
            $this->assertSame('Location104', $data['reportRows'][0]['location']);
            $this->assertSame('Location5', $data['reportRows'][99]['location']);

            return true;
        })->andReturn($pdf);
        $this->get(route('incidents.export', $query + ['format' => 'pdf']))->assertOk();
    }
}
