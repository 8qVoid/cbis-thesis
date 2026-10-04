<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RegistrationValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        User::all()->each(fn (User $user) => $user->forceDelete());
        Notification::fake();
    }

    #[DataProvider('unselectedServices')]
    public function test_registration_requires_at_least_one_service(bool $omitServices): void
    {
        $payload = $this->registration(['services' => []]);
        if ($omitServices) {
            unset($payload['services']);
        }

        $this->postJson(route('donor.register.store'), $payload)
            ->assertUnprocessable()->assertJsonValidationErrors('services');

        $this->assertNoRegistration();
    }

    public static function unselectedServices(): array
    {
        return ['omitted checkboxes' => [true], 'empty service list' => [false]];
    }

    #[DataProvider('requiredFields')]
    public function test_registration_rejects_each_missing_required_field(string $field): void
    {
        $payload = $this->registration();
        unset($payload[$field]);

        $this->postJson(route('donor.register.store'), $payload)
            ->assertUnprocessable()->assertJsonValidationErrors($field);

        $this->assertNoRegistration();
    }

    public static function requiredFields(): array
    {
        return [
            'first name' => ['first_name'], 'last name' => ['last_name'],
            'birth date' => ['birth_date'], 'sex' => ['sex'],
            'mobile number' => ['contact_number'], 'email' => ['email'],
            'address' => ['address'], 'password' => ['password'],
            'password confirmation' => ['password_confirmation'],
        ];
    }

    #[DataProvider('invalidBirthDates')]
    public function test_registration_rejects_today_and_future_birth_dates(int $daysFromToday): void
    {
        $this->postJson(route('donor.register.store'), $this->registration([
            'birth_date' => today()->addDays($daysFromToday)->toDateString(),
        ]))->assertUnprocessable()->assertJsonValidationErrors('birth_date');

        $this->assertNoRegistration();
    }

    public static function invalidBirthDates(): array
    {
        return ['today' => [0], 'future date' => [18]];
    }

    #[DataProvider('donorServices')]
    public function test_blood_type_is_required_whenever_donor_service_is_selected(array $services): void
    {
        $this->postJson(route('donor.register.store'), $this->registration([
            'services' => $services,
        ]))->assertUnprocessable()->assertJsonValidationErrors('blood_type');

        $this->assertNoRegistration();
    }

    public static function donorServices(): array
    {
        return ['donor only' => [['donor']], 'donor and patient' => [['donor', 'patient']]];
    }

    #[DataProvider('incompleteAddresses')]
    public function test_registration_requires_a_valid_city_and_barangay(string $address): void
    {
        $this->postJson(route('donor.register.store'), $this->registration([
            'address' => $address,
        ]))->assertUnprocessable()->assertJsonValidationErrors('address');

        $this->assertNoRegistration();
    }

    public static function incompleteAddresses(): array
    {
        return [
            'missing barangay' => ['Manapla, Negros Occidental'],
            'unrecognized barangay' => ['Unknown Barangay, Manapla, Negros Occidental'],
        ];
    }

    public function test_patient_can_register_without_blood_type_middle_name_or_valid_id(): void
    {
        $this->post(route('donor.register.store'), $this->registration())
            ->assertSessionHasNoErrors()->assertRedirect(route('verification.notice'));

        $user = User::where('email', 'registration.validation@example.test')->sole();
        $this->assertSame(['Patient'], $user->getRoleNames()->all());
        $this->assertFalse($user->hasVerifiedEmail());
        $this->assertNull($user->middle_name);
        $this->assertDatabaseHas('patient_profiles', ['user_id' => $user->id]);
        $this->assertDatabaseCount('donors', 0);
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_failed_submission_preserves_no_services_selected_and_limits_birth_date(): void
    {
        $payload = $this->registration();
        unset($payload['services']);

        $this->from(route('donor.register'))->post(route('donor.register.store'), $payload)
            ->assertRedirect(route('donor.register'))->assertSessionHasErrors('services');

        $response = $this->get(route('donor.register'))->assertOk()
            ->assertSee('id="registrationForm"', false)
            ->assertSee('id="servicesError"', false);
        $html = $response->getContent();

        foreach (['registrationServiceDonor', 'registrationServicePatient'] as $id) {
            $input = $this->inputWithId($html, $id);
            $this->assertDoesNotMatchRegularExpression('/\schecked(?:\s|=|>)/', $input);
        }
        $this->assertStringContainsString('max="'.today()->subDay()->toDateString().'"', $this->inputWithId($html, 'birthDate'));
        $this->assertNoRegistration();
    }

    private function inputWithId(string $html, string $id): string
    {
        $this->assertSame(1, preg_match('/<input\b[^>]*\bid="'.preg_quote($id, '/').'"[^>]*>/', $html, $matches));

        return $matches[0];
    }

    private function assertNoRegistration(): void
    {
        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('donors', 0);
        $this->assertDatabaseCount('patient_profiles', 0);
        $this->assertGuest('web');
        Notification::assertNothingSent();
    }

    private function registration(array $overrides = []): array
    {
        return [
            'services' => ['patient'], 'first_name' => 'Registration', 'last_name' => 'Tester',
            'birth_date' => '1995-01-01', 'sex' => 'female', 'contact_number' => '09171230001',
            'email' => 'registration.validation@example.test', 'address' => 'Barangay I (Pob.), Manapla, Negros Occidental',
            'password' => 'StrongPass123!', 'password_confirmation' => 'StrongPass123!', ...$overrides,
        ];
    }
}
