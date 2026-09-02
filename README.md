# Centralized Blood Inventory System

A Laravel-based thesis system for Philippine Red Cross blood inventory, donation events, donor records, and patient blood reservations across multiple Negros Occidental branches.

Each facility maintains its own inventory. The Quality Assurance Officer (QAO) can monitor branches centrally, while operational actions remain assigned to the responsible facility staff.

## Core Features

- Separate real-time inventory for every Red Cross facility
- Four blood components: whole blood, packed red blood cells, platelet concentrate, and fresh frozen plasma
- Donation and bloodletting records
- Patient blood reservations with ID, prescription, and supporting-document uploads
- Event scheduling with QAO approval before map publication
- Low-stock, reservation, and event-review notifications
- Facility-specific summaries and QAO report exports
- Public facility and approved-event map
- One public account that can enable Donor services, Patient services, or both

## Role Access

### Quality Assurance Officer (QAO)

Can:

- Monitor inventory across all facilities
- Receive low-stock and new-reservation notifications
- View reservation status without processing the request
- Review and approve or reject event map publication
- Manage facilities, blood-bank locations, and staff accounts
- View limited donor information
- Generate system reports and export PDF/Excel files

Cannot:

- Approve, reject, or fulfill patient blood reservations
- Add operational donation or release transactions for a facility
- View private patient reservation documents
- View detailed donor medical information

### Blood Bank Staff (BBS)

Can:

- Work only with their assigned facility's records and inventory
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
- Submit a blood reservation to a selected facility
- Upload the required identification, prescription, and supporting documents
- View reservation status and receive status notifications
- Maintain their patient profile

Cannot:

- Approve or process their own reservation
- View internal inventory quantities or other patients' records

## Donor and Patient Accounts

A person uses one account and one personal profile. During registration, they may select Donor, Patient, or both services. Services can be enabled later without creating a duplicate person or losing existing history.

## Reservation Workflow

1. A patient submits a reservation and required documents.
2. QAO and the selected facility's Blood Bank Staff receive a notification.
3. Blood Bank Staff review the requirements.
4. The request moves through `Submitted`, `Under Review`, `Approved` or `Rejected`, then `Fulfilled` when released.
5. Approval is blocked when the selected facility lacks sufficient unexpired stock for the requested blood type and component.

## Event Publication Workflow

1. An Event Facilitator creates an event and proposes its map location.
2. The event remains pending and is not shown publicly.
3. QAO approves or rejects the event.
4. The Facilitator receives the decision notification.
5. An approved public event becomes visible on the map.

## Technology

- Laravel 13 and PHP 8.3+
- MySQL
- Blade, Vite, and Tailwind CSS
- Spatie Laravel Permission
- Laravel Notifications
- Leaflet and OpenStreetMap
- Laravel Excel and DomPDF

## Local Installation

```powershell
git clone https://github.com/8qVoid/cbis-thesis.git
cd cbis-thesis
composer install
Copy-Item .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
php artisan storage:link
npm install
npm run build
php artisan serve
```

Open [http://localhost:8000](http://localhost:8000).

For Laragon-specific setup, see [`docs/local-setup.md`](docs/local-setup.md).

## Seeded Development Accounts

These credentials are for local development only:

| Role | Email | Password |
| --- | --- | --- |
| QAO | `admin@cbis.local` | `password` |
| Event Facilitator | `facility.admin@cbis.local` | `password` |
| Blood Bank Staff | `medical.staff@cbis.local` | `password` |

## Testing

```powershell
php artisan test
npm run build
```

The feature suite covers role boundaries, unified Donor/Patient registration, reservation documents and status transitions, facility stock isolation, event approval, alerts, reports, donation verification, and blood releases.

## Scope Exclusions

- AI or machine-learning forecasting
- Third-party hospital or national blood-bank integration
- Payments and chat
- Mobile application
