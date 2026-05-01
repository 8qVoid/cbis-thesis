<?php

namespace Tests\Feature;

use App\Models\Facility;
use App\Models\FacilityApplication;
use App\Models\User;
use App\Notifications\FacilityApplicationApproved;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class FacilityApplicationApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_approval_restores_and_reuses_existing_user_with_application_email(): void
    {
        Notification::fake();
        $this->seed(RolePermissionSeeder::class);

        $superAdmin = User::where('email', 'admin@cbis.local')->firstOrFail();
        $existingUser = User::factory()->create([
            'email' => 'facility.applicant@example.test',
            'phone' => null,
            'facility_id' => null,
            'is_active' => false,
        ]);
        $existingUser->delete();

        $application = FacilityApplication::create([
            'organization_name' => 'Reusable Email Blood Bank',
            'facility_type' => 'blood_bank',
            'contact_person' => 'Facility Applicant',
            'contact_number' => '09171234567',
            'email' => 'facility.applicant@example.test',
            'address' => '123 Sample Street',
            'legitimacy_proof_path' => 'facility-applications/legitimacy/sample.pdf',
            'doh_accreditation_proof_path' => 'facility-applications/doh/sample.pdf',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($superAdmin)->put(route('facility-applications.review', $application), [
            'status' => 'approved',
            'review_notes' => 'Approved.',
        ]);

        $response->assertRedirect(route('facility-applications.show', $application));
        $response->assertSessionHasNoErrors();

        $reusedUser = User::where('email', 'facility.applicant@example.test')->firstOrFail();
        $facility = Facility::where('name', 'Reusable Email Blood Bank')->firstOrFail();

        $this->assertNull($reusedUser->deleted_at);
        $this->assertTrue($reusedUser->is_active);
        $this->assertSame($facility->id, $reusedUser->facility_id);
        $this->assertTrue($reusedUser->hasRole('Facilitator'));

        Notification::assertSentOnDemand(FacilityApplicationApproved::class, function (FacilityApplicationApproved $notification): bool {
            return $notification->recipientEmail === 'facility.applicant@example.test'
                && ! empty($notification->temporaryPassword);
        });
        Notification::assertNotSentTo($superAdmin, FacilityApplicationApproved::class);
    }

    public function test_approval_creates_first_facilitator_login_and_emails_temporary_password(): void
    {
        Notification::fake();
        $this->seed(RolePermissionSeeder::class);

        $superAdmin = User::where('email', 'admin@cbis.local')->firstOrFail();
        $application = FacilityApplication::create([
            'organization_name' => 'New Facility Blood Bank',
            'facility_type' => 'blood_bank',
            'contact_person' => 'New Facility Contact',
            'contact_number' => '09171234567',
            'email' => 'new.facility@example.test',
            'address' => '456 Sample Street',
            'legitimacy_proof_path' => 'facility-applications/legitimacy/sample.pdf',
            'doh_accreditation_proof_path' => 'facility-applications/doh/sample.pdf',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($superAdmin)->put(route('facility-applications.review', $application), [
            'status' => 'approved',
            'review_notes' => null,
        ]);

        $response->assertRedirect(route('facility-applications.show', $application));
        $response->assertSessionHasNoErrors();

        $facility = Facility::where('name', 'New Facility Blood Bank')->firstOrFail();
        $staffUser = User::where('email', 'new.facility@example.test')->firstOrFail();

        $this->assertSame($facility->id, $staffUser->facility_id);
        $this->assertTrue($staffUser->hasRole('Facilitator'));

        Notification::assertSentOnDemand(FacilityApplicationApproved::class, function (FacilityApplicationApproved $notification): bool {
            return $notification->recipientEmail === 'new.facility@example.test'
                && ! empty($notification->temporaryPassword);
        });
        Notification::assertNotSentTo($superAdmin, FacilityApplicationApproved::class);
    }
}
