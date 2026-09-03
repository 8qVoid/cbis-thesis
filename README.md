# Centralized Blood Inventory System

A Laravel-based thesis system for Philippine Red Cross blood inventory, donation events, donor records, and patient blood reservations across multiple Negros Occidental branches.

Blood inventory and patient reservation processing belong exclusively to the Bacolod main chapter. Branch Facilitators organize activities and propose locations for QAO approval; branches do not store or manage blood units.

## Core Features

- Blood inventory managed only at the Bacolod main chapter
- Four blood components: whole blood, packed red blood cells, platelet concentrate, and fresh frozen plasma
- Donation and bloodletting records
- Patient blood reservations with two separate required uploads: ID and doctor's blood request/prescription
- Event scheduling with QAO approval before map publication
- Low-stock, reservation, and event-review notifications
- Facility-specific summaries and QAO report exports
- Public facility and approved-event map
- One public account that can enable Donor services, Patient services, or both

## Role Access

### Quality Assurance Officer (QAO)

Can:

- Monitor the Bacolod main chapter inventory
- Receive low-stock and new-reservation notifications
- View reservation status without processing the request
- Create activities with automatic approval and public map visibility when current/upcoming and active
- Review and approve or reject Facilitator event map publication
- Manage facilities, blood-bank locations, and staff accounts
- View limited donor information
- Select inventory, donations, releases, and/or reservations for PDF/Excel export, with detailed rows, totals, or both

Cannot:

- Approve, reject, or fulfill patient blood reservations
- Add operational donation or release transactions for a facility
- View private patient reservation documents
- View detailed donor medical information

### Blood Bank Staff (BBS)

Can:

- Work with the Bacolod main chapter's records and inventory; BBS accounts cannot be assigned to branches
- View detailed donor records
- Manage donation, bloodletting, inventory, and blood-release transactions
- Review patient requirements and approve, reject, or fulfill blood reservations
- Monitor their facility's stock and receive low-stock notifications
- Request and view system-generated summaries

Cannot:

- Access another facility's inventory or records
- Generate downloadable Excel/PDF reports
- Create or publish map pins
- Approve event map publication
- Manage system roles or staff accounts

### Event Facilitator

Can:

- Create and manage event schedules for their assigned facility
- Propose an event location and map coordinates
- Receive event approval or rejection notifications
- Maintain their facility's event information

Cannot:

- Publish an event location without QAO approval
- Process patient blood reservations
- Manage blood inventory, releases, donor medical records, or staff accounts
- Generate reports

### Donor

Can:

- Register and sign in using a public account
- Maintain a donor profile
- View public facilities and approved donation events
- Register for or cancel registration to an eligible event
- View their donation-related information

Cannot:

- Access staff dashboards or internal inventory management
- Process reservations, events, or facility records

### Patient

Can:

- Register and sign in using a public account
- Submit a blood reservation to the Bacolod main chapter
- Upload an ID and a doctor's blood request/prescription separately; both are required
- View reservation status and receive status notifications
- Maintain their patient profile

Cannot:

- Approve or process their own reservation
- View internal inventory quantities or other patients' records

## Donor and Patient Accounts

A person uses one account and one personal profile. During registration, they may select Donor, Patient, or both services. Services can be enabled later without creating a duplicate person or losing existing history.

## Reservation Workflow

1. A patient submits a reservation and required documents.
2. QAO and the Bacolod main chapter's Blood Bank Staff receive a notification.
3. Blood Bank Staff review the requirements.
4. The request moves through `Submitted`, `Under Review`, `Approved` or `Rejected`, then `Fulfilled` when released.
5. Approval is blocked when the selected facility lacks sufficient unexpired stock for the requested blood type and component.

## Event Publication Workflow

QAO-created activities are automatically approved. Public map visibility still requires a planned/ongoing event dated today or later. Facilitator-created or Facilitator-edited activities follow this workflow:

