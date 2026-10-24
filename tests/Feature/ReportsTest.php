<?php

namespace Tests\Feature;

use App\Models\{Incident, Subdivision, User, Visitor};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_security_cannot_access_report_pages_previews_or_downloads(): void
    {
        $area = Subdivision::create(['subdivision_name' => 'North', 'status' => 'Active']);
        $this->actingAs(User::factory()->create(['role' => 'security', 'subdivision_id' => $area->getKey()]));
        foreach (['reports.index', 'incidents.print', 'visitors.print'] as $route) {
            $this->get(route($route))->assertRedirect(route('dashboard'));
        }
        foreach (['incidents.export', 'visitors.export'] as $route) {
            foreach (['excel', 'pdf'] as $format) {
                $this->get(route($route, ['format' => $format]))->assertRedirect(route('dashboard'));
            }
        }
        foreach (['incidents.index', 'visitors.index'] as $route) {
            $this->withoutVite()->get(route($route))->assertOk()
                ->assertDontSee(route('reports.index'), false);
        }
    }

    public function test_preview_and_print_use_photo_urls_without_reading_image_bytes(): void
    {
        $area = Subdivision::create(['subdivision_name' => 'North', 'status' => 'Active']);
        $user = User::factory()->create(['role' => 'staff', 'subdivision_id' => $area->getKey()]);
        $path = 'uploads/incidents/large-proof.jpg';
        Incident::create([
            'subdivision_id' => $area->getKey(), 'reported_by' => $user->getKey(),
            'location' => 'Gate', 'status' => 'Resolved', 'incident_date' => now(),
            'reported_at' => now(), 'proof_photo_path' => $path,
        ]);
        \Illuminate\Support\Facades\Storage::fake('public');
        \Illuminate\Support\Facades\Storage::disk('public')->put($path, 'large image placeholder');
        $absolutePath = \Illuminate\Support\Facades\Storage::disk('public')->path($path);
        \Illuminate\Support\Facades\File::partialMock()->shouldReceive('mimeType')->with($absolutePath)->never();
        \Illuminate\Support\Facades\File::shouldReceive('get')->with($absolutePath)->never();
        $url = route('incidents.photos.show', ['path' => $path]);
        foreach ([['preview' => 1, 'embed' => 1], ['autoprint' => 1]] as $options) {
            $this->actingAs($user)->get(route('incidents.print', $options + ['report' => 1, 'view' => 'all']))
                ->assertOk()->assertSee('src="'.$url.'"', false)
                ->assertDontSee('data:image')
                ->assertViewHas('reportRows', fn ($rows) => count($rows) === 1 && $rows[0]['proof_data_uri'] === null);
        }
    }

    public function test_large_reports_download_in_bounded_non_overlapping_parts(): void
    {
        $subdivision = Subdivision::create(['subdivision_name' => 'North', 'status' => 'Active']);
        $guard = User::factory()->create(['role' => 'admin', 'subdivision_id' => $subdivision->getKey()]);
        $this->actingAs($guard);
        foreach (['visitors', 'incidents'] as $type) {
            for ($i = 1; $i <= 101; $i++) {
                if ($type === 'visitors') {
                    Visitor::create(['subdivision_id' => $subdivision->getKey(), 'first_name' => 'Visitor'.$i, 'surname' => 'Cruz', 'status' => 'Inside', 'check_in' => now()->subDay()]);
                } else {
                    Incident::create(['subdivision_id' => $subdivision->getKey(), 'location' => 'Location'.$i, 'description' => 'Report', 'status' => 'Open', 'incident_date' => now()->subDay(), 'reported_at' => now()->subDay(), 'reported_by' => $guard->getKey()]);
                }
            }
            $page = $this->get(route('reports.index', ['type' => $type, 'all_dates' => 1]))->assertOk()
                ->assertViewHas('totalParts', 2)->assertViewHas('totalRecords', 101)->assertSee('Next Part');
            $filters = $page->viewData('filters');
            $this->assertArrayNotHasKey('date_from', $filters);
            $first = $this->get(route($type.'.export', $filters + ['format' => 'excel']))->assertOk();
            $this->assertStringContainsString('part-1.csv', $first->headers->get('Content-Disposition'));
            $firstRows = array_slice(array_map('str_getcsv', explode("\n", trim($first->streamedContent()))), 1);
            $secondFilters = array_replace($filters, ['report_part' => 2]);
            $second = $this->get(route($type.'.export', $secondFilters + ['format' => 'excel']))->assertOk();
            $secondRows = array_slice(array_map('str_getcsv', explode("\n", trim($second->streamedContent()))), 1);
            $this->assertCount(100, $firstRows);
            $this->assertCount(1, $secondRows);
            $this->assertNotContains($secondRows[0], $firstRows);
            $this->get(route($type.'.print', $secondFilters + ['embed' => 1]))->assertOk()
                ->assertViewHas('reportRows', fn ($rows) => count($rows) === 1);
            $this->getJson(route($type.'.export', ['report_part' => 0, 'format' => 'pdf']))
                ->assertUnprocessable()->assertJsonValidationErrors('report_part');
        }
    }

    public function test_reports_page_builds_filtered_downloads_and_limits_visitor_access(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get(route('reports.index', ['type' => 'visitors', 'q' => 'Ralph', 'report_status' => 'Inside']))
            ->assertOk()->assertSee('Generate Report')->assertSee('Download PDF')->assertSee('Download Excel (CSV)')
            ->assertViewHas('filters', fn ($filters) => $filters['q'] === 'Ralph' && $filters['report_status'] === 'Inside')
            ->assertViewHas('pdfUrl', fn ($url) => str_starts_with($url, '/visitors/export?') && str_contains($url, 'format=pdf'))
            ->assertViewHas('filters', fn ($filters) => $filters['date_from'] === now()->startOfDay()->format('Y-m-d\TH:i')); 
        $staff = User::factory()->create(['role' => 'staff']);
        $this->actingAs($staff)->get(route('reports.index'))->assertOk()->assertDontSee('Visitor Reports');
        $this->get(route('reports.index', ['type' => 'visitors']))->assertForbidden();
        $this->getJson(route('reports.index', ['date_from' => '2026-10-23T12:00', 'date_to' => '2026-10-22T12:00']))
            ->assertUnprocessable()->assertJsonValidationErrors('date_to');
        $this->get(route('reports.index', ['date_to' => '2026-10-23T12:00']))->assertOk();
    }

    public function test_incident_reports_include_active_cases_and_preserve_subdivision_scope(): void
    {
        $subdivision = Subdivision::create(['subdivision_name' => 'North', 'status' => 'Active']);
        $other = Subdivision::create(['subdivision_name' => 'South', 'status' => 'Active']);
        $guard = User::factory()->create(['role' => 'staff', 'subdivision_id' => $subdivision->getKey()]);
        foreach ([[$subdivision, 'Open', 'Active gate'], [$subdivision, 'Closed', 'Closed gate'], [$other, 'Open', 'Foreign gate']] as [$area, $status, $location]) {
            Incident::create(['subdivision_id' => $area->getKey(), 'description' => $location, 'location' => $location, 'category' => 'Security', 'status' => $status, 'incident_date' => now(), 'reported_at' => now(), 'reported_by' => $guard->getKey()]);
        }
        $this->actingAs($guard)->get(route('incidents.print', ['report' => 1, 'view' => 'active', 'embed' => 1]))
            ->assertOk()->assertSee('Active gate')->assertDontSee('Closed gate')->assertDontSee('Foreign gate');
        $csv = $this->get(route('incidents.export', ['report' => 1, 'view' => 'all', 'format' => 'excel', 'subdivision_id' => $other->getKey()]))->assertOk()->streamedContent();
        $this->assertStringContainsString('Active gate', $csv);
        $this->assertStringContainsString('Closed gate', $csv);
        $this->assertStringNotContainsString('Foreign gate', $csv);
    }

    public function test_visitor_reports_filter_status_and_check_in_dates_in_preview_and_download(): void
    {
        $subdivision = Subdivision::create(['subdivision_name' => 'North', 'status' => 'Active']);
        $guard = User::factory()->create(['role' => 'admin', 'subdivision_id' => $subdivision->getKey()]);
        foreach ([['Inside', 'Ralph', now()], ['Checked Out', 'Ana', now()], ['Inside', 'Old', now()->subDays(2)]] as [$status, $name, $time]) {
            Visitor::create(['subdivision_id' => $subdivision->getKey(), 'first_name' => $name, 'surname' => 'Cruz', 'status' => $status, 'check_in' => $time]);
        }
        $filters = ['report' => 1, 'report_status' => 'Inside', 'date_from' => now()->startOfDay()->format('Y-m-d\TH:i')];
        $this->actingAs($guard)->get(route('visitors.print', $filters + ['embed' => 1]))->assertOk()
            ->assertSee('Ralph Cruz')->assertDontSee('Ana Cruz')->assertDontSee('Old Cruz');
        $csv = $this->get(route('visitors.export', $filters + ['format' => 'excel']))->assertOk()->streamedContent();
        $this->assertStringContainsString('Ralph Cruz', $csv);
        $this->assertStringNotContainsString('Ana Cruz', $csv);
        $this->assertStringNotContainsString('Old Cruz', $csv);
    }
}
