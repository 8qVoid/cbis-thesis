# Red Cross requirements comparison

Reviewed against the eight questionnaire photographs and the user's documentator instructions, plus the current project code. This is a requirements review, not a record of implemented changes. Questionnaire statements are interview evidence, not verified medical policy or software authorization. The user's explicit role instructions take precedence where they differ from handwritten suggestions.

## Confirmed branch scope

The user clarified that facilities are different Red Cross branches around Negros. The main branch supplied the questionnaire answers. This is a shared website for multiple Red Cross branches, not a hospital licensing or partnership application system. Each branch owns its inventory; inventories are not shared or pooled.

Implementation baseline: scope inventory, reservations, releases, stock notifications, summaries, and exports to the responsible branch. A reservation must target a branch and must never consume another branch's stock. Public approved activity maps may contain events from multiple branches without exposing inventory. Treat QAO access as branch-scoped by default; the main branch's interview role does not itself authorize access to every branch's inventory. Any distinct central account-administration role needs explicit boundaries and must not imply inventory access.

Retain the facility/branch data model and preserve existing records. Distinguish internal Red Cross branch onboarding from public applications for blood-bank licensing. Who may create branch accounts remains to be defined.

## Target roles from the user's instructions

| Role | Allowed | Restricted |
| --- | --- | --- |
| Quality Assurance Officer (QAO) | Monitor inventory within the agreed workplace scope; receive reservation and low-stock notifications; approve facilitator events and public map pins; set activity locations; export Excel/Word reports; view limited donor information | Cannot accept, reject, allocate, or release blood for patient reservations; cannot see unrestricted donor clinical details by default |
| Blood Bank Staff (BBS) | Manage own facility blood stock and processing; review reservation requirements; accept/reject patient reservations; view detailed donor records; request an in-system summary | Cannot export report files; cannot set activity map pins |
| Facilitator | Create/manage activity proposals and schedules; propose map locations; receive relevant notifications | Cannot publish unapproved events or pins; no inventory, reservation processing, report export, or general donor-management access under the user's stated event-only scope |
| Patient | Register/login; manage own profile; submit blood reservation requests with supporting files and ID; see own reservation status and notifications | No staff actions, other patients' files, or internal inventory access |
| Donor | Choose donor registration; use donor profile and existing donor event functions | No patient/staff privileges merely by registering as a donor |

## Existing implementation and required changes

| Area | Current code evidence | Required change |
| --- | --- | --- |
| Roles | `database/seeders/RolePermissionSeeder.php` defines Super Administrator, Facilitator, Medical Staff / Nurse, Public User. Facilitator has broad operational permissions. `app/Models/User.php` identifies central administrators by role and a null facility. | Introduce the agreed QAO/BBS roles and revise policies, routes, requests, navigation, and account migration. Renaming labels alone is insufficient. Avoid giving QAO the existing central-administrator bypass. |
| Blood categories | `app/Models/BloodInventory.php` records ABO/Rh blood type but no component category. | Add Whole Blood, Packed Red Blood Cells, Platelet Concentrate, Fresh Frozen Plasma separately from ABO/Rh. Carry category through inventory, donation processing, reservations, release, filters, alerts, and reports; do not pool different components. Confirm classification of existing records rather than inventing it. |
| Patient workflow | `routes/web.php` has donor registration; no patient registration/reservation module. `BloodRelease` stores a patient name only. | Add patient accounts, private uploads, staff review, status history, patient notifications, QAO informational alerts, and reservation/release linkage. |
| Event approval | `DonationScheduleController::store` saves events and notifies eligible donors directly. Public queries use `is_public` and planned/ongoing status; no separate QAO approval gate. | Add pending/approved/rejected review state and reviewer audit. Only approved events and pins may reach public pages, donor registration, or public notifications. Material edits to approved events should require renewed approval. |
| Reports | `ReportController` exports XLSX/PDF; its authorization rejects central admins and requires inventory-management capability. | Give QAO separate export permission, add Word DOCX if 'docs' means Word, and deny export endpoints to BBS. Define the staff summary-request workflow separately from file generation. |
| Donor privacy | `DonorController` returns the same donor detail view for authorized users. Donor model has basic profile/eligibility fields; no explicit serology module. | Define a limited QAO field list and detailed BBS field list, enforced server-side including exports and notifications. Decide separately whether serologic results enter thesis scope. |
| Public stock | Public `/portal/availability` route and `PublicPortalController::availability` expose availability by facility. | Interview says not to publish stock/availability. Remove public access at route/query level, not merely hide navigation. Public event maps can remain. |
| Facility applications | Public `/facility/apply` and `FacilityApplicationController` implement facility application/approval/onboarding. | Confirmed facilities are Red Cross branches around Negros, each with its own inventory. Retain branch records; replace the public hospital/blood-bank application concept with appropriately authorized internal branch onboarding. Do not present onboarding as licensing. Preserve existing records. |
| Stock alerts | Low-stock command/listener use one `LOW_STOCK_THRESHOLD` value, currently 5. | Add component-aware thresholds and agreed recipients. Handwritten emergency levels read WB 20 per blood type, PRBC 20, platelets 5, FFP 10 units. Confirm aggregation and inclusive/exclusive trigger rule. |
| Immediate updates | Inventory listeners exist, but that alone does not prove immediate refresh across open browsers or integration with external systems. | Verify stock updates across sessions; add suitable refresh/delivery behavior if needed. No external-system integration should be claimed without an actual interface. |