1. An Event Facilitator creates an event and proposes its map location.
2. The event remains pending and is not shown publicly.
3. QAO approves or rejects the event.
4. The Facilitator receives the decision notification.
5. An approved public event becomes visible on the map.

## Technology

- Laravel 13; use PHP 8.3 or 8.4 with the locked dependencies
- MySQL
- Blade, Vite, and Tailwind CSS
- Spatie Laravel Permission
- Laravel Notifications
- Leaflet and OpenStreetMap
- Laravel Excel and DomPDF

## Local Installation

Prerequisites: Git, Composer 2, PHP 8.3 or 8.4, MySQL, and Node.js 24 with npm. Enable PHP's PDO MySQL, PDO SQLite (tests), GD, ZIP, mbstring, fileinfo, OpenSSL, and XML extensions. Run commands from a terminal where `php`, `composer`, `node`, and `npm` are available.

**1. Clone and install dependencies** (PowerShell):

```powershell
git clone https://github.com/8qVoid/cbis-thesis.git
cd cbis-thesis
composer install
Copy-Item .env.example .env
```

**2. Start MySQL and create an empty database** using HeidiSQL, phpMyAdmin, or the MySQL console:

```sql
CREATE DATABASE centralized_blood_inventory CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Edit `.env` to match your local MySQL credentials:

```dotenv
APP_URL=http://localhost:8000
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=centralized_blood_inventory
DB_USERNAME=root
DB_PASSWORD=
MAIL_MAILER=log
```

The log mailer lets you test without an email account. Verification links and emails are written to `storage/logs/laravel.log`; configure your own SMTP credentials for real delivery. Never commit `.env`, patient documents, or database backups.

**3. Initialize and build**:

```powershell
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
npm ci
npm run build
php artisan serve --host=localhost --port=8000
```

**4. Keep two additional terminals running** in the project directory for queued notifications and scheduled inventory checks:

```powershell
php artisan queue:work --tries=3 --timeout=90
```

```powershell
php artisan schedule:work
```

Open [http://localhost:8000](http://localhost:8000). Keep MySQL running. After restarting your PC, restart MySQL and the three Artisan commands; you do not need to reinstall or reseed. Map tiles require internet access.

Do not use `migrate:fresh` on an existing database: it deletes all tables. For an existing checkout, back up the database, run `git pull`, `composer install`, `php artisan migrate`, `php artisan db:seed --class=RolePermissionSeeder`, `php artisan optimize:clear`, `npm ci`, and `npm run build`; then restart workers. The role seeder updates permissions and converts legacy role names, so review customized accounts first. Do not regenerate an existing application key.

For Laragon-specific setup, see [`docs/local-setup.md`](docs/local-setup.md).

## Seeded Development Accounts

These credentials are for local development only:

| Role | Email | Password |
| --- | --- | --- |
| QAO | `admin@cbis.local` | `password` |
| Event Facilitator | `facility.admin@cbis.local` | `password` |
| Blood Bank Staff | `medical.staff@cbis.local` | `password` |

The seed creates the Bacolod main chapter and these three staff accounts. Register Donor/Patient accounts through **Register as Donor or Patient**. QAO can add activity branches and assign Event Facilitators; Blood Bank Staff must belong to the main chapter. Existing prototype account display names may differ from their role badge. Change default passwords before using real data or exposing the site publicly.

## Testing

```powershell
php artisan test
npm run build
```

The feature suite uses a separate in-memory SQLite database configured in `phpunit.xml`; enable PDO SQLite and clear cached configuration before running it (`php artisan config:clear`). Do not point tests at a real database. The suite covers role boundaries, unified Donor/Patient registration, reservation documents and status transitions, main-chapter scope, event approval, alerts, selected reports, donation verification, and blood releases.

## Scope Exclusions

- AI or machine-learning forecasting
- Third-party hospital or national blood-bank integration
- Payments and chat
- Mobile application
