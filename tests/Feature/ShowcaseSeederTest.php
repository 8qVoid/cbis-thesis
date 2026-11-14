<?php

namespace Tests\Feature;

use App\Models\BloodInventory;
use App\Models\BloodReservation;
use App\Models\DonationSchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ShowcaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_local_showcase_records_are_connected_repeatable_and_send_no_mail(): void
    {
        Mail::fake();

        $this->artisan('showcase:populate')->assertExitCode(0);

        $this->assertSame(100, User::where('email', 'like', '%@cbis.test')->count());
        $this->assertSame(40, BloodInventory::whereNotNull('showcase_key')->count());
        $this->assertSame(32, BloodReservation::where('reference', 'like', 'SC-REQ-%')->count());
        $this->assertSame(12, DonationSchedule::where('title', 'like', 'Community Blood Drive - %')->count());
        $this->assertGreaterThan(100, DB::table('notifications')->count());
        $this->assertNotNull(User::where('email', 'maria.santos.001@cbis.test')->firstOrFail()->email_verified_at);

        foreach (BloodReservation::where('reference', 'like', 'SC-REQ-%')->where('status', 'fulfilled')->get() as $request) {
            $this->assertSame($request->units_requested, (int) $request->releases()->sum('units_released'));
        }

        $counts = [
            User::count(), BloodInventory::count(), BloodReservation::count(),
            DonationSchedule::count(), DB::table('notifications')->count(),
        ];
        $this->artisan('showcase:populate')->assertExitCode(0);
        $this->assertSame($counts, [
            User::count(), BloodInventory::count(), BloodReservation::count(),
            DonationSchedule::count(), DB::table('notifications')->count(),
        ]);
        Mail::assertNothingSent();
    }
}
