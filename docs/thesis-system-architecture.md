# Centralized Web-Based Real-Time Blood Inventory System

## PHASE 1 - Architecture, Modules, Folder Structure, ERD Summary

### Architecture
- Single Laravel 13 monolith with one MySQL database.
- Multi-facility data is isolated by `facility_id` and role-based middleware.
- Central administrator can access all facilities.
- Facility users are limited to their own records.
- Public portal is read-only for blood availability, schedules, and map.

### Module Breakdown
1. Authentication and RBAC
2. Role-based dashboards
3. Facility management
4. Donor management
5. Donation records
6. Bloodletting records
7. Blood inventory
8. Blood releases/usage
9. Notifications
10. Reports (PDF/Excel)
11. Mapping module (Leaflet + OpenStreetMap)
12. Public portal

### Folder Structure
- `app/Models` - domain entities and relationships
- `app/Http/Controllers` - resource controllers and dashboards
- `app/Http/Requests` - validation and authorization rules
- `app/Events`, `app/Listeners` - real-time inventory updates
- `app/Notifications` - low stock alerts
- `app/Console/Commands` - scheduled expiry and low-stock processing
- `app/Exports` - Excel report export classes
- `database/migrations` - schema definitions
- `database/seeders` - roles, permissions, default users/facility
- `resources/views` - Blade UI and public portal pages
- `routes/web.php`, `routes/console.php` - web and scheduler routing

### ERD Summary
- `facilities` hasMany `users`, `donors`, `donation_records`, `blood_inventory`, `blood_releases`, `donation_schedules`, `blood_bank_locations`
- `donors` hasMany `donation_records`
- `donation_records` hasOne `blood_inventory`; hasOne `bloodletting_records`
- `blood_inventory` hasMany `blood_releases`
- `audit_logs` records critical actions per user/facility
- Spatie permission tables manage roles and permissions

## PHASE 2 - Database Schema, Migrations, Models, Relationships
- Implemented all required tables and related models:
  - users, roles, permissions, facilities, donors, donation_records, bloodletting_records,
  - blood_inventory, blood_releases, donation_schedules, blood_bank_locations,
  - notifications, audit_logs
- Added soft deletes to operational entities.
- Added facility foreign keys and indexes for filtering and report performance.

## PHASE 3 - Permission Matrix, Routes, Controllers, Form Requests

### Permission Matrix
- Central Administrator
  - full system access
- Facility Admin / Blood Bank Personnel
  - donors, donation records, inventory, blood releases, schedules, reports
- Medical Technologist
  - bloodletting records, blood release support, report viewing
- Public User
  - public portal views only

### Route Plan
- Public:
  - `/` blood availability + schedules
  - `/portal/map` map locations/events
- Auth:
  - `/login`, `/logout`
- Protected:
  - dashboards, facilities, donors, donation records, bloodletting records, inventory,
    releases, schedules, locations, reports

### Controllers and Form Requests
- Resource controllers implemented for each core module.
- Form Requests implemented for validation and permission checks.
- Facility scoping enforced for non-central-admin users.

## PHASE 4 - Blade Views, Dashboards, CRUD, Public Portal, Map
- Role-aware dashboard with inventory summary.
- CRUD pages for all required modules.
- Public portal page for blood availability and schedules.
- Leaflet map page with facility markers, schedule snippets, and contact info.

## PHASE 5 - Notifications, Events/Listeners, Scheduler, Reports
- Events/listeners:
  - DonationRecorded -> IncreaseInventoryFromDonation
  - BloodReleased -> DecreaseInventoryFromRelease
- Notifications:
  - queued `LowStockAlert` saved to database notifications
- Scheduled tasks:
  - `inventory:flag-expired` hourly
  - `inventory:notify-low-stock` every two hours
- Reports:
  - PDF via DomPDF
  - Excel via Laravel Excel

## PHASE 6 - Setup, Seeding, Thesis Alignment
- Seeding includes roles, permissions, one sample facility, and default users.
- System is fully centralized with controlled facility creation.
- Scope excludes AI, external hospital integrations, forecasting, mobile apps, and payments.
