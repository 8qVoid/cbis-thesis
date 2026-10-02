# Local security and request-tampering audit

Audit date: October 1, 2026 (Asia/Taipei).

Status: the original assessment below records the pre-fix findings. The three medium-priority findings have now been addressed; see [the before-and-after fix report](security-fixes-2026-10-01.md). The two lower-priority findings remain outside the requested fix scope.

## Scope and evidence

Reviewed authentication, registration, profile changes, role/facility middleware, reservations and private documents, events, donations, releases, reports, notifications, uploads, Blade rendering, map HTML construction, and the donation Livewire component.

Behavioral checks used HTTP requests through Laravel's application kernel and Livewire's test harness, a separate SQLite `:memory:` database, freshly created accounts, fake document storage, and fake notifications. No existing MySQL records were used or changed. Permission seeding creates a demo QAO account, but the new security-control suite removes it before exercising newly created accounts; the separate probes use factory-created accounts rather than logging in with demo credentials.

`http://cbis-thesis.test` refused connections during this audit. Browser DevTools interactions, live Apache response headers, and deployment configuration were therefore not verified. These findings demonstrate server behavior with tampered requests, rather than claiming a completed browser or external-network penetration test. Dependency vulnerability scanning and infrastructure penetration testing are outside this assessment.

Inspect Element can always change local HTML, displayed text, hidden fields, disabled buttons, and JavaScript values. The important boundary is whether the server accepts an unauthorized or invalid request. Cosmetic browser edits alone do not alter stored records or grant permissions.

## Original confirmed findings

| Priority | Finding | Access required | Reproduced result |
| --- | --- | --- | --- |
| Medium | Donation update lacks the creation volume limit | Blood Bank Staff with donation permissions | Updating a pending donation to `volume_ml=450000` and `status=verified` creates 1,000 inventory units |
| Medium | Login rate limit can be bypassed using HTML aliases | Unauthenticated | A blocked email login gets a fresh limit when submitted inside HTML tags that validation subsequently removes |
| Medium | Existing sessions survive password reset and change | An already authenticated session | Independently persisted earlier sessions can still access `/account` after successful password reset/change |
| Low | Malformed service-selection input causes a server error | Donor or Patient account | `services=donor` as a scalar returns HTTP 500 |
| Low / UI integrity | Read-only donation number accepts a client override | Blood Bank Staff with donation permissions | Both a Livewire property override and a direct POST save an arbitrary unique donation number |

These findings have different implications. The login-limit issue increases the number of password guesses; it does not authenticate a request without valid credentials. The session issue allows someone who already has a session to retain access after account recovery. Inventory inflation is a privileged staff integrity problem: patients and donors were not able to reach that endpoint. The donation-number finding is also a misleading create-form promise; the existing edit form deliberately allows donation-number changes, so this is not demonstrated privilege escalation.

### Donation volume / inventory inflation

`app/Http/Requests/UpdateDonationRecordRequest.php:17` validates `volume_ml` with `integer|min:1`, while creation validates `max:5000`. Before inventory has been generated, a staff user can update a pending donation to an oversized volume and verified status. `app/Listeners/IncreaseInventoryFromDonation.php:23` derives stock by dividing the accepted volume by 450.

The probe created a pending 450 mL donation, submitted a forged PUT with 450,000 mL and verified status, and observed a saved donation plus 1,000 inventory units. Apply consistent limits to updates and all stock-generation entry points, and have the workflow owner confirm the appropriate donation volume/unit policy. Include boundary checks for accepted and rejected volumes.

### Login throttling aliases

`app/Providers/AppServiceProvider.php:27-30` chooses the login limit bucket using the submitted login string. `app/Http/Requests/BaseFormRequest.php:85` removes HTML tags later, before credentials are checked.

The probe exhausted the ordinary email's five attempts, confirmed HTTP 429 on the sixth, then submitted eight distinct HTML wrappers with wrong passwords without receiving that exhausted limit. A fresh `<b>email</b>` alias with the correct password logged into the same account. Normalize the limiter identity using the same canonicalization as authentication, and consider an additional IP-wide limit to bound attempts spread over different identifiers. Whitespace-only aliases were not used: global middleware trims those before throttling.

### Sessions after password recovery

The password reset handler changes the password and remember token (`app/Http/Controllers/Auth/ForgotPasswordController.php:55-59`). The change handler changes only the password (`app/Http/Controllers/Auth/PasswordController.php:32-33`). The registered web middleware does not include session password-hash verification.

