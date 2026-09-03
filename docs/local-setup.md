# Local setup and troubleshooting

Follow the [README installation steps](../README.md#local-installation) for cloning, database setup, build commands, and demonstration accounts. Use PHP 8.3 or 8.4, Composer 2, MySQL, and Node.js 24.

## Using Laragon

1. Place the clone in `C:\laragon\www\cbis-thesis` and start MySQL in Laragon.
2. Check `php -v` and `node -v` in your terminal. Laragon's older Node 18 cannot build this project's Vite assets.
3. Complete the README installation instructions, including `.env`, database creation, migrations, seeders, storage link, and asset build.
4. Run `php artisan serve --host=localhost --port=8000` and open <http://localhost:8000>. Apache is not required for this mode.
5. Run `php artisan queue:work --tries=3 --timeout=90` and `php artisan schedule:work` in separate terminals.

Alternatively, Laragon's Apache virtual host can serve `http://cbis-thesis.test` with its document root pointing to the repository's `public` directory. Set `APP_URL` to that address and run `php artisan config:clear`. Use one hostname consistently: sessions and cookies are separate between the two addresses.

The optional `start-local.ps1` helper starts only queue/scheduler workers, not the localhost web server. It contains a machine-specific PHP path; update that path for your installation before using it. The manual commands above are portable.

## Common problems

| Problem | What to check |
| --- | --- |
| Connection refused | Start MySQL and the Artisan web server. Check whether port 8000 is already occupied. |
| Database connection error | Create the database and check `.env` credentials; then run `php artisan config:clear`. |
| Missing table | Run `php artisan migrate --seed` on a fresh installation. Never use `migrate:fresh` on data you need. |
| Missing styles / Vite manifest | Run `npm ci` and `npm run build` using Node 24. |
| Composer extension error | Enable the extension in the CLI PHP's `php.ini` (`php --ini` identifies it). Use `composer check-platform-reqs` to diagnose; do not bypass platform checks. |
| Missing uploaded event image | Run `php artisan storage:link`. On Windows, symlinks may require Developer Mode or an administrator terminal. |
| Email not delivered | With `MAIL_MAILER=log`, read `storage/logs/laravel.log`. For actual delivery configure SMTP; keep the queue worker running. |
| Inventory alerts do not run | Keep the scheduler and queue worker running. Expiry checks run hourly; low-stock checks run every two hours. |
| 403 access denied | Sign in with the correct role. QAO cannot process reservations; BBS cannot export; Facilitators cannot access inventory. BBS must belong to Bacolod main. |
| Chrome blocks a report download | Check Chrome's download/security message and extensions. Do not disable security globally; diagnose the specific block. |

## Data and security

- `.env`, database data, uploaded documents, `vendor`, and `node_modules` are not distributed by Git. Each clone needs setup; a clone does not copy existing accounts or patient records.
- New installations get only the seeded main chapter and demonstration staff accounts. Donor and Patient accounts are created through public registration.
- Use synthetic data for demonstrations. Default passwords, debug mode, and the local server are not a production deployment configuration.
- Back up existing data before migrations. Keep `APP_KEY` unchanged on an existing installation.

## Verification

Run `php artisan config:clear`, `php artisan test`, and `npm run build`. The tests use in-memory SQLite as configured in `phpunit.xml`, not your MySQL application database. They require PDO SQLite and GD for fake upload tests.
