<?php

namespace Tests\Feature;

use App\Models\Donor;
use App\Models\User;
use App\Notifications\AccountResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ForgotPasswordFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_account_can_receive_reset_link_and_reset_password(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'staff@example.com',
            'password' => 'old-password',
            'is_active' => true,
        ]);

        $this->post(route('password.email'), [
            'account_type' => 'staff',
            'email' => $user->email,
        ])->assertSessionHas('success');

        Notification::assertSentTo($user, AccountResetPasswordNotification::class);

        $token = Password::broker('users')->createToken($user);

        $this->post(route('password.reset.update'), [
            'account_type' => 'staff',
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
    }

    public function test_donor_account_can_receive_reset_link_and_reset_password(): void
    {
        Notification::fake();

        $donor = Donor::query()->create([
            'first_name' => 'Quincy',
            'last_name' => 'Dover',
            'birth_date' => now()->subYears(25)->toDateString(),
            'sex' => 'male',
            'blood_type' => 'A+',
            'contact_number' => '09123456789',
            'email' => 'donor@example.com',
            'password' => 'old-password',
            'is_online_registered' => true,
        ]);

        $this->post(route('password.email'), [
            'account_type' => 'donor',
            'email' => $donor->email,
        ])->assertSessionHas('success');

        Notification::assertSentTo($donor, AccountResetPasswordNotification::class);

        $token = Password::broker('donors')->createToken($donor);

        $this->post(route('password.reset.update'), [
            'account_type' => 'donor',
            'token' => $token,
            'email' => $donor->email,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('new-password', $donor->fresh()->password));
    }

    public function test_new_public_account_uses_the_user_password_broker(): void
    {
        Notification::fake();
        Role::findOrCreate('Patient', 'web');
        $patient = User::factory()->create([
            'email' => 'patient@example.com', 'password' => 'old-password', 'is_active' => true,
        ]);
        $patient->assignRole('Patient');

        $this->post(route('password.email'), [
            'account_type' => 'donor', 'email' => $patient->email,
        ])->assertSessionHas('success');
        Notification::assertSentTo($patient, AccountResetPasswordNotification::class, function ($notification) use ($patient) {
            return str_contains($notification->toMail($patient)->actionUrl, '/reset-password/donor/');
        });

        $token = Password::broker('users')->createToken($patient);
        $this->post(route('password.reset.update'), [
            'account_type' => 'donor', 'token' => $token, 'email' => $patient->email,
            'password' => 'new-password', 'password_confirmation' => 'new-password',
        ])->assertRedirect(route('login'));
        $this->assertTrue(Hash::check('new-password', $patient->fresh()->password));

        $this->post(route('password.email'), [
            'account_type' => 'staff', 'email' => $patient->email,
        ])->assertSessionHasErrors('email');
    }

    public function test_repeated_reset_requests_show_a_wait_message(): void
    {
        foreach (range(1, 3) as $_) {
            $this->post(route('password.email'), [
                'account_type' => 'donor', 'email' => 'missing@example.com',
            ])->assertSessionHasErrors('email');
        }

        $this->post(route('password.email'), [
            'account_type' => 'donor', 'email' => 'missing@example.com',
        ])->assertRedirect()->assertSessionHasErrors(['email' => 'Too many password reset requests. Please wait 10 minute(s) before trying again.']);
    }
}