The probes used separate persisted sessions, cleared cached authentication/request state between simulated browsers, completed password reset or change, and replayed an earlier session. `/account` still returned HTTP 200. Add effective session revocation or password-hash verification for both supported guards, rotate remember tokens appropriately, and define whether password changes preserve only the current session. Recheck both existing sessions and remember-me cookies.

### Malformed service input

`app/Http/Controllers/AccountProfileController.php:93` passes the raw `services` input into `in_array()` while assembling validation. A scalar string raises a type error before the array validation can return a normal 422 response. Validate or safely type-check the input before using it in the conditional rule. Debug mode can reveal extra error details if the same configuration is exposed publicly.

### Read-only donation number

`resources/views/livewire/donation-records/create-donation-record.blade.php:102` marks the input read-only and describes it as uneditable. `app/Livewire/DonationRecords/CreateDonationRecord.php:48` exposes an unlocked public value, and `save()` persists a client-supplied value after validation. Direct `donation-records.store` requests also accept it.

The probes saved `CLIENT-OVERRIDE-UNIQUE` through Livewire and `CLIENT-POST-NUMBER` through HTTP. If the number must be system-controlled, generate it server-side at save time, reject/ignore client overrides, and align the edit workflow with that policy. If staff are intentionally allowed to choose it, correct the form's promise instead.

## Protections verified

The new `tests/Feature/RequestTamperingSecurityTest.php` contains 12 passing tests and 75 assertions:

- Public registration rejects injected role/account-control fields and staff values in `services`.
- A newly registered Patient account cannot directly invoke staff pages, staff creation, inventory/release/event mutations, or report requests.
- Profile/service edits do not change the account's role, facility, owner, activity status, or password through injected fields.
- Patient submissions cannot forge their reservation's status, owner, reviewer, review time, or reference.
- Reservation quantity and main-chapter constraints are checked by the server.
- A patient cannot view another patient's reservation or approve their own request.
- Private document IDs must belong to the selected reservation; other patients and QAO cannot download them.
- Script uploads are rejected despite a client-side file-picker override.
- Forged event registration fields do not select another donor, facility, or attended status.
- Direct requests cannot join an unapproved event.
- Review-note markup is HTML-escaped in the rendered patient view.
- Cross-site state-changing requests without a valid CSRF token receive 419. This test explicitly enables the real middleware path because ordinary Laravel test requests skip CSRF.

The existing suite additionally checks role surfaces, report-request ownership, main-chapter scope, reservation transitions, and release stock rechecks. Source review found escaping in public map HTML and private storage/authorization for identity and reservation documents. This is bounded evidence for the paths tested, not proof that all possible attacks are blocked.

## Lower-confidence or dormant observations

- Global CSP and frame-protection headers are not configured in application code. Actual deployment headers remain unverified while the local site is offline.
- Facility application proofs are written to the public storage disk, but the submission routes are absent and no proof files were observed during review. Treat this as a dormant risk to address before enabling that workflow, rather than a reproduced active leak.
- The local environment uses `APP_ENV=local`, `APP_DEBUG=true`, and HTTP. Production error handling, TLS, and cookie settings require a separate deployment check.

## Validation and reproduction

Before the fixes, the full PHPUnit run passed **85 tests and 777 assertions**. The separate finding-reproduction suite passed **7 tests and 79 assertions**, recording the insecure behavior described above. The original result logs preserve that baseline. The probe sources have since been updated to assert that the three medium-priority attacks are blocked, while retaining the two lower-priority reproductions; current results are described in the fix report. The added control test passed the repository's Pint style check.

The available PHP 8.3 installation lacked SQLite and fileinfo in its default CLI configuration. They were enabled per process without editing PHP configuration. Initial runs failed because of those missing extensions; the final results above are from the corrected runtime.

From the repository root in PowerShell:

```powershell
$auditPhp = 'C:\laragon\bin\php\php-8.3.33-nts-Win32-vs16-x64\php.exe'
& $auditPhp -d extension=pdo_sqlite -d extension=sqlite3 -d extension=fileinfo vendor/phpunit/phpunit/phpunit --colors=never
& $auditPhp -d extension=pdo_sqlite -d extension=sqlite3 -d extension=fileinfo vendor/phpunit/phpunit/phpunit --colors=never output/security-audit
```

Use the repository's `phpunit.xml`, which selects the in-memory database, and do not override it with a real database connection. Probe sources are `output/security-audit/AuthProbesTest.php` and `output/security-audit/OperationalProbesTest.php`. Result logs are `output/security-audit/controls.xml`, `output/security-audit/full-suite.xml`, and `output/security-audit/reproductions.xml`.
