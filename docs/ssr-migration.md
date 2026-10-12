# SSR migration report

Base: main 8ee6f76, includes Big's feature/big-ssr. Date: 12 October 2026.

## Architecture

Before: several Blade pages returned shells, fetched lists through JSON APIs, rendered rows with JavaScript and sometimes paginated/filterered limited collections in memory. Room/Tenant controllers queried lists that initial browser scripts fetched again.

After: initial business page reads use Page Controller, authorized/scoped Eloquent query, Blade HTML. Search/filter links and database pagination preserve query strings. Mutation requests continue using existing Actions through APIs or existing web form routes. Success reloads the current page to read canonical server state.

## Migration matrix

| Page | Before | After | Evidence |
|---|---|---|---|
| Invoice Index | Fetch/render up to 500 records | SSR rows/stats, DB page of 20 | feature test + browser Admin/Tenant |
| Admin Dashboard | Server shell plus API summaries/lists | SSR, shared DashboardSummary with JSON API | feature test + browser |
| Tenant Dashboard | Fetch all pages across endpoints | Scoped SSR, SQL totals and latest lists | feature test + browser |
| Contract Index | Rental API rendered rows | SSR search/status/page of 20, exact contract dates | feature test + modal browser |
| Rental Create | API options and busy tenant lookup | SSR eligible options, dynamic meter lookup retained | feature test; mutation regressions |
| Meter Management | API list renderer | SSR selected-room history/page of 20; batch flags | feature test + browser usage selection |
| Repair Management | API list renderer | Scoped SSR page of 20, room/status | feature test + history browser |
| Room List | Unused controller data then API render | SSR cards/table, search/floor/status/page of 20 | feature test + view toggle/history browser |
| Tenant List | Unused controller data then API render | SSR search/page of 20, existing web forms | feature test |
| Invoice History/Detail | Big SSR | Retained | existing SSR regression tests |
| Rental Index/Detail/Contract | Big SSR | Retained, create options augmented | existing SSR regression tests |
| Tenant rental/contract/meter/profile | SSR | Retained | existing tenant portal tests |
| Account/Settings/Auth | Livewire/Blade/Fortify | Retained | existing auth/account tests |

## Shared read code

- Invoice scopes are reused for API/page visibility, search and month. Search adds numeric/display invoice IDs; JSON response shape remains stable.
- DashboardSummary contains the pre-existing admin aggregation. Page and API both call it, with authorization at their entry points. Server never calls its own HTTP API.
- MeterPresentation batches invoice references (including soft-deleted), rental boundaries and latest Audit IDs for rendered records. Actions remain the authoritative mutation guard.
- Room history uses a new admin-only /rooms/{room}/history read endpoint, exact room IDs and eager loading instead of scanning global Rental/Invoice/Repair API pages.

## API dependencies

Still required: Rental contract detail when modal opens, Meter baseline after selecting a room, Meter usage preview, Repair histories, Invoice detail/payment/proof lookups during dialogs, protected proof downloads, Rental list for Issue dialog, Global Search interactions, all POST/PATCH/DELETE business endpoints.

Potentially redundant for initial rendering: GET dashboard summary, list APIs for rooms/tenants/repairs/invoices and some rental reads. They remain available because tests, interactive helpers and external callers may rely on them. No API endpoint was deleted. Detailed route/reference inventories accompany this report.

## Validation and limits

Automated tests include initial HTML data, filtering, pagination with query-string preservation, tenant isolation, guests, invalid query values, missing room, snapshot rent vs monthly contract rent, cancellation/locking and XSS escaping. Existing business Action tests remain in the full suite. See the final results section for the exact run.

Browser smoke tests used a separate copied SQLite demo database. Checked Admin dashboard, Room table toggle and history modal, Invoice Issue dialog, Contract modal, Meter selection/usage, Repair detail, Tenant Dashboard and Invoice visibility. No payment, move-out or other financial mutation was submitted in the browser. Those flows rely on automated regression coverage in this pass. Full mobile/browser matrix remains to be tested.

Known remaining limits: room history previews latest 50 records per category; the UI states this limit. Rental Index ACTIVE list in Big's existing SSR and tenant meter history retain their pre-existing loading/query limitations. Modal option loaders may still load multiple API pages on demand. This migration did not redesign those workflows. SQLite-specific indexes/search queries remain. No migrations, billing Actions, payment Actions or database engine changed.

