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
}
