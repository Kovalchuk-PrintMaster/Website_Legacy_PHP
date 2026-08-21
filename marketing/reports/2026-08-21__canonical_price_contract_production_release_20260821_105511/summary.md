# Canonical price contract production release

Generated: `2026-08-21T10:58:34.048094+03:00`
Git checkpoint: `1b90064529e4484d3f4824a376c0705e223a75ac`

## Released code

- `base/core/admin/controllers/BaseAdmin.php`
- `base/core/admin/views/include/form_templates/price_mode.php`
- `base/core/base/settings/Settings.php`
- `base/templates/default/include/productCardHelpers.php`
- `base/templates/default/include/structuredData.php`

## Database

- Exactly 42 reviewed rows migrated `range → request`.
- `price_from` and `price_to` were preserved.
- Before visible modes: `{"exact": 1, "range": 161, "request": 2}`
- After visible modes: `{"exact": 1, "range": 119, "request": 44}`

## Safety

- Fresh full off-host rollback snapshot: `/srv/software_development/forprint-project/forprint_website/.runtime/backups/hosting/20260821_105541`
- Exact five-file pre-release copies are stored in this report.
- Unrelated Batch B files were hash-guarded and remained untouched.
- No Git or Google Ads mutation was performed.

## Acceptance

- All 42 migrated product routes returned HTTP 200 and rendered request state without Product numeric offers.
- A remaining range representative preserved AggregateOffer bounds.
- The exact-price representative preserved Offer.price.
- Production sitemap remained 191 URLs.
