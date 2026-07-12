
ForPrint Website — Current Status
Status

local_website_runtime_smoke_v0_5_6_completed

Repository

/srv/software_development/forprint-project/forprint_website

Remote

git@github.com:Kovalchuk-PrintMaster/Website_Legacy_PHP.git

Website base

base/

Current state
Repository scaffold is committed and pushed.
Legacy base inventory is committed and pushed.
Safe tracking policy is committed and pushed.
Config examples are committed and pushed.
Selected legacy PHP source checkpoint is committed and pushed.
Default hardcoded admin seed is neutralized.
Minimal launch readiness and database import plan is committed and pushed.
Minimal webroot exposure hardening is committed and pushed.
Staging runtime requirements are committed and pushed.
Local database import preparation is committed and pushed.
Local SQL dump intake is committed and pushed.
Local SQL import and smoke run is committed and pushed.
Legacy webroot naming policy is committed and pushed.
Local website runtime smoke v0.5.6 is completed.
SQL dumps and local env/database artifacts are ignored.
The module is treated as a temporary legacy PHP public website, not a full ForPrint rewrite.
base/ remains the intentional legacy PHP webroot.
Public launch remains blocked.
Runtime inspectors
scripts/inspection/check_website_staging_runtime.py
scripts/inspection/check_website_database_import_readiness.py
scripts/inspection/inspect_website_sql_dump.py
scripts/inspection/import_website_sql_dump_local.py
scripts/inspection/check_website_local_runtime_smoke.py
Database
SQL dump is local only and must not be committed.
Local dump path: database_dumps/im_21.05.25.sql.
Local DB name: forprint_website_legacy_local.
Local SQL import status: LOCAL_SQL_IMPORT_AND_SMOKE_OK.
Local runtime smoke status: LOCAL_WEBSITE_RUNTIME_SMOKE_OK.
Current blockers
Local HTTP smoke must be performed.
Public admin must be blocked/restricted.
Runtime directory permissions must be reviewed in deployment context.
HTTPS/server config required.
Upload/mail behavior must be controlled before public launch.
Next recommended checkpoint

ForPrint_Web_Site_Base — Local HTTP Smoke v0.5.7

Local Python environment
Local venv: .venv_website/.
Website Python tooling should run from .venv_website, not Blueprint venv.
Local Python environment status: WEBSITE_LOCAL_PYTHON_ENV_OK.

Local HTTP smoke
Local HTTP smoke status: LOCAL_WEBSITE_HTTP_SMOKE_OK.
Local PHP server route / returns HTTP 200.
Local route /catalog returns HTTP 301.
Local route /search returns HTTP 301.
Legacy DB charset SQL compatibility fix applied.
Website frontend starts locally through 127.0.0.1:8098.
Cart issue remains for a separate checkpoint.

Local operator startup
Server-side local start command: make site-start.
Local HTTP smoke command: make site-smoke.
Windows tunnel helper: scripts/windows/start_website_tunnel.bat.
Frontend refresh workflow documented.
Aggressive frontend replacement is allowed where the old UI is obsolete.
Backend changes remain targeted and safety-reviewed.

Navigation control discovery
Navigation source discovery completed.
Existing information.show_top_menu should be reused before adding a new navigation table.
Public header currently mixes dynamic menu data and hardcoded links.
Cart should remain a separate checkpoint.

Admin rendering and header navigation
Admin PHP 8.2 rendering compatibility fixed.
Admin /admin/show renders locally after login.
Public information top-menu filter typo fixed.
Existing information.show_top_menu is the first admin-controlled top-menu mechanism.
New navigation table remains deferred.
Cart remains separate.

Managed contacts and information pages
Managed Contacts page completed.
Generic managed information page route added.
Contacts are controlled through information admin table.
/contacts/, /information/contacts/, and /information/oplata-i-dostavka/ are covered by local HTTP smoke.
Admin product edit PHP 8.2 checkboxlist fatal fixed.
Next checkpoint: managed special offers product listing.

Managed special offers product listing
/special-offers/ public route added.
Products are selected by existing product flags: sale=1 OR hot=1.
The existing “Спеціальні пропозиції” information row is normalized to special-offers.
Information route redirects to the product listing.
No production DB migration or deployment.

## Promotions, special offers, and delivery

- `/promotions/` added for `sale=1 OR hit=1`.
- `/special-offers/` adjusted for `hot=1 OR new=1`.
- Admin product flag hints show dynamic section names from `information`.
- `Оплата і Доставка` now has base managed information content.
- No production DB migration or deployment.

## Managed news reserve page

- `/news/` reserve text page added.
- `Новини` is now controlled through the `information` table.
- Hardcoded header news link removed.
- The page can be hidden from menu through admin.

## Standalone image optimizer

- Added standalone optimizer tool for manual testing.
- Legacy admin upload remains unchanged.
- No database writes are performed by the optimizer.

## Apply optimized goods image

- Added one-product apply script for optimized goods images.
- Legacy admin upload remains unchanged.
- DB update requires explicit `--apply`.

## Goods upload optimizer pipeline

- Added admin post-upload optimization for `goods.img` only.
- New goods images target `goods/<catalog-alias>/<product-slug>_NN.jpg`.
- Legacy upload remains fallback if optimization fails.

## Gallery upload base fix

- gallery_upload_base_fix_v0_6_9_ready.
- Multiple gallery uploads now work when PHP upload limits allow the request.
- Failed gallery uploads no longer wipe existing gallery records.
- Gallery image optimization remains a separate next step.

## Local dev server upload limits

- local_dev_server_upload_limits_v0_6_10_ready.
- Added make site-serve for PHP built-in server startup with upload limits.
- Default local upload limits: 32M file, 128M POST, 50 files, 512M memory.
- Production/staging PHP-FPM limits remain server-level configuration.
