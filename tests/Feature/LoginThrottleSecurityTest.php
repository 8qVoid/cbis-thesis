<?php

namespace Tests\Feature;

use App\Models\Donor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginThrottleSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_markup_whitespace_and_case_share_the_same_login_limit(): void
    {
        $user = User::factory()->create(['email' => 'staff@example.test', 'password' => 'correct-password']);
        $aliases = [
            'staff@example.test',
            '<b>staff@example.test</b>',
            ' <i>staff@example.test</i> ',
            'STAFF@example.test',
            '<span>Staff@EXAMPLE.TEST</span>',
        ];

        foreach ($aliases as $login) {
            $this->post(route('login.store'), ['login' => $login, 'password' => 'wrong-password'])
                ->assertRedirect()->assertSessionHasErrors('login');
        }

        $this->post(route('login.store'), ['login' => $user->email, 'password' => 'correct-password'])
            ->assertTooManyRequests();
        $this->assertGuest('web');
    }

    public function test_mobile_markup_and_whitespace_cannot_reset_the_login_limit(): void
    {
        User::factory()->create(['phone' => '+639171234567', 'password' => 'correct-password']);

        foreach (['09171234567', '<b>09171234567</b>', ' 09171234567 ', '<i>09171234567</i>', '<span>09171234567</span>'] as $login) {
            $this->post(route('login.store'), ['login' => $login, 'password' => 'wrong-password'])
                ->assertRedirect()->assertSessionHasErrors('login');
        }

        $this->post(route('login.store'), ['login' => '09171234567', 'password' => 'correct-password'])
            ->assertTooManyRequests();
        $this->assertGuest('web');
    }

    public function test_rotating_valid_identifiers_is_bounded_by_the_aggregate_ip_limit(): void
    {
        for ($attempt = 1; $attempt <= 30; $attempt++) {
            $this->post(route('login.store'), ['login' => "account{$attempt}@example.test", 'password' => 'wrong-password'])
                ->assertRedirect()->assertSessionHasErrors('login');
        }

        $this->post(route('login.store'), ['login' => 'another@example.test', 'password' => 'wrong-password'])
            ->assertTooManyRequests();
    }

    public function test_another_identity_and_ip_keep_independent_buckets_and_limits_expire(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('login.store'), ['login' => 'first@example.test', 'password' => 'wrong-password'])
                ->assertRedirect();
        }
        $this->post(route('login.store'), ['login' => 'first@example.test', 'password' => 'wrong-password'])
            ->assertTooManyRequests();
        $this->post(route('login.store'), ['login' => 'second@example.test', 'password' => 'wrong-password'])
            ->assertRedirect();
        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.2'])
            ->post(route('login.store'), ['login' => 'first@example.test', 'password' => 'wrong-password'])
            ->assertRedirect();

        $this->travel(61)->seconds();
        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->post(route('login.store'), ['login' => 'first@example.test', 'password' => 'wrong-password'])
            ->assertRedirect();
    }

    public function test_malformed_login_values_are_validation_errors_instead_of_server_errors(): void
    {
        foreach ([['staff@example.test'], null, true, 12345, str_repeat('a', 256)] as $login) {
            $this->postJson(route('login.store'), ['login' => $login, 'password' => 'wrong-password'])
                ->assertUnprocessable()->assertJsonValidationErrors('login');
        }

        $this->postJson(route('login.store'), ['login' => ['another@example.test'], 'password' => 'wrong-password'])
            ->assertTooManyRequests();
    }

    public function test_sanitized_email_login_preserves_database_email_case_matching(): void
    {
        $user = User::factory()->create(['email' => 'Staff@example.test', 'password' => 'correct-password']);

        // SQLite's case-sensitive lookup must not change when the throttle key folds case.
        $this->post(route('login.store'), ['login' => 'staff@example.test', 'password' => 'correct-password'])
            ->assertSessionHasErrors('login');
        $this->assertGuest('web');

        $this->post(route('login.store'), ['login' => ' <b>Staff@example.test</b> ', 'password' => 'correct-password'])
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user, 'web');
    }

    public function test_accepted_mobile_login_still_authenticates_the_legacy_donor_guard(): void
    {
        $donor = Donor::create([
            'first_name' => 'Throttle', 'last_name' => 'Test', 'birth_date' => '2000-01-01',
            'sex' => 'male', 'blood_type' => 'O+', 'contact_number' => '+639171234567',
            'password' => 'correct-password', 'is_online_registered' => true,
        ]);

        $this->post(route('login.store'), ['login' => '<b>09171234567</b>', 'password' => 'correct-password'])
            ->assertRedirect(route('donor.portal.profile'));
        $this->assertAuthenticatedAs($donor, 'donor');
    }
}
