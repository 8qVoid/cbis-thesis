<?php

namespace Tests\Feature;

use App\Models\BloodInventory;
use App\Models\BloodRelease;
use App\Models\BloodReservation;
use App\Models\DonationRecord;
use App\Models\DonationSchedule;
use App\Models\Donor;
use App\Models\EventRegistration;
use App\Models\User;
use App\Notifications\EventPostedNotification;
use App\Notifications\LowStockAlert;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoDataScenarioTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_population_is_protected_when_disabled(): void
    {
        config(['demo.enabled' => false]);

        $this->artisan('demo:populate')->assertFailed();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_demo_population_creates_connected_repeatable_workflows(): void
    {
        config(['demo.enabled' => true]);

        $this->artisan('demo:populate')->assertSuccessful();

        $this->assertSame(14, User::where('email', 'like', 'demo.%@example.test')->count());
        $this->assertSame(10, Donor::whereHas('user', fn ($query) => $query->where('email', 'like', 'demo.%@example.test'))->count());
        $this->assertSame(5, DonationSchedule::where('title', 'like', '[DEMO]%')->count());
        $this->assertGreaterThanOrEqual(10, EventRegistration::whereHas('event', fn ($query) => $query->where('title', 'like', '[DEMO]%'))->count());
        $this->assertSame(8, DonationRecord::where('donation_no', 'like', 'DEMO-DON-%')->count());
        $this->assertGreaterThanOrEqual(7, BloodInventory::whereHas('donationRecord', fn ($query) => $query->where('donation_no', 'like', 'DEMO-DON-%'))->count());
        $this->assertSame(5, BloodReservation::where('reference', 'like', 'DEMO-BR-%')->count());
        $this->assertSame(1, BloodRelease::where('purpose', 'like', '[DEMO]%')->count());
        $this->assertDatabaseHas('notifications', ['type' => EventPostedNotification::class]);
        $this->assertDatabaseHas('notifications', ['type' => LowStockAlert::class]);

        $counts = [
            User::count(), DonationSchedule::count(), EventRegistration::count(), DonationRecord::count(),
            BloodInventory::count(), BloodReservation::count(), BloodRelease::count(),
        ];
        $this->artisan('demo:populate')->assertSuccessful();
        $this->assertSame($counts, [
            User::count(), DonationSchedule::count(), EventRegistration::count(), DonationRecord::count(),
            BloodInventory::count(), BloodReservation::count(), BloodRelease::count(),
        ]);
    }

    public function test_cross_day_reruns_preserve_batch_expiry_and_released_stock(): void
    {
        config(['demo.enabled' => true]);
        $this->artisan('demo:populate')->assertSuccessful();

        $inventory = BloodInventory::orderBy('id')->get(['id', 'units_available', 'expiration_date', 'status'])->toArray();
        $releasedBatch = BloodRelease::firstOrFail()->inventory;
        $this->assertSame(11, $releasedBatch->units_available);

        $this->travel(2)->days();
        $this->artisan('demo:populate')->assertSuccessful();

        $this->assertSame(6, BloodInventory::whereNull('donation_record_id')->count());
        $this->assertSame($inventory, BloodInventory::orderBy('id')->get(['id', 'units_available', 'expiration_date', 'status'])->toArray());
        $this->assertSame(11, $releasedBatch->fresh()->units_available);
        $this->assertDatabaseCount('blood_releases', 1);
    }

    public function test_reruns_preserve_user_edits_and_notification_read_state(): void
    {
        config(['demo.enabled' => true]);
        $this->artisan('demo:populate')->assertSuccessful();

        $user = User::where('email', 'demo.maria-santos@example.test')->firstOrFail();
        $user->update(['name' => 'Updated Demo Name', 'address' => 'Updated address', 'is_active' => false]);
        $user->syncRoles(['Patient']);
        $donor = $user->donorProfile;
        $donor->update(['contact_number' => '09179999999', 'is_eligible' => false]);
        $screening = $donor->screenings()->firstOrFail();
        $screening->update(['status' => 'deferred']);
        $event = DonationSchedule::where('title', '[DEMO] Bacolod Government Center Blood Donation Day')->firstOrFail();
        $event->update(['venue' => 'Updated demonstration venue', 'status' => 'cancelled']);
        $registration = EventRegistration::firstOrFail();
        $registration->update(['status' => 'no_show']);
        $record = DonationRecord::where('donation_no', 'DEMO-DON-003')->firstOrFail();
        $record->update(['remarks' => 'Reviewed by demo operator']);
        $inventory = BloodInventory::whereNull('donation_record_id')->where('blood_type', 'B+')->firstOrFail();
        $inventory->update(['units_available' => 7, 'expiration_date' => today()->addDays(80)]);
        $reservation = BloodReservation::where('reference', 'DEMO-BR-001')->firstOrFail();
        $reservation->update(['status' => 'under_review', 'review_notes' => 'Checked by demo operator']);
        $notification = $user->unreadNotifications()->firstOrFail();
        $notification->markAsRead();
        $readAt = $notification->fresh()->read_at;
        BloodRelease::firstOrFail()->forceDelete();

        $this->travel(1)->day();
        $this->artisan('demo:populate')->assertSuccessful();

        $this->assertSame('Updated Demo Name', $user->fresh()->name);
        $this->assertSame('Updated address', $user->fresh()->address);
        $this->assertFalse($user->fresh()->is_active);
        $this->assertSame(['Patient'], $user->fresh()->getRoleNames()->all());
        $this->assertSame('09179999999', $donor->fresh()->contact_number);
        $this->assertFalse($donor->fresh()->is_eligible);
        $this->assertSame('deferred', $screening->fresh()->status);
        $this->assertSame('Updated demonstration venue', $event->fresh()->venue);
        $this->assertSame('cancelled', $event->fresh()->status);
        $this->assertSame('no_show', $registration->fresh()->status);
        $this->assertSame('Reviewed by demo operator', $record->fresh()->remarks);
        $this->assertSame(7, $inventory->fresh()->units_available);
        $this->assertSame('under_review', $reservation->fresh()->status);
        $this->assertSame('Checked by demo operator', $reservation->fresh()->review_notes);
        $this->assertTrue($notification->fresh()->read_at->equalTo($readAt));
        $this->assertDatabaseCount('blood_releases', 0);
    }

    public function test_reruns_do_not_restore_soft_deleted_demo_records(): void
    {
        config(['demo.enabled' => true]);
        $this->artisan('demo:populate')->assertSuccessful();

        $records = [
            User::where('email', 'demo.maria-santos@example.test')->firstOrFail(),
            User::where('email', 'demo.sofia-martinez@example.test')->firstOrFail(),
            Donor::whereHas('user', fn ($query) => $query->where('email', 'demo.jasmine-flores@example.test'))->firstOrFail(),
            DonationSchedule::where('title', '[DEMO] Bacolod Government Center Blood Donation Day')->firstOrFail(),
            DonationRecord::where('donation_no', 'DEMO-DON-004')->firstOrFail(),
            BloodInventory::whereNull('donation_record_id')->where('blood_type', 'A+')->firstOrFail(),
            BloodRelease::firstOrFail(),
        ];
        foreach ($records as $record) {
            $record->delete();
        }
        BloodReservation::where('reference', 'DEMO-BR-002')->firstOrFail()->update(['patient_user_id' => $records[0]->id]);

        $this->travel(2)->days();
        $this->artisan('demo:populate')->assertSuccessful();

        foreach ($records as $record) {
            $this->assertSoftDeleted($record);
        }
        $this->assertSame(17, User::withTrashed()->count());
        $this->assertSame(10, Donor::withTrashed()->count());
        $this->assertSame(5, DonationSchedule::withTrashed()->count());
        $this->assertSame(8, DonationRecord::withTrashed()->count());
        $this->assertSame(13, BloodInventory::withTrashed()->count());
        $this->assertSame(1, BloodRelease::withTrashed()->count());
    }
}
