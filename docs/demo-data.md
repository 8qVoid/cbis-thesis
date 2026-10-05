# Render demo data

The optional `demo:populate` command creates connected demonstration records in the normal application tables. Accounts, donor profiles, screenings, event registrations, donations, inventory, requests, releases, and database notifications can be viewed through the existing pages.

People and activities are fictional. Activity titles use `[DEMO]`, descriptions identify test data, and account emails use the reserved `example.test` domain. Demo account passwords are random and are not published. The scenario does not send emails to these accounts.

## Enable on the demo environment

Set `DEMO_DATA_ENABLED=true` on the intended Render service and deploy. The Docker startup script automatically runs the command after migrations and the baseline staff seeders. To run it manually on an already configured demo environment:

```sh
php artisan demo:populate
```

The flag defaults to `false`, and the command refuses to run while it is disabled. A normal local clone or pull therefore does not populate the local database. Keep the flag off in the local `.env` unless you deliberately want demo records there.

Rerunning the scenario reuses existing records and preserves edited balances, statuses, profile details, notification read states, and soft deletions. Its lookup keys are account emails, activity titles, donation numbers, and request references; changing those identifiers can make them appear to be new records on a later run.

The current Render setup uses a SQLite file inside the service instance. Demo data is recreated when Render starts a fresh instance; records entered manually on that instance are not a persistent backup.
