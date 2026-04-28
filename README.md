# Centralized Web-Based Real-Time Blood Inventory System with Donation and Bloodletting Records

Laravel 13 thesis system implementing centralized, multi-facility blood inventory management with real-time transaction updates.

## Scope Alignment
- Centralized single-platform architecture
- One database for many facilities
- Facility applications reviewed by the Red Cross super administrator
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
11. Mapping module with event and facility pins
12. Public portal and donor event registration
13. Required event/location photo uploads

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
7. Ensure public uploaded files are available:
   - `php artisan storage:link`
8. Run queue worker:
   - `php artisan queue:work`
9. Run scheduler:
   - `php artisan schedule:work`
10. Serve app:
   - `php artisan serve`

## Default Users (Seeder)
- Super Administrator: `admin@cbis.local` / `password`
- Facility Facilitator: `facility.admin@cbis.local` / `password`
- Medical Staff / Nurse: `medical.staff@cbis.local` / `password`

## Role Boundaries

### Super Administrator
The Red Cross main administrator for central oversight and approval.

Can access:
- Dashboard
- Donor records
- Donation records
- Bloodletting records
- Blood inventory
- Blood releases
- Event schedules
- Blood bank locations
- Reports
- Notifications
- Facilities
- Staff accounts
- Facility applications

Can do:
- Approve or reject facility applications
- Manage approved facilities
- Manage central facility and location records
- View records across all facilities
- Generate reports
- Monitor facility application alerts
- View staff accounts across facilities

Cannot do:
- Create donor records for a facility
- Create donation records as a facility
- Create bloodletting records as a facility
- Create or edit inventory as a facility
- Create blood release records as a facility
- Create event schedules as a facility
- Create facility staff accounts
- Receive low-stock alerts

### Facility Facilitator
The approved facility account for front desk and facility operations.

Can access:
- Dashboard
- Donor records
- Donation records
- Bloodletting records
- Event schedules
- Staff accounts
- Notifications

Can do:
- Manage donors under their assigned facility
- Record donation transactions
- Manage bloodletting records
- Create and manage donation events or schedules with required uploaded event photos
- Create facility staff accounts, such as Medical Staff / Nurse
- View dashboard summaries for their assigned facility
- Receive low-stock alerts for their assigned facility

Cannot access:
- Facility approval
- All-facility management
- Inventory management
- Blood releases
- Reports
- Locations
- Super administrator controls

### Medical Staff / Nurse
The facility inventory user.

Can access:
- Dashboard
- Blood inventory
- Notifications
- Reports

Can do:
- View current stock
- Add or update inventory records
- Monitor low-stock alerts
- Manage inventory for their assigned facility only
- Download reports for their assigned facility

Cannot access:
- Donor records
- Donation records
- Bloodletting records
- Event schedules
- Staff management
- Facility approval
- Other facilities' data

### Public User
The public-facing portal user role for non-staff access.

Can access:
- Public event list and combined map
- Blood availability
- Facility application form
- Donor portal profile after donor login

Can do:
- View upcoming public blood donation and bloodletting events
- Register for public events when registration is still open
- View red facility pins and blue event/activity pins on one map

## Scheduled Commands
- `inventory:flag-expired`
- `inventory:notify-low-stock`

## Low Stock Alerts (In-App + Email)
- In-app low-stock alerts are available for Facility Facilitators and Medical Staff / Nurse users through the navbar notification center and `/notifications`.
- Email alerts are sent through `LowStockAlert` when inventory enters low-stock state.
- Required runtime processes:
  - `php artisan schedule:work`
  - `php artisan queue:work`
- Configure SMTP in `.env`:
  - `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`
  - `MAIL_FROM_ADDRESS`, `MAIL_FROM_NAME`

## Facility Application Alerts
- When a public facility application is submitted, the Super Administrator receives:
  - one in-app dashboard/navbar notification
  - one email notification through `FacilityApplicationSubmitted`
- The Super Administrator notification center is dedicated to facility application alerts, not low-stock alerts.

## Event Notifications
- When a public event is created with status `planned` or `ongoing`, verified online registered donors receive an email notification.
- Donors are notified only when they have an email, an online portal account, and are marked eligible.
- The event email includes the event title, type, facility, date, time, venue, contact details, and a link to the public event map.

## Event and Facility Map
- The public map shows both marker types on one Leaflet/OpenStreetMap map:
  - Blue pins: public events and activities
  - Red pins: blood bank or hospital facility locations
- Map controls allow users to show or hide events and facilities.
- Event map popups show the uploaded event photo, event details, and a registration action.
- Facility map popups show the uploaded location photo and facility contact information.
- Public events stay visible while they are public, dated today or later, and have status `planned` or `ongoing`.
- A facility can remove an event from the public map by editing the event status to `completed` or `cancelled`, or by setting `Show to Public` to `No`.

## Required Photo Uploads
- Creating an event schedule requires an uploaded event photo.
- Creating a blood bank location requires an uploaded location photo.
- Editing an existing event or location keeps the current photo unless a replacement image is uploaded.
- Accepted formats: JPG, JPEG, PNG, and WebP.
- Maximum upload size: 4 MB.
- Uploaded files are stored on the Laravel public disk:
  - `storage/app/public/event-photos`
  - `storage/app/public/location-photos`

## Thesis Constraints Observed
Excluded features (as required):
- AI/ML
- third-party hospital or national blood bank integration
- forecasting
- mobile app
- payment or chat systems

## Documentation
- Architecture and phase breakdown: `docs/thesis-system-architecture.md`
