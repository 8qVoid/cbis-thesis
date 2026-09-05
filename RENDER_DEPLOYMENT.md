# Render deployment

This project can be deployed as a Docker web service on Render. Create the web
service in the same region as the `cbis-database` PostgreSQL instance.

## Render settings

| Setting | Value |
| --- | --- |
| Repository | `8qVoid/cbis-thesis` |
| Runtime | Docker |
| Region | Singapore |
| Instance type | Free |

## Environment variables

Set these in the Render web-service Environment tab. Use the database's
**Internal Database URL** as `DB_URL`; do not commit it to the repository.

```text
APP_NAME=CBIS
APP_ENV=production
APP_DEBUG=false
APP_URL=https://YOUR-RENDER-URL.onrender.com
APP_KEY=base64:GENERATE_A_UNIQUE_LARAVEL_KEY
DB_CONNECTION=pgsql
DB_URL=YOUR_RENDER_INTERNAL_DATABASE_URL
DB_SSLMODE=require
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=local
LOW_STOCK_THRESHOLD=5
LOG_CHANNEL=stderr
```

The container applies migrations and seeds the initial demonstration accounts at
startup. Free Render instances use temporary local storage, so uploaded files
are appropriate only for demonstration data.
