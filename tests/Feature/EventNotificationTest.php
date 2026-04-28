<?php

namespace Tests\Feature;

use App\Models\Donor;
use App\Models\Facility;
use App\Models\User;
use App\Notifications\EventPostedNotification;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EventNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_event_creation_notifies_online_eligible_donors_by_email(): void
    {
        $this->seed(RolePermissionSeeder::class);
        Notification::fake();
        Storage::fake('public');

        $facility = Facility::create([
            'code' => 'BB-777',
            'name' => 'Event Blood Bank',
            'type' => 'blood_bank',
            'is_active' => true,
        ]);

        $facilitator = User::factory()->create(['facility_id' => $facility->id]);
        $facilitator->assignRole('Facilitator');

        $notifiedDonor = $this->donor([
            'email' => 'eligible@example.com',
            'is_online_registered' => true,
            'is_eligible' => true,
            'password' => Hash::make('password'),
        ]);
        $offlineDonor = $this->donor([
            'email' => 'offline@example.com',
            'is_online_registered' => false,
            'is_eligible' => true,
            'password' => null,
        ]);
        $ineligibleDonor = $this->donor([
            'email' => 'ineligible@example.com',
            'is_online_registered' => true,
            'is_eligible' => false,
            'password' => Hash::make('password'),
        ]);

        $this->actingAs($facilitator)->post(route('donation-schedules.store'), [
            'title' => 'Community Blood Drive',
            'event_type' => 'blood_donation',
            'event_date' => now()->addWeek()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '12:00',
            'venue' => 'City Gym',
            'contact_person' => 'Event Coordinator',
            'contact_number' => '09171234567',
            'is_public' => '1',
            'status' => 'planned',
            'photo' => UploadedFile::fake()->image('community-blood-drive.jpg'),
        ])->assertRedirect(route('donation-schedules.index'));

        Notification::assertSentTo($notifiedDonor, EventPostedNotification::class);
        Notification::assertNotSentTo($offlineDonor, EventPostedNotification::class);
        Notification::assertNotSentTo($ineligibleDonor, EventPostedNotification::class);
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function donor(array $overrides = []): Donor
    {
        return Donor::create(array_merge([
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'birth_date' => now()->subYears(25)->toDateString(),
            'sex' => 'male',
            'blood_type' => 'O+',
            'contact_number' => '+639171234567',
            'address' => fake()->address(),
            'is_eligible' => true,
            'is_online_registered' => true,
        ], $overrides));
    }
}