## Interview findings and limitations

- Current inventory uses Excel/spreadsheets; donation records and stock monitoring use a computer system. BBS and medical technologists manage inventory; IT operates the computer system, and QAO is named website administrator.
- Reported problems include inaccurate records and difficulty tracking supply. FIFO, expiry monitoring, and redistribution of near-expiry blood are checked as current practices, not proof that all must be implemented in this thesis.
- The recorded workflow is registration, donor screening, medical check-up, data encoding, collection, component preparation, serologic testing, storage, distribution. A component dropdown alone does not implement this entire clinical workflow.
- Q13/Q8 photograph lists Whole Blood, Packed Red Blood Cells, Platelet Concentrate, and Fresh Frozen Plasma. The handwriting associates 35, 35, 5, and 1 year with them; the first three units are not explicit. Do not hard-code clinical expiration rules from this alone; retain explicit expiry dates pending confirmation of local practice.
- Physical donor cards and both reservation and walk-in requests are checked. Online submission must not imply that original-document verification or physical collection is unnecessary.
- Reservation notes mention an original blood request, referral letter from a partner, and IDs. Another note asks for a government/student ID upload. Clarify exactly which documents are mandatory for each account/request and which originals BBS checks on collection.
- Q15 and Q19 answer no to other hospitals applying as blood banks/storage facilities. Q24 answers no to sharing inventory in the proposed centralized system. Q20–23 are blank; blanks must not be interpreted as approval of partnership or licensing workflows.
- Q25 requests real-time integration/immediate updates, but Q26 gives no API or technical integration requirements.
- The handwritten suggestions allow facilitator-level access to serology and adjustable inventory/expiry notifications, whereas the user's documentator instructions limit facilitators to events and give QAO limited donor information. Use the user's narrower permissions unless explicitly revised.

## Decisions still needed

1. Branch scope is resolved: multiple Red Cross branches with independent inventories. Define who administers branch accounts; do not grant cross-branch inventory visibility by default.
2. Does BBS 'request summary' mean an immediate on-screen system response, or a request that QAO reviews and fulfils? What does 'QAO does not generate summary' exclude, and should exports contain detailed rows only?
3. Does 'Excel or docs' mean both XLSX and DOCX, and which record sets/date ranges belong in each export?
4. What exact donor fields can QAO see, and is serology in scope? Do not grant access to test results based only on a general administrator role.
5. Which reservation documents are mandatory, when does acceptance reserve stock, how long is it held, and what happens on cancellation, rejection, expiry, or collection?

## Implementation safeguards and verification

- Store patient IDs, referral files, and medical requests privately, separate from public event photos. Authorize every upload download by owner and staff role; QAO notifications should not leak file contents.
- Patient registration must never grant staff roles. Enforce ownership and facility scope on every read/write/export endpoint.
- Separate reservation acceptance, stock allocation, and physical release. Use transactions and locking so simultaneous requests cannot overbook or double-release units. No automated clinical compatibility/eligibility decisions are implied by this review.
- Restrict QAO reservation actions and BBS export/map actions on the server, even if clients directly request their URLs.
- Test all role denials, cross-facility access, private-file access, event approval/reapproval, component-separated stock, reservation contention, stock restoration, report permissions, and notification recipients.
- Keep database backups and preserve records when migrating roles and scope. Existing application tests need realignment with the approved requirements, rather than preserving obsolete permissions merely to make tests pass.

Suggested order: finalize role boundaries within the confirmed branch scope; implement permissions/privacy; introduce components and alerts; add patient reservations; add event approvals; separate reports and summaries; validate each role in Chrome and automated tests.
