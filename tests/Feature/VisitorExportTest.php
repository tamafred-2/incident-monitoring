<?php

namespace Tests\Feature;

use App\Models\Subdivision;
use App\Models\User;
use App\Models\Visitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VisitorExportTest extends TestCase
{
    use RefreshDatabase;

    private function signInWithVisitorHistory(): void
    {
        $subdivision = Subdivision::create([
            'subdivision_name' => 'Export Test',
            'status' => 'Active',
        ]);
        $this->actingAs(User::factory()->create([
            'role' => 'admin',
            'subdivision_id' => $subdivision->getKey(),
        ]));
        Visitor::create([
            'subdivision_id' => $subdivision->getKey(),
            'surname' => 'Cruz',
            'first_name' => 'Ana',
            'check_in' => now()->subHour(),
            'check_out' => now(),
            'status' => 'Checked Out',
        ]);
    }

    public function test_excel_download_contains_visitor_status_as_text(): void
    {
        $this->signInWithVisitorHistory();

        $response = $this->get(route('visitors.export', ['format' => 'excel']))
            ->assertOk()
            ->assertDownload();

        $rows = array_map('str_getcsv', explode("\n", trim($response->streamedContent())));
        $this->assertSame('Ana Cruz', $rows[1][0]);
        $this->assertSame('Checked Out', $rows[1][12]);
    }

    public function test_pdf_download_renders_visitor_history(): void
    {
        $this->signInWithVisitorHistory();

        $response = $this->get(route('visitors.export', ['format' => 'pdf']))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertDownload();

        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    public function test_preview_download_links_use_same_origin_and_override_stale_format(): void
    {
        $this->signInWithVisitorHistory();

        $this->get(route('visitors.print', ['preview' => 1, 'format' => 'pdf']))
            ->assertOk()
            ->assertSee('href="/visitors/export?preview=1&amp;format=excel"', false)
            ->assertSee('href="/visitors/export?preview=1&amp;format=pdf"', false);
    }

    public function test_pdf_handles_multiple_pages_and_a_purpose_taller_than_one_page(): void
    {
        $this->signInWithVisitorHistory();
        $visitor = Visitor::firstOrFail();
        $visitor->update(['purpose' => str_repeat('A long visitor purpose that must flow across pages. ', 350)]);
        for ($i = 0; $i < 29; $i++) {
            $copy = $visitor->replicate();
            $copy->purpose = 'Visiting a resident';
            $copy->save();
        }

        $response = $this->get(route('visitors.export', ['format' => 'pdf']))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $this->assertGreaterThan(1, preg_match_all('~/Type\s*/Page\b~', $response->getContent()));
    }

    public function test_pdf_download_succeeds_when_history_exceeds_the_export_limit(): void
    {
        $this->signInWithVisitorHistory();
        $visitor = Visitor::firstOrFail();
        for ($i = 0; $i < 200; $i++) {
            $visitor->replicate()->save();
        }

        $response = $this->get(route('visitors.export', ['format' => 'pdf']))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertDownload();

        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    public function test_reports_replace_history_export_controls_and_use_same_origin_urls(): void
    {
        $this->signInWithVisitorHistory();
        $this->withoutVite()->get(route('visitors.index', ['tab' => 'history']))
            ->assertOk()->assertSee(route('reports.index', ['type' => 'visitors']))
            ->assertDontSee('openExcelConfirm');
        $this->get(route('reports.index', ['type' => 'visitors']))->assertOk()
            ->assertViewHas('excelUrl', fn ($url) => str_starts_with($url, '/visitors/export?') && str_contains($url, 'format=excel'))
            ->assertViewHas('pdfUrl', fn ($url) => str_starts_with($url, '/visitors/export?') && str_contains($url, 'format=pdf'));
    }

    public function test_pdf_selects_latest_100_matching_visits_before_rendering(): void
    {
        $this->signInWithVisitorHistory();
        $visitor = Visitor::firstOrFail();
        for ($i = 0; $i < 105; $i++) {
            $copy = $visitor->replicate();
            $copy->first_name = 'Visit'.$i;
            $copy->check_in = now()->subMinutes(105 - $i);
            $copy->purpose = 'Selected';
            $copy->save();
        }
        // A higher ID must not outrank a more recent check-in.
        $older = $visitor->replicate();
        $older->check_in = now()->subYear();
        $older->purpose = 'Selected';
        $older->save();

        $csv = $this->get(route('visitors.export', ['format' => 'excel', 'q' => 'Selected']))
            ->assertOk()->assertDownload();
        $rows = array_map('str_getcsv', explode("\n", trim($csv->streamedContent())));
        $this->assertCount(101, $rows);
        $this->assertSame('Visit104 Cruz', $rows[1][0]);
        $this->assertSame('Visit5 Cruz', $rows[100][0]);

        $pdf = \Mockery::mock(\Barryvdh\DomPDF\PDF::class);
        $pdf->shouldReceive('setPaper')->with('a4', 'portrait')->once()->andReturnSelf();
        $pdf->shouldReceive('download')->once()->andReturn(response('%PDF-test'));
        \Barryvdh\DomPDF\Facade\Pdf::shouldReceive('loadView')->once()
            ->withArgs(function ($view, $data) {
                $this->assertSame('visitors.report-pdf', $view);
                $this->assertSame(106, $data['totalMatching']);
                $this->assertCount(100, $data['reportRows']);
                $this->assertSame('Visit104 Cruz', $data['reportRows'][0]['name']);
                $this->assertSame('Visit5 Cruz', $data['reportRows'][99]['name']);

                return true;
            })->andReturn($pdf);

        $this->get(route('visitors.export', ['format' => 'pdf', 'q' => 'Selected']))->assertOk();
    }
}
