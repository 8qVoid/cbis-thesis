<?php

namespace Tests\Feature;

use App\Models\DonationSchedule;
use App\Models\Facility;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class EmailVerificationSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        Notification::fake();
    }

    public function test_public_registration_requires_email_verification_before_account_and_event_access(): void
    {
        $facility = Facility::create([
            'code' => 'VERIFY', 'name' => 'Verification Facility', 'type' => 'blood_bank',
            'is_active' => true, 'is_main_chapter' => true,
        ]);
        $event = DonationSchedule::create([
            'facility_id' => $facility->id, 'title' => 'Verified Donor Drive',
            'event_type' => 'blood_donation', 'event_date' => today()->addWeek(),
            'start_time' => '09:00', 'end_time' => '12:00',
            'start_at' => today()->addWeek()->setTime(9, 0), 'end_at' => today()->addWeek()->setTime(12, 0),
            'venue' => 'Verification Hall', 'is_public' => true, 'approval_status' => 'approved', 'status' => 'planned',
        ]);

        $this->post(route('donor.register.store'), $this->registration([
            'event_id' => $event->id, 'services' => ['donor'], 'blood_type' => 'O+',
        ]))->assertRedirect(route('verification.notice'));

        $user = User::where('email', 'verify@example.test')->sole();
        $this->assertFalse($user->hasVerifiedEmail());
        Notification::assertSentTo($user, VerifyEmail::class);
        $this->assertDatabaseMissing('event_registrations', ['donation_schedule_id' => $event->id]);
        $this->get(route('account.dashboard'))->assertRedirect(route('verification.notice'));
        $this->get(route('donor.events.index'))->assertRedirect(route('verification.notice'));

        $verificationUrl = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
            'id' => $user->id, 'hash' => sha1($user->getEmailForVerification()),
        ]);
        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->get($verificationUrl)->assertRedirect(route('login'));
        $this->post(route('login.store'), [
            'login' => 'verify@example.test', 'password' => 'StrongPass123!',
        ])->assertRedirect($verificationUrl);
        $this->get($verificationUrl)->assertRedirect(route('account.dashboard'))
            ->assertSessionHas('success', fn ($message) => str_contains($message, 'Email verified'));

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $this->assertDatabaseHas('event_registrations', [
            'donation_schedule_id' => $event->id, 'donor_id' => $user->donorProfile->id, 'status' => 'registered',
        ]);
    }

    public function test_registration_rejects_weak_passwords_and_shows_strength_controls(): void
    {
        $this->postJson(route('donor.register.store'), $this->registration([
            'password' => 'password123', 'password_confirmation' => 'password123',
        ]))->assertUnprocessable()->assertJsonValidationErrors('password');
        $this->assertDatabaseMissing('users', ['email' => 'verify@example.test']);

        $this->get(route('donor.register'))->assertOk()
            ->assertSee('id="passwordStrength"', false)
            ->assertSee('id="showRegistrationPassword"', false)
            ->assertSee('You must verify your email before using account services.');
    }

    public function test_unverified_public_login_returns_to_verification_notice_and_resend_is_available(): void
    {
        $user = User::factory()->unverified()->create([
            'email' => 'unverified@example.test', 'password' => 'StrongPass123!',
        ]);
        $user->assignRole('Patient');

        $this->post(route('login.store'), [
            'login' => 'unverified@example.test', 'password' => 'StrongPass123!',
        ])->assertRedirect(route('verification.notice'));
        $this->get(route('verification.notice'))->assertOk()->assertSee('unverified@example.test');
        $this->post(route('verification.send'))->assertRedirect();
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    #[DataProvider('publicRoles')]
    public function test_changing_public_profile_email_requires_verification_of_the_new_address(array $roles): void
    {
        $user = User::factory()->create(['email' => 'original.profile@example.test']);
        $user->assignRole($roles);
        $passwordHash = $user->password;
        $oldVerificationUrl = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
            'id' => $user->id, 'hash' => sha1($user->getEmailForVerification()),
        ]);

        $this->actingAs($user)->put(route('account.details.update'), $this->profile([
            'email' => 'changed.profile@example.test',
            'email_verified_at' => now()->toDateTimeString(),
        ]))->assertSessionHasNoErrors()->assertRedirect(route('verification.notice'));

        $user->refresh();
        $this->assertSame('changed.profile@example.test', $user->email);
        $this->assertFalse($user->hasVerifiedEmail());
        $this->assertSame($passwordHash, $user->password);
        $this->assertEqualsCanonicalizing($roles, $user->getRoleNames()->all());
        Notification::assertSentToTimes($user, VerifyEmail::class, 1);
        $this->get(route('account.dashboard'))->assertRedirect(route('verification.notice'));
        $this->get(route('reservations.create'))->assertRedirect(route('verification.notice'));
        $this->get($oldVerificationUrl)->assertForbidden();
        $this->assertFalse($user->fresh()->hasVerifiedEmail());

        $newVerificationUrl = URL::temporarySignedRoute('verification.verify', now()->addHour(), [
            'id' => $user->id, 'hash' => sha1($user->getEmailForVerification()),
        ]);
        $this->get($newVerificationUrl)->assertRedirect(route('account.dashboard'));
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $this->get(route('account.dashboard'))->assertOk();
    }

    public static function publicRoles(): array
    {
        return [
            'donor' => [['Donor']],
            'patient' => [['Patient']],
            'donor and patient' => [['Donor', 'Patient']],
        ];
    }

    public function test_saving_public_profile_with_the_same_email_keeps_verification_and_sends_no_link(): void
    {
        $user = User::factory()->create(['email' => 'unchanged.profile@example.test']);
        $user->assignRole(['Donor', 'Patient']);
        $verifiedAt = $user->email_verified_at->toDateTimeString();
        $passwordHash = $user->password;

        $this->actingAs($user)->put(route('account.details.update'), $this->profile([
            'email' => $user->email, 'first_name' => 'Updated',
        ]))->assertSessionHasNoErrors()->assertRedirect(route('account.details.edit'));

        $user->refresh();
        $this->assertSame('Updated Profile', $user->name);
        $this->assertTrue($user->hasVerifiedEmail());
        $this->assertSame($verifiedAt, $user->email_verified_at->toDateTimeString());
        $this->assertSame($passwordHash, $user->password);
        $this->assertTrue($user->hasAllRoles(['Donor', 'Patient']));
        Notification::assertNothingSent();
        $this->get(route('account.details.edit'))->assertOk();
    }

    private function profile(array $overrides = []): array
    {
        return [
            'first_name' => 'Verify', 'last_name' => 'Profile',
            'address' => 'Barangay I (Pob.), Manapla, Negros Occidental',
            'email' => 'original.profile@example.test', 'phone' => '09179876543',
            ...$overrides,
        ];
    }

    private function registration(array $overrides = []): array
    {
        return [
            'services' => ['patient'], 'first_name' => 'Verify', 'last_name' => 'Account',
            'birth_date' => '1995-01-01', 'sex' => 'female', 'contact_number' => '09171230000',
            'email' => 'verify@example.test', 'address' => 'Barangay I (Pob.), Manapla, Negros Occidental',
            'password' => 'StrongPass123!', 'password_confirmation' => 'StrongPass123!', ...$overrides,
        ];
    }
}
