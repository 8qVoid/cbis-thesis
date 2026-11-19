# Report request date selection fix

Previously, any pending selected report blocked every other selected report from the same requester, even for different dates.

Pending duplicates are now matched by requester, report type, actual date boundaries, selected sections, and detail level. Reordering the same sections or selecting the same dates using a different period mode still counts as a duplicate. Existing pending requests remain valid and QAO approval is still required.

Update an existing installation without resetting data:

```powershell
git status
git pull --ff-only origin main
php artisan optimize:clear
```

If Git reports conflicting local changes, stop and preserve those changes before continuing. This fix needs no migration, seeder, dependency installation, or asset build. Do not run migrate:fresh or seeders for this fix. Reload the report page after updating.

Check: submit a selected report, then request a different date range. Both should be pending. Submit the identical selection again; it should be blocked until the earlier request is reviewed.
