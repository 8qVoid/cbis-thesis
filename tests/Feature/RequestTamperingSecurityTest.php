<?php

namespace Tests\Feature;

use App\Models\BloodReservation;
use App\Models\DonationSchedule;
use App\Models\Donor;
use App\Models\Facility;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RequestTamperingSecurityTest extends TestCase
{
    use RefreshDatabase;

    private Facility $main;

    protected function setUp(): void
    {
        parent::setUp();
        // Keep the permission definitions and remove the seeder's demo account.
        $this->seed(RolePermissionSeeder::class);
        User::all()->each(fn (User $user) => $user->forceDelete());
        $this->main = Facility::create([
            'code' => 'SECURITY-MAIN', 'name' => 'Security Test Main',
            'type' => 'blood_bank', 'is_active' => true, 'is_main_chapter' => true,
        ]);
        Notification::fake();
    }

    public function test_registration_rejects_injected_staff_and_account_control_fields(): void
    {
        $this->postJson(route('donor.register.store'), [
            ...$this->registration(), 'role' => 'Quality Assurance Officer',
            'is_active' => true, 'permissions' => ['manage users'], 'user_id' => 1,
        ])->assertUnprocessable()->assertJsonValidationErrors('request');

        $this->assertDatabaseCount('users', 0);
        $this->postJson(route('donor.register.store'), [
            ...$this->registration(), 'services' => ['patient', 'Quality Assurance Officer'],
        ])->assertUnprocessable()->assertJsonValidationErrors('services.1');
        $this->assertDatabaseCount('users', 0);
    }

    public function test_newly_registered_public_account_cannot_call_staff_actions_directly(): void
    {
        $this->post(route('donor.register.store'), $this->registration())
            ->assertRedirect(route('account.dashboard'));
        $user = User::where('email', 'tamper.public@example.test')->sole();
        $this->assertSame(['Patient'], $user->getRoleNames()->all());
        $this->assertNull($user->facility_id);

        foreach (['dashboard', 'staff-users.index', 'blood-inventory.index', 'reports.index', 'donation-schedules.index'] as $name) {
            $this->getJson(route($name))->assertForbidden();
        }
        foreach (['staff-users.store', 'blood-inventory.store', 'blood-releases.store', 'donation-schedules.store', 'report-requests.store'] as $name) {
            $this->postJson(route($name), ['role' => 'Quality Assurance Officer', 'facility_id' => $this->main->id])
                ->assertForbidden();
        }
        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('blood_inventory', 0);
    }

    public function test_public_services_and_profile_cannot_change_role_facility_or_owner(): void
    {
        $patient = $this->user('Patient');
        $this->actingAs($patient)->putJson(route('account.profile.update'), [
            'services' => ['patient', 'Blood Bank Staff'],
        ])->assertUnprocessable()->assertJsonValidationErrors('services.1');
        $this->assertSame(['Patient'], $patient->fresh()->getRoleNames()->all());

        $this->put(route('account.details.update'), [
            'first_name' => 'Public', 'last_name' => 'Tester',
            'address' => 'Barangay I (Pob.), Manapla, Negros Occidental',
            'email' => $patient->email, 'phone' => '+639177770001',
            'role' => 'Blood Bank Staff', 'facility_id' => $this->main->id,
            'id' => 9999, 'is_active' => false, 'password' => 'forged-password',
        ])->assertSessionHasNoErrors()->assertRedirect(route('account.details.edit'));
        $patient->refresh();
        $this->assertNull($patient->facility_id);
        $this->assertTrue($patient->is_active);
        $this->assertSame(['Patient'], $patient->getRoleNames()->all());
        $this->assertTrue(password_verify('password', $patient->password));
    }

    public function test_reservation_ignores_forged_approval_reviewer_and_patient_fields(): void
    {
        Storage::fake('local');
        $patient = $this->user('Patient');
        $other = $this->user('Patient');
        $this->actingAs($patient)->post(route('reservations.store'), [
            ...$this->reservationPayload(), 'patient_user_id' => $other->id,
            'status' => 'approved', 'reviewed_by' => $other->id,
            'reviewed_at' => now()->toDateTimeString(), 'reference' => 'FORGED',
        ])->assertSessionHasNoErrors()->assertRedirect(route('reservations.index'));

        $reservation = BloodReservation::sole();
        $this->assertSame($patient->id, $reservation->patient_user_id);
        $this->assertSame('submitted', $reservation->status);
        $this->assertNull($reservation->reviewed_by);
        $this->assertNull($reservation->reviewed_at);
        $this->assertNotSame('FORGED', $reservation->reference);
    }

    public function test_removed_browser_quantity_limits_and_wrong_facility_are_rejected(): void
    {
        Storage::fake('local');
        $patient = $this->user('Patient');
        $branch = Facility::create(['code' => 'SECURITY-BRANCH', 'name' => 'Branch', 'type' => 'blood_bank', 'is_active' => true]);
        $this->actingAs($patient)->postJson(route('reservations.store'), [
            ...$this->reservationPayload(), 'units_requested' => 999, 'facility_id' => $branch->id,
        ])->assertUnprocessable()->assertJsonValidationErrors(['units_requested', 'facility_id']);
        $this->postJson(route('reservations.store'), [
            ...$this->reservationPayload(), 'units_requested' => -1,
        ])->assertUnprocessable()->assertJsonValidationErrors('units_requested');
        $this->assertDatabaseCount('blood_reservations', 0);
    }

    public function test_patient_cannot_open_another_patient_reservation_or_review_own_request(): void
    {
        $patient = $this->user('Patient');
        $other = $this->user('Patient');
        $own = $this->reservation($patient);
        $foreign = $this->reservation($other);
        $this->actingAs($patient)->getJson(route('reservations.show', $foreign))->assertForbidden();
        $this->patchJson(route('reservations.review', $own), ['status' => 'approved'])->assertForbidden();
        $this->assertSame('submitted', $own->fresh()->status);
    }

    public function test_private_document_ids_are_scoped_to_both_owner_and_reservation(): void
    {
        Storage::fake('local');
        $patient = $this->user('Patient');
        $other = $this->user('Patient');
        $own = $this->reservation($patient);
        $foreign = $this->reservation($other);
        Storage::disk('local')->put('reservations/private.pdf', '%PDF-1.4 audit fixture');
        $document = $foreign->documents()->create([
            'type' => 'blood_request', 'path' => 'reservations/private.pdf',
            'original_name' => 'private.pdf', 'mime_type' => 'application/pdf', 'size' => 26,
        ]);
        $this->actingAs($patient)->get(route('reservations.documents.show', [$foreign, $document->id]))->assertForbidden();
        $this->get(route('reservations.documents.show', [$own, $document->id]))->assertNotFound();
        $qao = $this->user('Quality Assurance Officer');
        $this->actingAs($qao)->get(route('reservations.documents.show', [$foreign, $document->id]))->assertForbidden();
    }

    public function test_fake_file_accept_attribute_cannot_enable_script_uploads(): void
    {
        Storage::fake('local');
        $patient = $this->user('Patient');
        $this->actingAs($patient)->postJson(route('reservations.store'), [
            ...$this->reservationPayload(),
            'blood_request' => UploadedFile::fake()->create('payload.php', 1, 'application/x-httpd-php'),
        ])->assertUnprocessable()->assertJsonValidationErrors('blood_request');
        $this->assertDatabaseCount('blood_reservations', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_forged_event_registration_only_affects_authenticated_donor(): void
    {
        $user = $this->user('Donor');
        $other = $this->user('Donor');
        $donor = $this->donor($user);
        $foreignDonor = $this->donor($other);
        $event = $this->event('approved');
        $this->actingAs($user)->post(route('donor.events.register', $event), [
            'donor_id' => $foreignDonor->id, 'user_id' => $other->id,
            'status' => 'attended', 'facility_id' => 9999,
        ])->assertRedirect();
        $this->assertDatabaseHas('event_registrations', [
            'donor_id' => $donor->id, 'donation_schedule_id' => $event->id,
            'status' => 'registered', 'facility_id' => $this->main->id,
        ]);
        $this->assertDatabaseMissing('event_registrations', ['donor_id' => $foreignDonor->id]);
    }

    public function test_direct_request_cannot_join_an_unapproved_event(): void
    {
        $user = $this->user('Donor');
        $this->donor($user);
        $event = $this->event('pending');
        $this->actingAs($user)->postJson(route('donor.events.register', $event), ['approval_status' => 'approved'])
            ->assertUnprocessable();
        $this->assertDatabaseCount('event_registrations', 0);
        $this->assertSame('pending', $event->fresh()->approval_status);
    }

    public function test_review_notes_are_html_escaped_for_patient_views(): void
    {
        $patient = $this->user('Patient');
        $reservation = $this->reservation($patient);
        $payload = '<img src=x onerror=alert("security-audit")>';
        $reservation->update(['review_notes' => $payload]);
        $this->actingAs($patient)->get(route('reservations.show', $reservation))
            ->assertOk()->assertSee(e($payload), false)->assertDontSee($payload, false);
    }

    public function test_cross_site_state_change_requires_a_valid_csrf_token(): void
    {
        $patient = $this->user('Patient');
        $this->actingAs($patient);
        // Laravel normally skips CSRF in tests; enable the real middleware path.
        $this->app['env'] = 'local';
        $this->withHeader('Sec-Fetch-Site', 'cross-site')
            ->withSession(['_token' => 'security-audit-csrf'])
            ->putJson(route('account.profile.update'), ['services' => ['patient']])
            ->assertStatus(419);
        $this->putJson(route('account.profile.update'), [
            'services' => ['patient'], '_token' => 'incorrect-token',
        ])->assertStatus(419);
        $this->putJson(route('account.profile.update'), [
            'services' => ['patient'], '_token' => 'security-audit-csrf',
        ])->assertRedirect(route('account.dashboard'));
    }

    private function registration(): array
    {
        return [
            'services' => ['patient'], 'first_name' => 'Public', 'last_name' => 'Tester',
            'birth_date' => '1995-01-01', 'sex' => 'female', 'contact_number' => '+639177770001',
            'email' => 'tamper.public@example.test', 'address' => 'Barangay I (Pob.), Manapla, Negros Occidental',
            'password' => 'password123', 'password_confirmation' => 'password123',
        ];
    }

    private function user(string $role): User
    {
        $user = User::factory()->create(['facility_id' => null, 'is_active' => true]);
        $user->assignRole($role);

        return $user;
    }

    private function reservationPayload(): array
    {
        return [
            'facility_id' => $this->main->id, 'blood_type' => 'O+', 'component' => 'whole_blood',
            'units_requested' => 1, 'needed_on' => today()->addDays(3)->toDateString(),
            'blood_request' => UploadedFile::fake()->create('request.pdf', 1, 'application/pdf'),
            'identification' => UploadedFile::fake()->create('id.pdf', 1, 'application/pdf'),
        ];
    }

    private function reservation(User $patient): BloodReservation
    {
        return BloodReservation::create([
            'reference' => 'SEC-'.str()->random(12), 'patient_user_id' => $patient->id,
            'facility_id' => $this->main->id, 'blood_type' => 'O+', 'component' => 'whole_blood',
            'units_requested' => 1, 'needed_on' => today()->addDays(3), 'status' => 'submitted',
        ]);
    }

    private function donor(User $user): Donor
    {
        return Donor::create([
            'user_id' => $user->id, 'first_name' => 'Security', 'last_name' => 'Donor',
            'birth_date' => '1995-01-01', 'sex' => 'female', 'blood_type' => 'O+',
        ]);
    }

    private function event(string $approval): DonationSchedule
    {
        return DonationSchedule::create([
            'facility_id' => $this->main->id, 'title' => 'Security Test Event',
            'event_type' => 'blood_donation', 'event_date' => today()->addWeek(),
            'start_time' => '09:00', 'end_time' => '12:00', 'venue' => 'Test Hall',
            'start_at' => today()->addWeek()->setTime(9, 0),
            'end_at' => today()->addWeek()->setTime(12, 0),
            'is_public' => true, 'approval_status' => $approval, 'status' => 'planned',
        ]);
    }
}
