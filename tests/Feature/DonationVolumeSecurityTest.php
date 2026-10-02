<?php

namespace Tests\Feature;

use App\Events\DonationRecorded;
use App\Listeners\IncreaseInventoryFromDonation;
use App\Livewire\DonationRecords\CreateDonationRecord;
use App\Models\BloodInventory;
use App\Models\DonationRecord;
use App\Models\DonationSchedule;
use App\Models\Donor;
use App\Models\EventRegistration;
use App\Models\Facility;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DonationVolumeSecurityTest extends TestCase
{
    use RefreshDatabase;

    private User $staff;

    private Facility $main;

    private Donor $donor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertSame('sqlite', config('database.default'));
        $this->assertSame(':memory:', config('database.connections.sqlite.database'));
        $this->seed(RolePermissionSeeder::class);
        $this->main = Facility::create([
            'code' => 'VOLUME-MAIN', 'name' => 'Volume Test Main', 'type' => 'blood_bank',
            'is_main_chapter' => true, 'is_active' => true,
        ]);
        $this->staff = User::factory()->create(['facility_id' => $this->main->id, 'is_active' => true]);
        $this->staff->assignRole('Blood Bank Staff');
        $this->donor = Donor::create([
            'facility_id' => $this->main->id, 'first_name' => 'Fresh', 'last_name' => 'Volume Test',
            'birth_date' => '2000-01-01', 'sex' => 'male', 'blood_type' => 'A+',
        ]);
    }

    public function test_oversized_pending_to_verified_update_leaves_donation_and_stock_unchanged(): void
    {
        $payload = $this->payload();
        $record = DonationRecord::create([...$payload, 'recorded_by' => $this->staff->id]);
        $before = $record->refresh()->getAttributes();

        $this->actingAs($this->staff)->put(route('donation-records.update', $record), [
            ...$payload, 'volume_ml' => 450000, 'status' => 'verified', 'remarks' => 'Forged update',
        ])->assertSessionHasErrors('volume_ml');

        $this->assertSame($before, $record->fresh()->getAttributes());
        $this->assertDatabaseCount('blood_inventory', 0);
        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_http_creation_rejects_a_volume_above_the_existing_limit(): void
    {
        $this->actingAs($this->staff)->post(route('donation-records.store'), [
            ...$this->payload(), 'volume_ml' => 5001, 'status' => 'verified',
        ])->assertSessionHasErrors('volume_ml');

        $this->assertDatabaseCount('donation_records', 0);
        $this->assertDatabaseCount('blood_inventory', 0);
    }

    public function test_livewire_creation_rejects_a_tampered_oversized_volume(): void
    {
        $this->livewireDonation(5001)->call('save')->assertHasErrors(['volume_ml' => 'max']);

        $this->assertDatabaseCount('donation_records', 0);
        $this->assertDatabaseCount('blood_inventory', 0);
    }

    public function test_creation_and_verification_accept_the_existing_upper_boundary(): void
    {
        $payload = [...$this->payload(), 'volume_ml' => 5000];
        $this->actingAs($this->staff)->post(route('donation-records.store'), $payload)
            ->assertSessionHasNoErrors()->assertRedirect(route('donation-records.index'));
        $record = DonationRecord::sole();

        $this->put(route('donation-records.update', $record), [...$payload, 'status' => 'verified'])
            ->assertSessionHasNoErrors()->assertRedirect(route('donation-records.index'));

        $this->assertSame('verified', $record->fresh()->status);
        $this->assertSame(5000, (int) $record->fresh()->volume_ml);
        $this->assertSame(11, BloodInventory::sole()->units_available);
    }

    public function test_listener_rejects_an_oversized_record_created_outside_http_validation(): void
    {
        $record = DonationRecord::create([
            ...$this->payload(), 'recorded_by' => $this->staff->id,
            'volume_ml' => 450000, 'status' => 'verified',
        ]);

        try {
            event(new DonationRecorded($record));
            $this->fail('The inventory listener must reject an oversized donation.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('volume_ml', $exception->errors());
        }

        $this->assertDatabaseCount('blood_inventory', 0);
    }

    #[DataProvider('invalidVolumes')]
    public function test_listener_rejects_invalid_volumes_before_creating_stock(mixed $volume): void
    {
        $record = new DonationRecord([...$this->payload(), 'volume_ml' => $volume, 'status' => 'verified']);

        try {
            app(IncreaseInventoryFromDonation::class)->handle(new DonationRecorded($record));
            $this->fail('The inventory listener must reject an invalid donation volume.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('volume_ml', $exception->errors());
        }

        $this->assertDatabaseCount('blood_inventory', 0);
    }

    public static function invalidVolumes(): array
    {
        return [
            'missing' => [null], 'zero' => [0], 'negative' => [-1],
            'fractional' => [450.5], 'nonnumeric' => ['invalid'], 'above limit' => [5001],
        ];
    }

    public function test_http_creation_rolls_back_if_stock_processing_rejects_the_donation(): void
    {
        $this->rejectStockProcessing();
        $this->actingAs($this->staff)->post(route('donation-records.store'), [
            ...$this->payload(), 'status' => 'verified',
        ])->assertSessionHasErrors('volume_ml');

        $this->assertDatabaseCount('donation_records', 0);
        $this->assertDatabaseCount('blood_inventory', 0);
        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_http_verification_rolls_back_if_stock_processing_rejects_the_donation(): void
    {
        $payload = $this->payload();
        $record = DonationRecord::create([...$payload, 'recorded_by' => $this->staff->id]);
        $before = $record->refresh()->getAttributes();
        $this->rejectStockProcessing();

        $this->actingAs($this->staff)->put(route('donation-records.update', $record), [
            ...$payload, 'status' => 'verified', 'remarks' => 'Should roll back',
        ])->assertSessionHasErrors('volume_ml');

        $this->assertSame($before, $record->fresh()->getAttributes());
        $this->assertDatabaseCount('blood_inventory', 0);
        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_livewire_creation_rolls_back_donation_stock_and_attendance_on_processing_failure(): void
    {
        $event = DonationSchedule::create([
            'facility_id' => $this->main->id, 'title' => 'Volume Test Drive', 'event_type' => 'blood_donation',
            'event_date' => today(), 'start_time' => '08:00', 'end_time' => '23:59',
            'start_at' => today()->setTime(8, 0), 'end_at' => today()->endOfDay(),
            'venue' => 'Test Hall', 'is_public' => true, 'approval_status' => 'approved', 'status' => 'ongoing',
        ]);
        $registration = EventRegistration::create([
            'facility_id' => $this->main->id, 'donation_schedule_id' => $event->id,
            'donor_id' => $this->donor->id, 'status' => 'registered', 'registered_at' => now(),
        ]);
        $this->rejectStockProcessing();

        $this->livewireDonation(450)->set('selected_event_id', $event->id)
            ->call('save')->assertHasErrors('volume_ml');

        $this->assertSame('registered', $registration->fresh()->status);
        $this->assertDatabaseCount('donation_records', 0);
        $this->assertDatabaseCount('blood_inventory', 0);
        $this->assertDatabaseCount('audit_logs', 0);
    }

    private function rejectStockProcessing(): void
    {
        Event::listen(DonationRecorded::class, function (): void {
            throw ValidationException::withMessages(['volume_ml' => 'Stock processing rejected this donation.']);
        });
    }

    private function livewireDonation(int $volume)
    {
        return Livewire::actingAs($this->staff)->test(CreateDonationRecord::class)
            ->set('donor_id', $this->donor->id)->set('donated_at', now()->toDateTimeString())
            ->set('volume_ml', $volume)->set('expiration_date', today()->addDays(30)->toDateString())
            ->set('status', 'verified');
    }

    private function payload(): array
    {
        return [
            'facility_id' => $this->main->id, 'donor_id' => $this->donor->id,
            'donation_no' => 'DN-VOLUME-SECURITY', 'donated_at' => now()->toDateTimeString(),
            'blood_type' => 'A+', 'volume_ml' => 450,
            'expiration_date' => today()->addDays(30)->toDateString(), 'status' => 'pending',
        ];
    }
}
