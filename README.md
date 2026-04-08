# Centralized Web-Based Real-Time Blood Inventory System with Donation and Bloodletting Records

Laravel 13 thesis system implementing centralized, multi-facility blood inventory management with real-time transaction updates.

## Scope Alignment
- Centralized single-platform architecture
- One database for many facilities
- Admin-created facilities only (no public facility signup)
- Real-time inventory updates via events/listeners
- Role-based access per approved thesis roles

## Tech Stack
- Laravel 13, PHP 8.3+
- MySQL
- Blade + Bootstrap
- Spatie Laravel Permission (RBAC)
- Laravel Notifications + Queues
- Leaflet + OpenStreetMap
- DomPDF
- Laravel Excel

## Core Modules
1. Authentication and RBAC
2. Role dashboards
3. Facility management
4. Donor management
5. Blood donation records
6. Bloodletting records
7. Blood inventory
8. Blood release/usage
9. Notifications
10. Reports (PDF/Excel)
11. Mapping module
12. Public portal

## Installation
1. Clone project
2. Create `.env` from `.env.example`
3. Configure MySQL connection
4. Install dependencies:
   - `php C:\laragon\bin\composer\composer.phar install --ignore-platform-req=php --ignore-platform-req=ext-zip`
5. Generate app key:
   - `php artisan key:generate`
6. Run migrations and seeders:
   - `php artisan migrate:fresh --seed`
7. Run queue worker:
   - `php artisan queue:work`
8. Run scheduler:
   - `php artisan schedule:work`
9. Serve app:
   - `php artisan serve`

## Default Users (Seeder)
- Central Admin: `admin@cbis.local` / `password`
- Facility Admin: `facility.admin@cbis.local` / `password`
- Medical Technologist: `medtech@cbis.local` / `password`

## Scheduled Commands
- `inventory:flag-expired`
- `inventory:notify-low-stock`

## Low Stock Alerts (In-App + Email)
- In-app alerts are available for staff (central admin and facility admin) via navbar `Alerts` and `/notifications`.
- Email alerts are sent through `LowStockAlert` when inventory enters low-stock state.
- Required runtime processes:
  - `php artisan schedule:work`
  - `php artisan queue:work`
- Configure SMTP in `.env`:
  - `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`
  - `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME`

## Thesis Constraints Observed
Excluded features (as required):
- AI/ML
- third-party hospital or national blood bank integration
- forecasting
- mobile app
- payment or chat systems

## Documentation
- Architecture and phase breakdown: `docs/thesis-system-architecture.md`
