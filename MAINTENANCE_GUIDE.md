# CBIS local maintenance and debugging

This guide is for an existing local checkout. Read [README.md](README.md) for the full installation and role workflows. Run commands in PowerShell from the repository folder, such as `C:\laragon\www\cbis-thesis`.

## Update an existing copy while keeping its data

```powershell
git status
git pull --ff-only origin main
composer install
php artisan migrate
php artisan optimize:clear
npm ci
npm run build
php artisan storage:link
```

Review any modified files shown by `git status` before pulling. A pull may stop if a local edit conflicts with an incoming change. Keep the local `.env`, database, and `storage` directory. `storage:link` is safe to rerun when the link exists. Restart the app, queue worker, and scheduler after dependency or configuration updates.

Do not run `migrate:fresh`, `db:wipe`, `migrate:refresh`, or a showcase seeder against a database containing work you want to keep. Do not regenerate an existing `APP_KEY`. Run `php artisan db:seed --class=RolePermissionSeeder` only when role or permission changes require it, after checking local role customizations.

## Start the app

Start MySQL in Laragon first. Open separate PowerShell terminals in the project folder for the long-running processes:

```powershell
php artisan serve --host=127.0.0.1 --port=8000
```

```powershell
php artisan queue:work --tries=3 --timeout=90
```

```powershell
php artisan schedule:work
```

Open `http://127.0.0.1:8000/portal`. If `php` is not found, use Laragon's PHP terminal or its PHP executable. Check that `php -m` lists `pdo_mysql`, `fileinfo`, and the other extensions named in README. The scheduler flags expired inventory hourly and checks low stock every two hours. The application also checks expiry at display and release time.

## Local configuration

Set machine-specific values in `.env`, never in `.env.example`. Copy `.env.example` only when `.env` does not yet exist, then set `APP_URL`, `DB_*`, and mail values for that machine. Keep mail credentials private. For a changed `.env`, run `php artisan optimize:clear` and restart workers. For event images and public uploads, run `php artisan storage:link` and verify `storage/app/public` contains the files.

## Diagnose a problem

1. Record the page URL, signed-in role, exact action, expected result, actual result, and time. Capture the error message or screenshot with private data removed.
2. Check `storage/logs/laravel.log` for the matching time. In a browser, inspect the Network request's status and response.
3. Run `php artisan route:list --path=the-path` to check the accepted HTTP method. A 403 usually means permission or facility scope. A 404 may mean the record or route is unavailable. A 405 means the route exists but the HTTP method is unsupported. A 419 usually means an expired session or CSRF token. A 500 requires the Laravel log or exception trace.
4. Run `php artisan optimize:clear`. If a JavaScript or CSS change is missing, run `npm ci`, `npm run build`, and hard refresh with Ctrl+F5.
5. For missing images, check the saved file, `php artisan storage:link`, and the browser's image URL. For mail, check `.env` mail settings and the queue worker. For missing timed updates, check `php artisan schedule:work`.
6. Reproduce on a safe local test account. Do not run destructive commands to troubleshoot a working database.

## Tests and code map

`php artisan test` runs the PHP tests using the separate SQLite configuration in `phpunit.xml`. Verify `pdo_sqlite` is enabled and never point tests at the working MySQL database. `npm run build` checks the frontend build. `git diff --check` checks whitespace in pending changes.

- `routes/web.php` maps pages and actions to controllers.
- `app/Http/Controllers` contains workflows and state changes.
- `app/Http/Requests` validates input and permissions.
- `app/Models` defines records and expiry checks.
- `app/Support/ReservedStock.php` calculates outstanding approved reservations.
- `app/Console/Commands` and `routes/console.php` handle scheduled inventory status and alerts.
- `resources/views` contains page markup. `public/css` and `public/js` contain much of the visible styling and browser behavior.
- `database/migrations` changes the schema. `database/seeders` creates initial or showcase data.
- `tests/Feature` contains workflow and permission checks.

## Asking another assistant for help

Share the repository URL and point it to this guide and README. Include the current commit (`git rev-parse --short HEAD`), the exact failing URL and action, the role, the error text, and a small redacted log excerpt. Ask it to inspect the relevant route, controller, request, view, and tests before suggesting a change. Tell it whether your current database and uploads must be preserved. Do not paste `.env`, passwords, app keys, mail relay secrets, personal documents, or a full database dump into a chat.
