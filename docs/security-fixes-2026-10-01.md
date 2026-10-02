# Security fixes: before and after

Date: October 1, 2026 (Asia/Taipei).

The three medium-priority findings from the local audit have been fixed in the application. All verification uses isolated test data; existing MySQL records were not changed.

| Problem | Before | After |
| --- | --- | --- |
| Login attempt limit | HTML wrappers gave the same account a fresh limit because credentials were cleaned after throttling. | Limiting and authentication use the same cleaned identity. HTML, whitespace, and email-case aliases share the five-per-minute identity/IP limit. An additional 30-per-minute IP limit bounds attempts spread over different logins. |
| Sessions after password recovery | Earlier sessions could still use the account after password reset or change. | Sessions carry a password fingerprint. A reset rejects all previous sessions and remember cookies. A password change renews the current browser and rejects the other sessions and old cookies. |
| Donation update / inventory inflation | A pending donation could be updated to 450,000 mL and verified, generating 1,000 inventory units. | Create, update, Livewire, and stock-generation paths enforce the existing 1–5,000 mL limit. The same request is rejected, leaves the pending donation unchanged, and creates no stock. |

## Implementation details

`app/Support/LoginIdentity.php` provides the shared cleanup and limiter key. Authentication preserves the database's existing email-case matching; case folding is used for the limiter key. Malformed login values remain validation errors. Valid staff, public-account, and legacy donor logins continue to work.

`app/Listeners/StorePasswordSessionFingerprint.php` records an HMAC fingerprint at login, including registration and remembered-cookie restoration. `app/Http/Middleware/EnsurePasswordSession.php` checks both `web` and `donor` guards after the session loads and before route authentication. Stale browser sessions are invalidated without changing the account's newly valid remember token. Remembered-cookie restoration also verifies its password fingerprint.

Successful password changes rotate the remember token and renew only the current session and, when present, its remember cookie. Failed password changes leave both the current and other sessions usable. Public users return to their account dashboard after changing a password. Password reset rotates the remember token and requires a fresh login. No database migration is required.

`app/Support/DonationVolumePolicy.php` supplies shared validation. The inventory listener independently validates volume so bypassing form validation cannot mint oversized stock. Donation saves, stock processing, event attendance, and audit writes are transactional; processing errors roll back partial changes. Update processing locks the donation while checking whether it already generated inventory.

## Rollout behavior

Sessions created before this update lack the new fingerprint and require a fresh login once. They are deliberately rejected instead of being initialized with the new password after a reset. New sessions use the fingerprint automatically. Apply the code through the normal deployment process and rebuild any cached application/events configuration if the deployment uses it.

This fixes the three requested findings. The lower-priority editable donation number and malformed service-selection error from the original audit remain recorded separately.

## Verification

Permanent regression tests are in `tests/Feature/LoginThrottleSecurityTest.php`, `tests/Feature/PasswordSessionSecurityTest.php`, and `tests/Feature/DonationVolumeSecurityTest.php`. They exercise the original attack requests, valid and invalid boundaries, independent browser sessions, both account guards, array/database session storage, legacy sessions, old/new remember cookies, and transaction rollback.

The existing reservation/document workflow test now switches users through a real session login when reusing a simulated browser, so the persisted identity and fingerprint agree. Its document access and status-transition assertions remain intact.

The final full PHPUnit suite passed **122 tests and 1,514 assertions**. The updated audit probes passed **7 tests and 66 assertions**. The checks for the three medium-priority findings now expect rejection; the lower-priority probes still record their outstanding behavior. Pint passed for all changed application/test files, and `git diff --check` was clean.

Commands use PHP 8.3 with SQLite and fileinfo enabled per process:

```powershell
$securityPhp = 'C:\laragon\bin\php\php-8.3.33-nts-Win32-vs16-x64\php.exe'
& $securityPhp -d extension=pdo_sqlite -d extension=sqlite3 -d extension=fileinfo vendor/phpunit/phpunit/phpunit --colors=never
& $securityPhp -d extension=pdo_sqlite -d extension=sqlite3 -d extension=fileinfo vendor/phpunit/phpunit/phpunit --colors=never output/security-audit
```

Result logs: `output/security-audit/after-fixes-full-suite.xml` and `output/security-audit/after-fixes-probes.xml`. The scope remains local application/kernel and Livewire verification; live browser/deployment checks were not part of these fixes.
