<?php

namespace Tests\Feature;

use App\Models\House;
use App\Models\Resident;
use App\Models\Subdivision;
use App\Models\User;
use App\Models\Visitor;
use App\Models\VisitorProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VisitorProfilesTest extends TestCase
{
    use RefreshDatabase;

    private function setupGate(): array
    {
        Storage::fake('public');
        $subdivision = Subdivision::create(['subdivision_name' => 'Home', 'status' => 'Active']);
        $guard = User::factory()->create(['role' => 'security', 'subdivision_id' => $subdivision->getKey()]);
        $this->actingAs($guard);

        return [$subdivision, $guard];
    }

    private function register(): VisitorProfile
    {
        $this->post(route('visitor-profiles.store'), [
            'surname' => 'Cruz', 'first_name' => 'Ana', 'phone' => '09171234567',
            'id_photo' => UploadedFile::fake()->create('id.jpg', 10, 'image/jpeg'),
        ])->assertSessionHasNoErrors();

        return VisitorProfile::latest('profile_id')->firstOrFail();
    }

    public function test_admin_can_view_but_cannot_create_profiles_visits_or_schedules(): void
    {
        [$subdivision] = $this->setupGate();
        $profile = $this->register();
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);
        $this->get(route('visitor-profiles.index'))->assertOk()->assertDontSee('Add Visitor');
        $this->get(route('visitor-profiles.show', $profile))->assertOk()->assertDontSee('Schedule Expected Visit')->assertDontSee('Add Visit');
        $this->get(route('visitors.index'))->assertOk()->assertDontSee('Add Visit');
        $this->post(route('visitor-profiles.store'), [])->assertForbidden();
        $this->post(route('visitors.store'), [])->assertForbidden();
        $this->get(route('expected-visits.create', $profile))->assertForbidden();
        $this->post(route('expected-visits.store', $profile), [])->assertForbidden();
        $this->assertDatabaseCount('visitors', 0);
        $this->assertDatabaseCount('expected_visits', 0);
        $this->assertDatabaseCount('visitor_profiles', 1);
    }

    public function test_registering_a_visitor_creates_a_profile_without_checking_in(): void
    {
        $this->setupGate();
        $profile = $this->register();
        $this->assertDatabaseCount('visitor_profiles', 1);
        $this->assertDatabaseCount('visitors', 0);
        $this->assertDatabaseCount('users', 1);
        Storage::disk('public')->assertExists($profile->id_photo_path);
        $this->get(route('visitor-profiles.index'))->assertOk()->assertSee($profile->full_name)->assertDontSee($profile->visitor_number);
        $this->get(route('visitor-profiles.show', $profile))->assertOk()->assertSee('No visits yet.')->assertSee('Add Visit');
        $this->get(route('visitor-profiles.photo', $profile))->assertOk();
        $this->post(route('visitor-profiles.store'), [
            'surname' => 'Cruz', 'first_name' => 'Ana', 'phone' => '09171234567',
            'id_photo' => UploadedFile::fake()->create('id.jpg', 10, 'image/jpeg'),
        ])->assertSessionHasErrors('surname');
        $this->assertDatabaseCount('visitor_profiles', 1);
    }

    public function test_replacing_a_photo_changes_its_url_and_serves_the_latest_file_without_caching(): void
    {
        $this->setupGate();
        $profile = $this->register();
        $oldPath = $profile->id_photo_path;
        $oldUrl = route('visitor-profiles.photo', ['profile' => $profile, 'v' => hash('sha256', $oldPath)]);
        $this->get(route('visitor-profiles.show', $profile))->assertSee($oldUrl);

        $this->post(route('visitor-profiles.update', $profile), [
            '_method' => 'PUT',
            'first_name' => $profile->first_name, 'surname' => $profile->surname, 'phone' => $profile->phone,
            'id_photo' => UploadedFile::fake()->create('replacement.jpg', 12, 'image/jpeg'),
        ])->assertSessionHasNoErrors()->assertRedirect(route('visitor-profiles.show', $profile));

        $profile->refresh();
        $this->assertNotSame($oldPath, $profile->id_photo_path);
        $newUrl = route('visitor-profiles.photo', ['profile' => $profile, 'v' => hash('sha256', $profile->id_photo_path)]);
        $this->get(route('visitor-profiles.show', $profile))->assertSee($newUrl)->assertDontSee($oldUrl);
        $response = $this->get($newUrl)->assertOk();
        $this->assertSame(Storage::disk('public')->path($profile->id_photo_path), $response->baseResponse->getFile()->getPathname());
        $this->assertTrue($response->headers->hasCacheControlDirective('private'));
        $this->assertTrue($response->headers->hasCacheControlDirective('no-store'));
        $this->assertFalse($response->headers->hasCacheControlDirective('public'));

        $staff = User::factory()->create(['role' => 'staff', 'subdivision_id' => $profile->subdivision_id]);
        $this->actingAs($staff)->get($newUrl)->assertRedirect(route('dashboard'));
    }

    public function test_unsupported_files_are_rejected_for_registration_profile_edits_and_check_in(): void
    {
        $this->setupGate();
        $profile = $this->register();
        $originalPath = $profile->id_photo_path;

        foreach (['video.mp4' => 'video/mp4', 'renamed-video.jpg' => 'video/mp4', 'animation.gif' => 'image/gif', 'photo.webp' => 'image/webp'] as $filename => $mime) {
            $this->post(route('visitor-profiles.store'), [
                'first_name' => 'Ben', 'surname' => 'Reyes', 'phone' => '09171234567',
                'id_photo' => UploadedFile::fake()->create($filename, 10, $mime),
            ])->assertSessionHasErrors('id_photo');

            $this->put(route('visitor-profiles.update', $profile), [
                'first_name' => $profile->first_name, 'surname' => $profile->surname, 'phone' => $profile->phone,
                'id_photo' => UploadedFile::fake()->create($filename, 10, $mime),
            ])->assertSessionHasErrors('id_photo');

            $this->post(route('visitors.store'), [
                'visitor_profile_id' => $profile->getKey(), 'visit_type' => 'walk_in',
                'allowed_minutes' => 15, 'house_address_or_unit' => 'Gate',
                'id_photo' => UploadedFile::fake()->create($filename, 10, $mime),
            ])->assertSessionHasErrors('id_photo');
        }

        $this->assertSame($originalPath, $profile->fresh()->id_photo_path);
        $this->assertDatabaseCount('visitor_profiles', 1);
        $this->assertDatabaseCount('visitors', 0);
        $this->assertCount(1, Storage::disk('public')->allFiles());
    }

    public function test_id_photos_up_to_ten_megabytes_are_accepted_and_larger_ones_are_rejected(): void
    {
        $this->setupGate();
        $profile = $this->register();
        $originalPath = $profile->id_photo_path;
        $payload = ['first_name' => $profile->first_name, 'surname' => $profile->surname, 'phone' => $profile->phone];

        $this->put(route('visitor-profiles.update', $profile), $payload + [
            'id_photo' => UploadedFile::fake()->create('large.jpg', 10 * 1024 + 1, 'image/jpeg'),
        ])->assertSessionHasErrors(['id_photo' => 'The photo must be 10 MB or smaller. Please choose a smaller photo.']);
        $this->assertSame($originalPath, $profile->fresh()->id_photo_path);

        $this->put(route('visitor-profiles.update', $profile), $payload + [
            'id_photo' => UploadedFile::fake()->create('limit.jpg', 10 * 1024, 'image/jpeg'),
        ])->assertSessionHasNoErrors();
        $this->assertNotSame($originalPath, $profile->fresh()->id_photo_path);
    }

    public function test_requests_over_the_server_upload_limit_get_a_clear_response_instead_of_a_crash(): void
    {
        $this->setupGate();
        $oversized = ['CONTENT_LENGTH' => PHP_INT_MAX, 'CONTENT_TYPE' => 'multipart/form-data; boundary=x'];

        $this->call('POST', route('visitors.store'), [], [], [], $oversized + ['HTTP_ACCEPT' => 'application/json'])
            ->assertStatus(413)->assertJsonPath('errors.upload.0', 'The selected photos are too large to upload at once. Choose smaller or fewer photos, then try again.');
        $this->call('POST', route('visitor-profiles.store'), [], [], [], $oversized)
            ->assertStatus(413)->assertSee('too large to upload at once')->assertSee('Go back');
        $this->assertDatabaseCount('visitor_profiles', 0);
    }

    public function test_exact_full_name_is_rejected_even_with_a_different_phone(): void
    {
        $this->setupGate();
        $this->register();
        $this->post(route('visitor-profiles.store'), [
            'surname' => ' CRUZ ', 'first_name' => 'ana', 'phone' => '09179999999',
            'id_photo' => UploadedFile::fake()->create('id.jpg', 10, 'image/jpeg'),
        ])->assertSessionHasErrors('surname');
        $this->assertDatabaseCount('visitor_profiles', 1);

        foreach ([['middle_initials' => 'Maria'], ['middle_initials' => 'Marie'], ['extension' => 'Jr.']] as $difference) {
            $this->post(route('visitor-profiles.store'), $difference + [
                'surname' => 'Cruz', 'first_name' => 'Ana', 'phone' => '09179999999',
                'id_photo' => UploadedFile::fake()->create('id.jpg', 10, 'image/jpeg'),
            ])->assertSessionHasNoErrors();
        }
        $this->assertDatabaseCount('visitor_profiles', 4);
    }

    public function test_editing_cannot_duplicate_another_visitors_name_but_can_keep_its_own(): void
    {
        [$subdivision] = $this->setupGate();
        $profile = $this->register();
        $other = VisitorProfile::create(['subdivision_id' => $subdivision->getKey(), 'first_name' => 'Alfred', 'surname' => 'Tamayo', 'phone' => '09179999999']);
        $this->put(route('visitor-profiles.update', $other), [
            'first_name' => 'Ana', 'surname' => 'Cruz', 'phone' => $other->phone,
        ])->assertSessionHasErrors('surname');
        $this->assertSame('Alfred Tamayo', $other->fresh()->full_name);
        $this->put(route('visitor-profiles.update', $profile), [
            'first_name' => 'Ana', 'surname' => 'Cruz', 'phone' => '09171111111',
        ])->assertSessionHasNoErrors();
        $this->assertSame('09171111111', $profile->fresh()->phone);
    }

    public function test_one_profile_can_have_multiple_visits_with_separate_vehicles_and_times(): void
    {
        $this->setupGate();
        $profile = $this->register();
        $payload = ['visitor_profile_id' => $profile->getKey(), 'visit_type' => 'walk_in', 'allowed_minutes' => 15, 'house_address_or_unit' => 'Clubhouse', 'purpose' => 'Delivery'];
        $this->post(route('visitors.store'), $payload + [
            'on_vehicle' => 1, 'plate_number' => 'ABC 123', 'passenger_count' => 1, 'vehicle_type' => 'Motorcycle', 'vehicle_color' => 'Black',
        ])->assertSessionHasNoErrors();
        $first = Visitor::sole();
        $this->assertSame($profile->id_photo_path, $first->id_photo_path);
        $photo = $this->get(route('visitors.photo', $first))->assertOk();
        $this->assertTrue($photo->headers->hasCacheControlDirective('private'));
        $this->assertTrue($photo->headers->hasCacheControlDirective('no-store'));
        $this->post(route('visitors.store'), $payload)->assertSessionHasErrors('visitor_profile_id');
        $this->assertDatabaseCount('visitors', 1);
        $this->travel(90)->minutes();
        $this->post(route('visitors.checkout', $first), ['return_to' => 'profile'])->assertRedirect(route('visitor-profiles.show', $profile));
        $this->post(route('visitors.store'), $payload + ['on_vehicle' => 0])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('visitor_profiles', 1);
        $this->assertDatabaseCount('visitors', 2);
        $second = Visitor::latest('visitor_id')->first();
        $this->assertSame($profile->getKey(), $second->visitor_profile_id);
        $this->assertNull($second->plate_number);
        $this->assertNull($second->check_out);
        $this->assertSame('01h 30m', $first->fresh()->visit_duration_label);
        $this->get(route('visitor-profiles.show', $profile))->assertOk()->assertSee('ABC 123')->assertSee('01h 30m')->assertSee('Check Out');
    }

    public function test_every_resident_visit_requires_a_fresh_call_outcome_even_when_visiting_the_owner(): void
    {
        [$subdivision, $guard] = $this->setupGate();
        $profile = $this->register();
        $house = House::create(['subdivision_id' => $subdivision->getKey(), 'block' => '1', 'lot' => '1', 'street' => 'Main']);
        $owner = Resident::create(['subdivision_id' => $subdivision->getKey(), 'house_id' => $house->getKey(), 'full_name' => 'Jose Reyes', 'phone' => '09179999999', 'relation_to_owner' => 'Owner', 'status' => 'Active']);
        $payload = ['visitor_profile_id' => $profile->getKey(), 'visit_type' => 'resident', 'resident_id' => $owner->getKey(), 'resident_house_id' => $house->getKey(), 'house_address_or_unit' => $house->display_address];
        $this->post(route('visitors.store'), $payload)->assertSessionHasErrors('owner_permission');
        $this->assertDatabaseCount('visitors', 0);
        $this->post(route('visitors.store'), $payload + ['owner_permission' => 1, 'approving_owner_id' => $owner->getKey()])->assertSessionHasNoErrors();
        $visit = Visitor::sole();
        $this->assertSame($owner->getKey(), $visit->owner_approval['owner_id']);
        $this->assertSame($guard->full_name, $visit->owner_approval['recorded_by_name']);
        $this->assertSame('phone', $visit->owner_approval['method']);
        $this->post(route('visitors.checkout', $visit))->assertSessionHasNoErrors();
        $this->post(route('visitors.store'), $payload)->assertSessionHasErrors('owner_permission');
        $this->assertDatabaseCount('visitors', 1);
        $this->get(route('visitor-profiles.show', $profile))->assertOk()->assertSee('Jose Reyes')->assertSee('Recorded by '.$guard->full_name);

        $this->post(route('visitors.store'), $payload + ['owner_permission' => '0', 'approving_owner_id' => $owner->getKey()])->assertSessionHasNoErrors();
        $unavailableVisit = Visitor::latest('visitor_id')->firstOrFail();
        $this->assertSame('Inside', $unavailableVisit->status->value);
        $this->assertSame('unavailable', $unavailableVisit->owner_approval['call_status']);
        $this->assertNull($unavailableVisit->owner_approval['approved_at']);
        $this->assertNull($unavailableVisit->owner_approval['method']);
        $this->assertNotEmpty($unavailableVisit->owner_approval['recorded_at']);
        $this->assertSame($guard->full_name, $unavailableVisit->owner_approval['recorded_by_name']);
        $this->assertDatabaseHas('visitor_requests', ['visitor_id' => $unavailableVisit->getKey(), 'status' => 'Pending', 'responded_at' => null]);
        $this->get(route('visitors.show', $unavailableVisit))->assertOk()->assertSee('Entry recorded without owner approval.');
        $this->get(route('visitor-profiles.show', $profile))->assertOk()->assertSee('Not called / owner unavailable');
    }

    public function test_profile_updates_do_not_rewrite_old_visits(): void
    {
        $this->setupGate();
        $profile = $this->register();
        $payload = ['visitor_profile_id' => $profile->getKey(), 'visit_type' => 'walk_in', 'allowed_minutes' => 15, 'house_address_or_unit' => 'Gate'];
        $this->post(route('visitors.store'), $payload)->assertSessionHasNoErrors();
        $first = Visitor::sole();
        $this->post(route('visitors.checkout', $first));
        $this->put(route('visitor-profiles.update', $profile), ['surname' => 'Reyes', 'first_name' => 'Ana', 'phone' => '09180000000'])->assertSessionHasNoErrors();
        $this->assertSame('Ana Cruz', $first->fresh()->full_name);
        $this->assertSame('09171234567', $first->fresh()->phone);
        $this->post(route('visitors.store'), $payload)->assertSessionHasNoErrors();
        $this->assertSame('Ana Reyes', Visitor::latest('visitor_id')->first()->full_name);
    }

    public function test_older_check_in_forms_reuse_a_matching_profile_and_cannot_check_in_twice(): void
    {
        $this->setupGate();
        $profile = $this->register();
        $payload = ['first_name' => 'Ana', 'surname' => 'Cruz', 'phone' => '09171234567', 'visit_type' => 'walk_in', 'allowed_minutes' => 15, 'house_address_or_unit' => 'Gate'];
        $this->post(route('visitors.store'), $payload + ['id_photo' => UploadedFile::fake()->create('id.jpg', 10, 'image/jpeg')])->assertSessionHasNoErrors();
        $this->assertSame($profile->getKey(), Visitor::sole()->visitor_profile_id);
        $this->post(route('visitors.store'), $payload + ['id_photo' => UploadedFile::fake()->create('id.jpg', 10, 'image/jpeg')])->assertSessionHasErrors('visitor_profile_id');
        $this->assertDatabaseCount('visitor_profiles', 1);
        $this->assertDatabaseCount('visitors', 1);
    }

    public function test_profile_search_and_all_profile_actions_respect_subdivision_access(): void
    {
        $this->setupGate();
        $own = $this->register();
        $other = Subdivision::create(['subdivision_name' => 'Other', 'status' => 'Active']);
        $foreign = VisitorProfile::create(['subdivision_id' => $other->getKey(), 'first_name' => 'Ana', 'surname' => 'Cruz', 'phone' => '09999999999']);
        $this->getJson(route('visitor-profiles.index', ['q' => 'Ana', 'subdivision_id' => $other->getKey()]))->assertOk()->assertJsonCount(1, 'profiles')->assertJsonPath('profiles.0.id', $own->getKey());
        $this->getJson(route('visitor-profiles.index', ['q' => $own->visitor_number]))->assertJsonPath('profiles.0.id', $own->getKey());
        $this->get(route('visitor-profiles.show', $foreign))->assertForbidden();
        $this->get(route('visitor-profiles.photo', $foreign))->assertForbidden();
        $this->put(route('visitor-profiles.update', $foreign), [])->assertForbidden();
        $this->get(route('visitors.index', ['profile' => $foreign->getKey()]))->assertForbidden();
        $this->post(route('visitors.store'), ['visitor_profile_id' => $foreign->getKey()])->assertForbidden();
        $this->assertDatabaseCount('visitors', 0);
    }

    public function test_backfill_preserves_history_and_groups_only_matching_names_and_nonempty_phones(): void
    {
        [$subdivision] = $this->setupGate();
        $migration = require database_path('migrations/2026_10_11_010000_create_visitor_profiles.php');
        $migration->down();
        foreach (['09170000000', '09170000000', '09180000000', null, null] as $index => $phone) {
            DB::table('visitors')->insert([
                'subdivision_id' => $subdivision->getKey(), 'surname' => 'Cruz', 'first_name' => 'Ana', 'full_name' => 'Ana Cruz',
                'phone' => $phone, 'check_in' => now()->subDays($index + 1), 'check_out' => now()->subDays($index + 1)->addHour(),
                'status' => 'Checked Out', 'deleted_at' => $index === 1 ? now() : null,
            ]);
        }
        $migration->up();
        $this->assertDatabaseCount('visitors', 5);
        $this->assertDatabaseCount('visitor_profiles', 4);
        $visits = Visitor::withTrashed()->orderBy('visitor_id')->get();
        $this->assertSame($visits[0]->visitor_profile_id, $visits[1]->visitor_profile_id);
        $this->assertNotSame($visits[0]->visitor_profile_id, $visits[2]->visitor_profile_id);
        $this->assertNotSame($visits[3]->visitor_profile_id, $visits[4]->visitor_profile_id);
        $this->assertTrue($visits[1]->trashed());
        $this->assertSame('01h 00m', $visits[0]->visit_duration_label);
    }
}
