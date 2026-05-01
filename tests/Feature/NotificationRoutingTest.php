<?php

namespace Tests\Feature;

use App\Models\BloodInventory;
use App\Models\Facility;
use App\Models\User;
use App\Notifications\FacilityApplicationSubmitted;
use App\Notifications\LowStockAlert;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class NotificationRoutingTest extends TestCase
{
    use RefreshDatabase;

    public function test_low_stock_alerts_go_to_facility_facilitators_and_medical_staff_not_super_admins(): void
    {
        $this->seed(RolePermissionSeeder::class);
        Notification::fake();

        $facility = $this->facility();
        $otherFacility = $this->facility();
        $superAdmin = User::where('email', 'admin@cbis.local')->firstOrFail();
        $facilitator = User::factory()->create(['facility_id' => $facility->id]);
        $facilitator->assignRole('Facilitator');
        $medicalStaff = User::factory()->create(['facility_id' => $facility->id]);
        $medicalStaff->assignRole('Medical Staff / Nurse');
        $otherFacilitator = User::factory()->create(['facility_id' => $otherFacility->id]);
        $otherFacilitator->assignRole('Facilitator');
        $otherMedicalStaff = User::factory()->create(['facility_id' => $otherFacility->id]);
        $otherMedicalStaff->assignRole('Medical Staff / Nurse');

        BloodInventory::create([
            'facility_id' => $facility->id,
            'blood_type' => 'A+',
            'units_available' => 2,
            'expiration_date' => now()->addDays(5)->toDateString(),
            'status' => 'active',
        ]);

        $this->artisan('inventory:notify-low-stock')->assertSuccessful();

        Notification::assertSentTo($facilitator, LowStockAlert::class);
        Notification::assertSentTo($medicalStaff, LowStockAlert::class);
        Notification::assertNotSentTo($superAdmin, LowStockAlert::class);
        Notification::assertNotSentTo($otherFacilitator, LowStockAlert::class);
        Notification::assertNotSentTo($otherMedicalStaff, LowStockAlert::class);
    }

    public function test_facility_application_submission_notifies_super_admins(): void
    {
        $this->seed(RolePermissionSeeder::class);
        Notification::fake();
        Storage::fake('public');

        $superAdmin = User::where('email', 'admin@cbis.local')->firstOrFail();

        $this->post(route('facility-application.store'), [
            'organization_name' => 'Applicant Blood Bank',
            'facility_type' => 'blood_bank',
            'contact_person' => 'Applicant Contact',
            'contact_number' => '09171234567',
            'email' => 'applicant@example.com',
            'address' => 'Applicant Address',
            'doh_accreditation_number' => 'DOH-123',
            'legitimacy_proof' => UploadedFile::fake()->create('legitimacy.pdf', 100, 'application/pdf'),
            'doh_accreditation_proof' => UploadedFile::fake()->create('doh.pdf', 100, 'application/pdf'),
        ])->assertRedirect(route('public.index'));

        Notification::assertSentTo($superAdmin, FacilityApplicationSubmitted::class);
    }

    private function facility(): Facility
    {
        return Facility::create([
            'code' => fake()->unique()->bothify('FAC-###'),
            'name' => fake()->company(),
            'type' => 'blood_bank',
            'is_active' => true,
        ]);
    }
}
