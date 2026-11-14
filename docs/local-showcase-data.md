# Local showcase data

This command adds connected synthetic records to an existing local CBIS installation. It does not reset the database. It runs pending migrations and the normal role/facility seeders before adding the records.

```powershell
git pull origin main
php artisan optimize:clear
php artisan showcase:populate
php artisan storage:link
```

Set `APP_ENV=local` in `.env` and start MySQL first. The command refuses to run in production. Running it again leaves existing records and edits in place and does not create duplicate showcase records.

The local scenario includes 100 verified synthetic accounts, donor screenings, 12 plaza events across Bacolod, Silay, Manapla, Victorias, and Cadiz, registrations, about 100 donation and bloodletting records, linked inventory, 40 manual stock batches including low and expired stock, 32 requests in different states, completed releases, and in-app notifications for donors, patients, QAO, and BBS. Counts can be lower when the command is run against a database containing previously deleted showcase records.

The two public login accounts are:

| Role | Email | Password |
| --- | --- | --- |
| Donor | `maria.santos.001@cbis.test` | `CBIS-Local-2026!` |
| Patient | `paolo.gonzales.066@cbis.test` | `CBIS-Local-2026!` |

All other synthetic accounts receive random passwords and do not need to be used for sign-in. Their addresses use the reserved `.test` domain. The seeder sets verification timestamps directly and inserts notifications into the database without sending email. Existing staff accounts keep their current passwords.

The event image uses the photo stored with the repository at `storage/app/public/event-photos/community-blood-drive.jpg`. The storage link makes it visible in the browser.
