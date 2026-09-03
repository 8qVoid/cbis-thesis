<?php

namespace Tests\Feature;

use App\Models\BloodInventory;
use App\Models\BloodReservation;
use App\Models\DonationRecord;
use App\Models\DonationSchedule;
use App\Models\Donor;
use App\Models\Facility;
use App\Models\User;
use App\Notifications\ActivityReviewStatusChanged;
use App\Notifications\BloodReservationSubmitted;
use App\Notifications\LowStockAlert;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumenterWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_one_public_account_can_have_donor_and_patient_services(): void
    {
        $facility = $this->facility();
        $response = $this->post(route('donor.register.store'), [
            'services' => ['donor', 'patient'], 'facility_id' => $facility->id,
            'first_name' => 'Maria', 'middle_name' => '', 'last_name' => 'Santos',
            'birth_date' => '1995-01-01', 'sex' => 'female', 'blood_type' => 'O+',
            'contact_number' => '09171234567', 'email' => 'maria@example.test',
            'address' => 'Bacolod City', 'password' => 'password123', 'password_confirmation' => 'password123',
        ]);

        $user = User::where('email', 'maria@example.test')->firstOrFail();
        $response->assertRedirect(route('account.dashboard'));
        $this->assertTrue($user->hasAllRoles(['Donor', 'Patient']));
        $this->assertNotNull($user->donorProfile);
        $this->assertNotNull($user->patientProfile);

        $this->actingAs($user)->put(route('account.profile.update'), ['services' => ['patient']])->assertRedirect(route('account.dashboard'));
        $this->assertFalse($user->fresh()->hasRole('Donor'));
        $this->assertDatabaseCount('donors', 1);
        $this->actingAs($user)->put(route('account.profile.update'), ['services' => ['donor', 'patient'], 'blood_type' => 'O+'])->assertRedirect(route('account.dashboard'));
        $this->assertTrue($user->fresh()->hasAllRoles(['Donor', 'Patient']));
        $this->assertDatabaseCount('donors', 1);
    }

    public function test_patient_account_can_submit_private_requirements_and_bbs_processes_in_order(): void
    {
        Storage::fake('local');
        Notification::fake();
        $facility = $this->facility();
        $qao = User::factory()->create(['facility_id' => null]);
        $qao->assignRole('Quality Assurance Officer');
        $bbs = User::factory()->create(['facility_id' => $facility->id]);
        $bbs->assignRole('Blood Bank Staff');

        $this->post(route('donor.register.store'), [
            'services' => ['patient'], 'facility_id' => $facility->id,
            'first_name' => 'Paolo', 'last_name' => 'Patient', 'birth_date' => '1990-05-05',
            'sex' => 'male', 'contact_number' => '09181234567', 'email' => 'paolo@example.test',
            'address' => 'Bacolod City', 'password' => 'password123', 'password_confirmation' => 'password123',
        ])->assertRedirect(route('account.dashboard'));
        $patient = User::where('email', 'paolo@example.test')->firstOrFail();
        $this->assertTrue($patient->hasRole('Patient'));
        $this->assertFalse($patient->hasRole('Donor'));

        $file = fn (string $name) => UploadedFile::fake()->create($name, 20, 'application/pdf');
        $this->actingAs($patient)->post(route('reservations.store'), [
            'facility_id' => $facility->id, 'blood_type' => 'O+', 'component' => 'packed_red_blood_cells',
            'units_requested' => 2, 'needed_on' => now()->addDays(2)->toDateString(),
            'clinical_purpose' => 'Scheduled treatment', 'blood_request' => $file('blood-request.pdf'),
            'identification' => $file('id.pdf'),
        ])->assertRedirect(route('reservations.index'));

        $reservation = BloodReservation::with('documents')->firstOrFail();
        $this->assertCount(2, $reservation->documents);
        $this->assertEqualsCanonicalizing(['blood_request', 'identification'], $reservation->documents->pluck('type')->all());
        Notification::assertSentTo($qao, BloodReservationSubmitted::class);
        Notification::assertSentTo($bbs, BloodReservationSubmitted::class);

        $document = $reservation->documents->first();
        $this->actingAs($qao)->get(route('reservations.documents.show', [$reservation, $document]))->assertForbidden();
        $this->actingAs($patient)->get(route('reservations.documents.show', [$reservation, $document]))->assertOk();
        $this->actingAs($bbs)->patch(route('reservations.review', $reservation), ['status' => 'approved'])->assertSessionHasErrors('status');
        $this->actingAs($bbs)->patch(route('reservations.review', $reservation), ['status' => 'under_review'])->assertRedirect();
        $this->actingAs($bbs)->patch(route('reservations.review', $reservation->fresh()), ['status' => 'approved'])->assertSessionHasErrors('status');

        BloodInventory::create([
            'facility_id' => $facility->id, 'blood_type' => 'O+', 'component' => 'packed_red_blood_cells',
            'units_available' => 3, 'expiration_date' => now()->addDays(20), 'status' => 'active',
        ]);
        $this->actingAs($bbs)->patch(route('reservations.review', $reservation->fresh()), ['status' => 'approved'])->assertRedirect();
        $this->assertSame('approved', $reservation->fresh()->status);
    }

    public function test_reservation_requires_both_separate_documents_before_creating_a_request(): void
    {
        Storage::fake('local');
        Notification::fake();
        $facility = $this->facility();
        $patient = User::factory()->create();
        $patient->assignRole('Patient');

        foreach (['identification', 'blood_request'] as $missing) {
            $data = [
                'facility_id' => $facility->id, 'blood_type' => 'O+', 'component' => 'whole_blood',
                'units_requested' => 1, 'needed_on' => now()->addDay()->toDateString(),
                'identification' => UploadedFile::fake()->image('id.jpg'),
                'blood_request' => UploadedFile::fake()->image('doctors-request.jpg'),
            ];
            unset($data[$missing]);
            $this->actingAs($patient)->post(route('reservations.store'), $data)->assertSessionHasErrors($missing);
        }

        $this->assertDatabaseCount('blood_reservations', 0);
        $this->assertDatabaseCount('blood_reservation_documents', 0);
        Notification::assertNothingSent();
    }

    public function test_qao_monitors_reservations_while_only_bbs_can_process_them(): void
    {
        $facility = $this->facility();
        $patient = User::factory()->create();
        $patient->assignRole('Patient');
        $qao = User::factory()->create(['facility_id' => null]);
        $qao->assignRole('Quality Assurance Officer');
        $bbs = User::factory()->create(['facility_id' => $facility->id]);
        $bbs->assignRole('Blood Bank Staff');
        $reservation = BloodReservation::create([
            'reference' => 'BR-TEST-1', 'patient_user_id' => $patient->id, 'facility_id' => $facility->id,
            'blood_type' => 'A+', 'component' => 'whole_blood', 'units_requested' => 1,
            'needed_on' => now()->addDay(), 'status' => 'submitted',
        ]);

        $this->actingAs($qao)->get(route('reservations.show', $reservation))->assertOk();
        $this->actingAs($qao)->patch(route('reservations.review', $reservation), ['status' => 'approved'])->assertForbidden();
        $this->actingAs($bbs)->patch(route('reservations.review', $reservation), ['status' => 'under_review'])->assertRedirect();
        $this->assertSame('under_review', $reservation->fresh()->status);
    }

    public function test_qao_navigation_only_shows_pages_it_can_open(): void
    {
        $qao = User::factory()->create(['facility_id' => null]);
        $qao->assignRole('Quality Assurance Officer');

        $response = $this->actingAs($qao)->get(route('dashboard'));

        $response->assertOk()
            ->assertSee('Records')
            ->assertSee('Inventory')
            ->assertSee('Reservations')
            ->assertSee('Events')
            ->assertSee('Locations')
            ->assertDontSee('href="'.route('donation-records.index').'"', false)
            ->assertDontSee('href="'.route('bloodletting-records.index').'"', false);

        $this->actingAs($qao)->get(route('blood-bank-locations.index'))->assertOk();
        $this->actingAs($qao)->get(route('blood-bank-locations.create'))->assertOk()->assertSee('Select facility');
    }

    public function test_event_is_hidden_until_qao_approves_it(): void
    {
        Storage::fake('public');
        Notification::fake();
        $facility = $this->facility();
        $facilitator = User::factory()->create(['facility_id' => $facility->id]);
        $facilitator->assignRole('Event Facilitator');
        $qao = User::factory()->create(['facility_id' => null]);
        $qao->assignRole('Quality Assurance Officer');
        $this->assertTrue($facilitator->can('manage schedules'));
        $this->assertTrue($facilitator->is_active);

        $this->actingAs($facilitator)->post(route('donation-schedules.store'), [
            'title' => 'Negros Blood Drive', 'event_type' => 'blood_donation',
            'event_date' => now()->addWeek()->toDateString(), 'start_time' => '09:00', 'end_time' => '12:00',
            'venue' => 'City Hall', 'latitude' => 10.67, 'longitude' => 122.95,
            'status' => 'planned', 'photo' => UploadedFile::fake()->image('event.jpg'),
        ])->assertRedirect(route('donation-schedules.index'));

        $event = DonationSchedule::firstOrFail();
        $this->assertSame('pending', $event->approval_status);
        $this->assertFalse($event->is_public);
        $this->actingAs($qao)->patch(route('donation-schedules.review', $event), ['approval_status' => 'approved'])->assertRedirect();
        $this->assertTrue($event->fresh()->is_public);
        Notification::assertSentTo($facilitator, ActivityReviewStatusChanged::class);

        $donorUser = User::factory()->create();
        $donorUser->assignRole('Donor');
        $donor = Donor::create([
            'user_id' => $donorUser->id, 'first_name' => 'Evan', 'last_name' => 'Donor',
            'birth_date' => '1994-04-04', 'sex' => 'male', 'blood_type' => 'B+',
            'contact_number' => '+639201234567', 'address' => 'Bacolod', 'is_eligible' => true,
            'is_online_registered' => true,
        ]);
        $this->actingAs($donorUser)->post(route('donor.events.register', $event->fresh()))->assertRedirect(route('public.map'));
        $this->assertDatabaseHas('event_registrations', ['donor_id' => $donor->id, 'donation_schedule_id' => $event->id, 'status' => 'registered']);
        $this->actingAs($donorUser)->delete(route('donor.events.cancel', $event->fresh()))->assertRedirect();
        $this->assertDatabaseHas('event_registrations', ['donor_id' => $donor->id, 'donation_schedule_id' => $event->id, 'status' => 'cancelled']);
    }

    public function test_bbs_summary_has_no_export_permission_and_inventory_is_facility_scoped(): void
    {
        $first = $this->facility('FAC-1');
        $second = $this->facility('FAC-2');
        $bbs = User::factory()->create(['facility_id' => $first->id]);
        $bbs->assignRole('Blood Bank Staff');
        $this->assertTrue($bbs->can('request summaries'));
        $this->assertTrue($bbs->is_active);
        BloodInventory::create(['facility_id' => $first->id, 'blood_type' => 'O+', 'component' => 'whole_blood', 'units_available' => 10, 'expiration_date' => now()->addDays(10), 'status' => 'active']);
        BloodInventory::create(['facility_id' => $second->id, 'blood_type' => 'AB+', 'component' => 'fresh_frozen_plasma', 'units_available' => 9, 'expiration_date' => now()->addDays(10), 'status' => 'active']);

        $this->actingAs($bbs)->get(route('reports.index'))->assertOk()->assertSee('O+')->assertDontSee('AB+')->assertSee('Summary view only');
        $this->actingAs($bbs)->get(route('reports.excel'))->assertForbidden();
    }

    public function test_low_stock_alerts_only_reach_qao_and_the_assigned_facility_bbs(): void
    {
        Notification::fake();
        $first = $this->facility('ALERT-1');
        $second = $this->facility('ALERT-2');
        $qao = User::factory()->create(['facility_id' => null]);
        $qao->assignRole('Quality Assurance Officer');
        $bbs = User::factory()->create(['facility_id' => $first->id]);
        $bbs->assignRole('Blood Bank Staff');
        $otherBbs = User::factory()->create(['facility_id' => $second->id]);
        $otherBbs->assignRole('Blood Bank Staff');
        $facilitator = User::factory()->create(['facility_id' => $first->id]);
        $facilitator->assignRole('Event Facilitator');
        BloodInventory::create([
            'facility_id' => $first->id, 'blood_type' => 'AB-', 'component' => 'platelet_concentrate',
            'units_available' => 4, 'expiration_date' => now()->addDays(4), 'status' => 'active',
        ]);

        $this->artisan('inventory:notify-low-stock')->assertSuccessful();

        Notification::assertSentTo($qao, LowStockAlert::class);
        Notification::assertSentTo($bbs, LowStockAlert::class);
        Notification::assertNotSentTo($otherBbs, LowStockAlert::class);
        Notification::assertNotSentTo($facilitator, LowStockAlert::class);
    }

    public function test_only_verified_donations_add_stock_and_releases_decrease_it(): void
    {
        $facility = $this->facility('FLOW-1');
        $bbs = User::factory()->create(['facility_id' => $facility->id]);
        $bbs->assignRole('Blood Bank Staff');
        $donor = Donor::create([
            'facility_id' => $facility->id, 'first_name' => 'Donna', 'last_name' => 'Reyes',
            'birth_date' => '1992-02-02', 'sex' => 'female', 'blood_type' => 'A+',
            'contact_number' => '+639191234567', 'address' => 'Negros', 'is_eligible' => true,
        ]);
        $payload = [
            'donor_id' => $donor->id, 'donation_no' => 'DN-FLOW-1',
            'donated_at' => now()->subHour()->format('Y-m-d H:i:s'), 'blood_type' => 'A+',
            'volume_ml' => 900, 'expiration_date' => now()->addDays(30)->toDateString(),
            'status' => 'pending', 'remarks' => 'Awaiting verification',
        ];

        $this->actingAs($bbs)->post(route('donation-records.store'), $payload)->assertRedirect();
        $record = DonationRecord::firstOrFail();
        $this->assertDatabaseCount('blood_inventory', 0);

        $this->actingAs($bbs)->put(route('donation-records.update', $record), [
            ...$payload, 'status' => 'verified', 'remarks' => 'Verified',
        ])->assertRedirect();
        $inventory = BloodInventory::where('donation_record_id', $record->id)->firstOrFail();
        $this->assertSame(2, $inventory->units_available);
        $this->assertSame('whole_blood', $inventory->component);

        $this->actingAs($bbs)->post(route('blood-releases.store'), [
            'blood_inventory_id' => $inventory->id, 'patient_name' => 'Sample Patient',
            'requesting_unit' => 'Emergency Room', 'released_at' => now()->format('Y-m-d H:i:s'),
            'units_released' => 1, 'purpose' => 'Transfusion',
        ])->assertRedirect();
        $this->assertSame(1, $inventory->fresh()->units_available);
    }

    private function facility(string $code = 'FAC-TEST'): Facility
    {
        return Facility::create(['code' => $code, 'name' => "Red Cross {$code}", 'type' => 'blood_bank', 'is_active' => true, 'is_main_chapter' => ! Facility::where('is_main_chapter', true)->exists()]);
    }
}