Main page initial data now follows SSR policy in the audited business routes. API helpers and innerHTML still appear in interactive components; their presence alone does not mean initial rendering is still client-side. No claim is made that every viewport, every exceptional UI case or production deployment has been verified.

## Modified files and reasons

- `app/Http/Controllers/Api/V1/DashboardController.php`: SSR read queries, authorization and view data.
- `app/Http/Controllers/ContractPageController.php`: SSR read queries, authorization and view data.
- `app/Http/Controllers/DashboardController.php`: SSR read queries, authorization and view data.
- `app/Http/Controllers/InvoicePageController.php`: SSR read queries, authorization and view data.
- `app/Http/Controllers/MaintenancePageController.php`: SSR read queries, authorization and view data.
- `app/Http/Controllers/RentalPageController.php`: SSR read queries, authorization and view data.
- `app/Http/Controllers/RoomController.php`: SSR read queries, authorization and view data.
- `app/Http/Controllers/TenantController.php`: SSR read queries, authorization and view data.
- `app/Http/Controllers/TenantDashboardController.php`: SSR read queries, authorization and view data.
- `app/Models/Invoice.php`: Reuse visibility/month/search scopes and add numeric/display invoice search.
- `app/Support/DashboardSummary.php`: Share existing admin summary reads with page and JSON API.
- `app/Support/MeterPresentation.php`: Batch reference/boundary/audit reads for meter row permissions.
- `docs/presentation-study-guide.md`: Architecture inventory, validation report or study guide.
- `docs/ssr-api-references.json`: Architecture inventory, validation report or study guide.
- `docs/ssr-migration-inventory.csv`: Architecture inventory, validation report or study guide.
- `docs/ssr-migration.md`: Architecture inventory, validation report or study guide.
- `docs/ssr-route-inventory.json`: Architecture inventory, validation report or study guide.
- `public/js/maintenance.js`: Attach operations to SSR rows; remove initial list fetch; retain boundary selection across pagination.
- `resources/views/contracts/index.blade.php`: Render server data using existing layout/classes and retain interactive controls.
- `resources/views/dashboard.blade.php`: Render server data using existing layout/classes and retain interactive controls.
- `resources/views/invoices/index.blade.php`: Render server data using existing layout/classes and retain interactive controls.
- `resources/views/maintenance/meters.blade.php`: Render server data using existing layout/classes and retain interactive controls.
- `resources/views/maintenance/repairs.blade.php`: Render server data using existing layout/classes and retain interactive controls.
- `resources/views/rentals/create.blade.php`: Render server data using existing layout/classes and retain interactive controls.
- `resources/views/rooms/index.blade.php`: Render server data using existing layout/classes and retain interactive controls.
- `resources/views/tenants/index.blade.php`: Render server data using existing layout/classes and retain interactive controls.
- `resources/views/user/dashboard.blade.php`: Render server data using existing layout/classes and retain interactive controls.
- `routes/web.php`: Bind SSR page controllers and exact-room history route.
- `tests/Feature/ServerRenderedPagesTest.php`: Regression coverage for SSR data, ownership, filters, pagination, escaping and meter boundary flags.

## Final validation before commit

- `composer test`: Pint passed, PHPStan 0 errors, Pest 605 tests: 586 passed, 19 skipped, 2,786 assertions.
- `php84 artisan view:cache`: passed. Compiled views cleared after validation for local development.
- `npm.cmd run build`: passed. Existing optional fontaine warning remains.
- `node --check`: passed for changed maintenance JS and extracted inline scripts (Blade data expressions replaced with test placeholders for syntax checking).
- `git diff --cached --check`: checked during staging.
- Browser smoke checks and screenshots: Tenant Dashboard/Invoice; Admin dashboard, Room table/history, Invoice Issue modal, Contract modal, Meter usage and Repair detail. A Browser automation timeout occurred on the native Logout dialog; the temporary tab was closed and tenant checks continued in a separate host session. This is not counted as a completed native Logout dialog test in this pass.
- A new opening-meter regression initially failed because timestamp storage required date comparisons. The batched boundary lookup was corrected with whereDate; the final full suite above passed afterward.

No business database migration or production data mutation was performed. A copied SQLite demo database served the browser preview, and automated tests used the configured in-memory SQLite test connection.
