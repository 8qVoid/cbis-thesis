<?php

namespace Tests\Feature;

use App\Models\BloodInventory;
use App\Models\BloodReservation;
use App\Models\DonationSchedule;
use App\Models\EventRegistration;
use App\Models\Facility;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ShowcaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_local_showcase_records_are_connected_repeatable_and_send_no_mail(): void
    {
        Mail::fake();
        Storage::fake('local');

        $this->artisan('showcase:populate')->assertExitCode(0);

        $this->assertSame(100, User::where('is_showcase', true)->where('email', 'like', '%@gmail.com')->count());
        $this->assertSame(40, BloodInventory::whereNotNull('showcase_key')->count());
        $this->assertSame(32, BloodReservation::where('reference', 'like', 'SC-REQ-%')->count());
        $this->assertSame(64, DB::table('blood_reservation_documents')->count());
        $firstRequest = BloodReservation::where('reference', 'SC-REQ-001')->firstOrFail();
        foreach ($firstRequest->documents as $document) {
            $this->assertTrue(Storage::disk('local')->exists($document->path));
            $this->assertStringStartsWith('%PDF', Storage::disk('local')->get($document->path));
        }
        $staff = User::role('Blood Bank Staff')->where('facility_id', $firstRequest->facility_id)->firstOrFail();
        $this->actingAs($staff)->get(route('reservations.documents.show', [$firstRequest, $firstRequest->documents->first()]))
            ->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertSame(12, DonationSchedule::where('title', 'like', 'Community Blood Drive - %')->count());
        $this->assertSame(['FAC-001', 'FAC-002'], Facility::orderBy('code')->pluck('code')->all());
        $this->assertSame(5, DonationSchedule::where('is_public', true)->where('status', 'planned')->count());
        $this->assertGreaterThan(100, DB::table('notifications')->count());
        $this->assertNotNull(User::where('email', 'maria.santos.001@gmail.com')->firstOrFail()->email_verified_at);

        foreach (BloodReservation::where('reference', 'like', 'SC-REQ-%')->where('status', 'fulfilled')->get() as $request) {
            $this->assertSame($request->units_requested, (int) $request->releases()->sum('units_released'));
        }

        $counts = [
            User::count(), BloodInventory::count(), BloodReservation::count(),
            DonationSchedule::count(), DB::table('notifications')->count(), DB::table('blood_reservation_documents')->count(),
        ];
        $oldBranch = Facility::create([
            'code' => 'SC-MANAPLA', 'name' => 'Manapla Community Branch', 'type' => 'blood_bank',
            'address' => 'Manapla', 'is_active' => true,
        ]);
        $manaplaEvent = DonationSchedule::where('venue', 'like', 'Manapla Public Plaza%')->firstOrFail();
        $manaplaEvent->update(['facility_id' => $oldBranch->id]);
        EventRegistration::where('donation_schedule_id', $manaplaEvent->id)->update(['facility_id' => $oldBranch->id]);
        User::where('email', 'maria.santos.001@gmail.com')->firstOrFail()
            ->forceFill(['email' => 'maria.santos.001@cbis.test'])->save();
        $this->artisan('showcase:populate')->assertExitCode(0);
        $this->assertSoftDeleted($oldBranch);
        $this->assertSame(Facility::where('code', 'FAC-002')->value('id'), $manaplaEvent->fresh()->facility_id);
        $this->assertSame(0, EventRegistration::where('donation_schedule_id', $manaplaEvent->id)->where('facility_id', $oldBranch->id)->count());
        $this->assertSame(['FAC-001', 'FAC-002'], Facility::orderBy('code')->pluck('code')->all());
        $this->assertNotNull(User::where('email', 'maria.santos.001@gmail.com')->first());
        $this->assertSame($counts, [
            User::count(), BloodInventory::count(), BloodReservation::count(),
            DonationSchedule::count(), DB::table('notifications')->count(), DB::table('blood_reservation_documents')->count(),
        ]);
        Mail::assertNothingSent();
    }
}
