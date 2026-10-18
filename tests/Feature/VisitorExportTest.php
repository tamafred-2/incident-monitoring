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
            'role' => 'security',
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

    public function test_history_buttons_use_same_origin_download_urls(): void
    {
        $this->signInWithVisitorHistory();

        $this->withoutVite()->get(route('visitors.index', ['tab' => 'history']))
            ->assertOk()
            ->assertSee("openExcelConfirm('\\/visitors\\/export?tab=history\\u0026format=excel')", false)
            ->assertSee("openPdfConfirm('\\/visitors\\/export?tab=history\\u0026format=pdf')", false);
    }
}
