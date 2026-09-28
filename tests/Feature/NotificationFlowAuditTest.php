<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\BloodReservationStatusChanged;
use App\Notifications\EventPostedNotification;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationFlowAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_opening_an_event_notification_marks_it_read_and_targets_its_map_pin(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $donor = User::factory()->create(['facility_id' => null, 'is_active' => true]);
        $donor->assignRole('Donor');
        $notification = $donor->notifications()->create([
            'id' => (string) Str::uuid(), 'type' => EventPostedNotification::class,
            'data' => ['title' => 'New donation activity', 'event_id' => 42,
                'event_type' => 'blood_donation', 'event_date' => '2026-09-30'],
        ]);

        $this->actingAs($donor)->get(route('notifications.open', $notification->id))
            ->assertRedirect(route('public.map', ['event_type' => 'blood_donation', 'event_date' => '2026-09-30']).'#event-42');
        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_public_accounts_can_filter_and_read_only_their_own_notifications(): void
    {
        $this->seed(RolePermissionSeeder::class);
        foreach (['Donor' => ['event', EventPostedNotification::class], 'Patient' => ['reservation_status', BloodReservationStatusChanged::class]] as $role => [$filter, $type]) {
            $owner = User::factory()->create(['facility_id' => null, 'is_active' => true]);
            $owner->assignRole($role);
            $other = User::factory()->create(['facility_id' => null, 'is_active' => true]);
            $other->assignRole($role);
            $data = ['title' => 'Private account update', 'event_title' => 'Donation activity', 'status' => 'under_review', 'reference' => 'BR-AUDIT'];
            $mine = $owner->notifications()->create(['id' => (string) Str::uuid(), 'type' => $type, 'data' => $data]);
            $theirs = $other->notifications()->create(['id' => (string) Str::uuid(), 'type' => $type, 'data' => $data]);

            $this->actingAs($owner)->get(route('notifications.index', ['type' => $filter]))->assertOk();
            $this->patch(route('notifications.read', $theirs->id))->assertNotFound();
            $this->patch(route('notifications.read', $mine->id))->assertRedirect();
            $this->assertNotNull($mine->fresh()->read_at);
            $this->post(route('notifications.read-all'))->assertRedirect();
            $this->assertNull($theirs->fresh()->read_at);
            $this->get(route('dashboard'))->assertForbidden();
            $owner->update(['is_active' => false]);
            $this->get(route('notifications.index'))->assertForbidden();
        }
    }
}
