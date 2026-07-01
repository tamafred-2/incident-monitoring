<?php

namespace Tests\Feature;

use App\Models\Subdivision;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubdivisionManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_subdivision_detail_page_can_be_viewed(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'subdivision_id' => null,
        ]);

        $subdivision = Subdivision::create([
            'subdivision_name' => 'Sample Subdivision',
            'address' => 'Sample Address',
            'contact_person' => 'Sample Contact',
            'contact_number' => '1234567',
            'email' => 'sample@example.com',
            'status' => 'Active',
        ]);

        $this->actingAs($admin)
            ->get(route('subdivisions.show', $subdivision))
            ->assertOk()
            ->assertSee('House Management')
            ->assertSee('Sample Subdivision');
    }

    public function test_subdivision_cannot_be_deleted(): void
    {
        // The subdivision is the singleton system profile; the destroy/restore/
        // force-delete routes were intentionally removed.
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('subdivisions.destroy'));
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('subdivisions.restore'));
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('subdivisions.force-delete'));
    }
}
