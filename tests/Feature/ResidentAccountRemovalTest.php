<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Incident;
use App\Models\Resident;
use App\Models\Subdivision;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ResidentAccountRemovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_resident_role_cannot_be_created_or_assigned_and_portal_routes_are_removed(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $staff = User::factory()->create(['role' => 'staff']);
        $data = ['first_name' => 'Ana', 'surname' => 'Cruz', 'email' => 'resident@example.test', 'role' => 'resident'];

        $this->actingAs($admin)->post(route('users.store'), $data)->assertSessionHasErrors('role');
        $this->put(route('users.update', $staff), $data)->assertSessionHasErrors('role');
        $this->assertDatabaseMissing('users', ['email' => $data['email']]);
        $this->assertSame(['admin', 'security', 'staff'], array_column(UserRole::cases(), 'value'));
        $this->assertFalse(Route::has('resident.visitors.index'));
        $this->get('/resident/visitors')->assertNotFound();
    }

    public function test_migration_retires_existing_logins_and_preserves_residents_and_reporters(): void
    {
        $migration = require database_path('migrations/2026_10_11_000000_remove_resident_accounts.php');
        $migration->down();
        $home = Subdivision::create(['subdivision_name' => 'Home', 'status' => 'Active']);
        $resident = Resident::create(['subdivision_id' => $home->getKey(), 'full_name' => 'Ana Cruz', 'status' => 'Active']);
        $admin = User::factory()->create(['role' => 'admin']);
        $id = DB::table('users')->insertGetId([
            'full_name' => 'Ana Cruz', 'email' => 'former-resident@example.test',
            'password' => Hash::make('password'), 'role' => 'resident',
            'resident_id' => $resident->getKey(), 'subdivision_id' => $home->getKey(),
            'is_active' => true,
        ], 'user_id');
        $incident = Incident::create([
            'subdivision_id' => $home->getKey(), 'description' => 'Existing report',
            'location' => 'Gate', 'incident_date' => now(), 'reported_at' => now(),
            'reported_by' => $id, 'status' => 'Open',
        ]);
        DB::table('sessions')->insert(['id' => 'resident-session', 'user_id' => $id, 'payload' => '', 'last_activity' => time()]);
        $migration->up();

        $this->assertDatabaseHas('residents', ['resident_id' => $resident->getKey()]);
        $this->assertDatabaseHas('users', ['user_id' => $id, 'role' => null, 'is_active' => false]);
        $this->assertSoftDeleted('users', ['user_id' => $id]);
        $this->assertFalse(Schema::hasColumn('users', 'resident_id'));
        $this->assertDatabaseMissing('sessions', ['id' => 'resident-session']);
        $this->assertSame('Ana Cruz', $incident->fresh()->reporter->full_name);
        $this->post(route('login'), ['email' => 'former-resident@example.test', 'password' => 'password'])->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->actingAs($admin)->get(route('users.index', ['view' => 'deleted']))->assertOk()->assertViewHas('users', fn ($users) => $users->isEmpty());
        $this->post(route('users.restore', ['userId' => $id]))->assertNotFound();
        $this->assertSame(UserRole::Admin, $admin->fresh()->role);
    }
}
