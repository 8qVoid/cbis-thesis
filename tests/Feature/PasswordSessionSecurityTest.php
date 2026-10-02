<?php

namespace Tests\Feature;

use App\Models\Donor;
use App\Models\PatientProfile;
use App\Models\User;
use App\Support\PasswordSessions;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Session\ExistenceAwareInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PasswordSessionSecurityTest extends TestCase
{
    use RefreshDatabase;

    public static function guardsAndDrivers(): array
    {
        return [
            'web with array sessions' => ['web', 'array'],
            'donor with array sessions' => ['donor', 'array'],
            'web with database sessions' => ['web', 'database'],
            'donor with database sessions' => ['donor', 'database'],
        ];
    }

    #[DataProvider('guardsAndDrivers')]
    public function test_reset_revokes_persisted_and_legacy_sessions_and_remember_cookies(string $guard, string $driver): void
    {
        $user = $this->account($guard, $driver);
        [$sessionId, $rememberCookie] = $this->login($guard, $user, remember: true);
        $legacySessionId = $this->legacyCopy($guard, $sessionId);
        $oldRememberToken = $user->fresh()->remember_token;
        $broker = $guard === 'web' ? 'users' : 'donors';
        $token = Password::broker($broker)->createToken($user);

        $this->switchBrowserSession(Str::random(40));
        $this->post(route('password.reset.update'), [
            'account_type' => $guard === 'web' ? 'staff' : 'donor',
            'email' => $user->email, 'token' => $token,
            'password' => 'changed-password', 'password_confirmation' => 'changed-password',
        ])->assertRedirect(route('login'));
        $this->assertGuest($guard);
        $this->assertTrue(Hash::check('changed-password', $user->fresh()->password));
        $this->assertNotSame($oldRememberToken, $user->fresh()->remember_token);
        $this->get(route('password.change'))->assertRedirect(route('login'));

        foreach ([$sessionId, $legacySessionId] as $oldSessionId) {
            $this->switchBrowserSession($oldSessionId);
            $this->get(route('password.change'))->assertRedirect(route('login'));
            $this->assertGuest($guard);
        }

        $this->switchBrowserSession(Str::random(40), $guard, $rememberCookie);
        $this->get(route('password.change'))->assertRedirect(route('login'));
        $this->assertGuest($guard);

        $this->switchBrowserSession(Str::random(40));
        $this->login($guard, $user->fresh(), password: 'changed-password');
        $this->get(route('password.change'))->assertOk();
    }

    #[DataProvider('guardsAndDrivers')]
    public function test_password_change_preserves_only_current_session_and_refreshes_its_remember_cookie(string $guard, string $driver): void
    {
        $user = $this->account($guard, $driver);
        [$otherSessionId, $oldRememberCookie] = $this->login($guard, $user, remember: true);
        $legacySessionId = $this->legacyCopy($guard, $otherSessionId);

        $this->switchBrowserSession(Str::random(40));
        [$currentSessionId, $currentRememberCookie] = $this->login($guard, $user->fresh(), remember: true);
        $oldRememberToken = $user->fresh()->remember_token;
        $this->switchBrowserSession($currentSessionId, $guard, $currentRememberCookie);
        $changed = $this->put(route('password.update'), [
            'current_password' => 'initial-password',
            'password' => 'changed-password', 'password_confirmation' => 'changed-password',
        ])->assertSessionHasNoErrors()->assertRedirect(route($guard === 'web' ? 'account.dashboard' : 'donor.portal.profile'));

        $renewedSessionId = $this->app['session.store']->getId();
        $renewedRememberCookie = $changed->getCookie(Auth::guard($guard)->getRecallerName())?->getValue();
        $this->assertNotSame($currentSessionId, $renewedSessionId);
        $this->assertNotEmpty($renewedRememberCookie);
        $this->assertNotSame($oldRememberCookie, $renewedRememberCookie);
        $this->assertNotSame($oldRememberToken, $user->fresh()->remember_token);
        $this->assertTrue(Hash::check('changed-password', $user->fresh()->password));

        $this->switchBrowserSession($renewedSessionId);
        $this->get(route('password.change'))->assertOk();
        $this->assertAuthenticatedAs($user->fresh(), $guard);

        foreach ([$otherSessionId, $currentSessionId, $legacySessionId] as $oldSessionId) {
            $this->switchBrowserSession($oldSessionId);
            $this->get(route('password.change'))->assertRedirect(route('login'));
            $this->assertGuest($guard);
        }

        $this->switchBrowserSession(Str::random(40), $guard, $oldRememberCookie);
        $this->get(route('password.change'))->assertRedirect(route('login'));
        $this->assertGuest($guard);

        $this->switchBrowserSession(Str::random(40), $guard, $renewedRememberCookie);
        $this->get(route('password.change'))->assertOk();
        $this->assertAuthenticatedAs($user->fresh(), $guard);
        $this->assertNotEmpty($this->app['session.store']->get(PasswordSessions::key($guard)));
    }

    #[DataProvider('guardsAndDrivers')]
    public function test_failed_change_preserves_current_and_other_sessions(string $guard, string $driver): void
    {
        $user = $this->account($guard, $driver);
        [$otherSessionId] = $this->login($guard, $user, remember: true);
        $this->switchBrowserSession(Str::random(40));
        [$currentSessionId] = $this->login($guard, $user->fresh());
        $oldPasswordHash = $user->fresh()->password;
        $oldRememberToken = $user->fresh()->remember_token;

        $this->put(route('password.update'), [
            'current_password' => 'incorrect-password',
            'password' => 'changed-password', 'password_confirmation' => 'changed-password',
        ])->assertSessionHasErrors('current_password');
        $this->assertSame($currentSessionId, $this->app['session.store']->getId());
        $this->assertSame($oldPasswordHash, $user->fresh()->password);
        $this->assertSame($oldRememberToken, $user->fresh()->remember_token);

        foreach ([$currentSessionId, $otherSessionId] as $sessionId) {
            $this->switchBrowserSession($sessionId);
            $this->get(route('password.change'))->assertOk();
            $this->assertAuthenticatedAs($user->fresh(), $guard);
        }
    }

    #[DataProvider('guardsAndDrivers')]
    public function test_legacy_sessions_without_password_fingerprints_require_a_new_login(string $guard, string $driver): void
    {
        $user = $this->account($guard, $driver);
        [$sessionId] = $this->login($guard, $user);
        $legacySessionId = $this->legacyCopy($guard, $sessionId);
        $this->switchBrowserSession($legacySessionId);

        $this->get(route('password.change'))->assertRedirect(route('login'));
        $this->assertGuest($guard);
        $this->assertTrue(Hash::check('initial-password', $user->fresh()->password));
    }

    private function account(string $guard, string $driver): User|Donor
    {
        config(['session.driver' => $driver]);
        $this->app['session']->forgetDrivers();
        $this->app->forgetInstance('session.store');
        Auth::forgetGuards();

        if ($guard === 'web') {
            $this->seed(RolePermissionSeeder::class);
            $user = User::factory()->create([
                'email' => 'password-session@example.test', 'password' => 'initial-password',
                'is_active' => true, 'facility_id' => null,
            ]);
            $user->assignRole('Patient');
            PatientProfile::create(['user_id' => $user->id]);

            return $user;
        }

        return Donor::create([
            'email' => 'legacy-password-session@example.test', 'password' => 'initial-password',
            'first_name' => 'Session', 'last_name' => 'Donor', 'birth_date' => '1995-01-01',
            'sex' => 'male', 'blood_type' => 'O+', 'contact_number' => '+639171111111',
            'is_online_registered' => true,
        ]);
    }

    private function login(string $guard, Authenticatable $user, bool $remember = false, string $password = 'initial-password'): array
    {
        $response = $this->post(route('login.store'), [
            'login' => $user->email, 'password' => $password, 'remember' => $remember ? '1' : '0',
        ])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertAuthenticatedAs($user, $guard);

        $session = $this->app['session.store'];
        $sessionId = $session->getId();
        $fingerprint = $session->get(PasswordSessions::key($guard));
        $this->assertNotEmpty($session->getHandler()->read($sessionId));
        $this->assertSame(PasswordSessions::fingerprint($guard, $user), $fingerprint);
        $this->assertNotSame($user->getAuthPassword(), $fingerprint);
        $rememberCookie = $response->getCookie(Auth::guard($guard)->getRecallerName())?->getValue();
        if ($remember) {
            $this->assertNotEmpty($rememberCookie);
        }
        $this->withCookie(config('session.cookie'), $sessionId);

        return [$sessionId, $rememberCookie];
    }

    private function legacyCopy(string $guard, string $sessionId): string
    {
        $handler = $this->app['session.store']->getHandler();
        $payload = json_decode($handler->read($sessionId), true, flags: JSON_THROW_ON_ERROR);
        unset($payload[PasswordSessions::key($guard)]);
        $legacySessionId = Str::random(40);
        if ($handler instanceof ExistenceAwareInterface) {
            $handler->setExists(false);
        }
        $handler->write($legacySessionId, json_encode($payload, JSON_THROW_ON_ERROR));

        return $legacySessionId;
    }

    private function switchBrowserSession(string $sessionId, ?string $guard = null, ?string $rememberCookie = null): void
    {
        Auth::forgetGuards();
        Auth::shouldUse('web');
        $session = $this->app['session.store'];
        $session->flush();
        if ($session->getHandler() instanceof ExistenceAwareInterface) {
            $session->getHandler()->setExists(false);
        }
        $this->app['cookie']->unqueue(Auth::guard('web')->getRecallerName());
        $this->app['cookie']->unqueue(Auth::guard('donor')->getRecallerName());
        $this->defaultCookies = [config('session.cookie') => $sessionId];
        if ($guard && $rememberCookie) {
            $this->withCookie(Auth::guard($guard)->getRecallerName(), $rememberCookie);
        }
    }
}
