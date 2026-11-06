<?php

namespace Tests\Feature;

use App\Models\Donor;
use App\Models\User;
use App\Notifications\AccountResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
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
            'email' => $user->email,
        ])->assertSessionHas('success');

        Notification::assertSentTo($user, AccountResetPasswordNotification::class);

        $token = Password::broker('users')->createToken($user);

        $this->post(route('password.reset.update'), [
            'account_type' => 'staff',
            'token' => $token,
            'email' => $user->email,
            'password' => 'New-password1',
            'password_confirmation' => 'New-password1',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('New-password1', $user->fresh()->password));
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
            'email' => $donor->email,
        ])->assertSessionHas('success');

        Notification::assertSentTo($donor, AccountResetPasswordNotification::class);

        $token = Password::broker('donors')->createToken($donor);

        $this->post(route('password.reset.update'), [
            'account_type' => 'donor',
            'token' => $token,
            'email' => $donor->email,
            'password' => 'New-password1',
            'password_confirmation' => 'New-password1',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('New-password1', $donor->fresh()->password));
    }
}
