# Local setup

The application is served by Laragon at http://cbis-thesis.test.

- PHP: Laragon PHP 8.5.10 with MySQL, SQLite, GD, ZIP, and the required XML extensions.
- Database: `centralized_blood_inventory` on local MySQL.
- Local configuration and application key: `.env` (not committed).
- Email: the `log` mailer writes messages to `storage/logs/laravel.log`. Real delivery requires SMTP credentials in `.env`; do not commit them.
- Frontend assets: compiled into `public/build`.
- Frontend build used Node 24.19.0 from the bundled Codex runtime; Laragon's Node 18 is too old for this project's Vite version. Use Node 24 for future builds.

After restarting Windows, start Apache and MySQL in Laragon, then right-click `start-local.ps1` and choose **Run with PowerShell** to start the queue and scheduler workers. The script avoids starting duplicate workers. No separate web or Vite server is needed for the compiled app.

Default local demonstration accounts all use the password `password`:

| Role | Email |
| --- | --- |
| Super Administrator | admin@cbis.local |
| Facility Facilitator | facility.admin@cbis.local |
| Medical Staff / Nurse | medical.staff@cbis.local |

These credentials and local settings are for development only. Change passwords and configure production settings before any public deployment.

## Verification

Dependencies installed, frontend build completed, MySQL migrations and seeders completed, public storage linked, and administrator login/dashboard verified in Chrome. Queue and scheduler workers are running.

The default SQLite test configuration cannot run the MySQL-specific migrations. Running the existing suite against a separate `cbis_thesis_test` MySQL database produced **14 passed, 8 failed** (88 assertions). The remaining application test failures are recorded in `storage/logs/setup-tests.log`; setup did not modify application behavior to address them. The application database remains separate from the test database.
