# Organic search

This subdomain owns technical SEO, search visibility, search intent/content
work, and Search Console analysis.

<!-- FP_GROWTH_ROADMAP_INDEX_V0_1_START -->
## Organic-search execution roadmap

Technical crawl, HTML validation, metadata, canonical/robots/sitemap,
multilingual/hreflang, Open Graph, structured data, Search Console and
recurring SEO regression are coordinated in:

`../programs/forprint_growth_roadmap_v0_1.md`
<!-- FP_GROWTH_ROADMAP_INDEX_V0_1_END -->

<!-- FP_SEO_BASELINE_CRAWLER_V0_1_START -->
## Canonical read-only search visibility baseline crawler

Persistent tool:

`../../scripts/inspection/audit_search_visibility.py`

Production baseline:

```bash
.venv_website/bin/python3 \
  scripts/inspection/audit_search_visibility.py \
  --base-url https://forprint.net.ua/
```

Local preview:

```bash
.venv_website/bin/python3 \
  scripts/inspection/audit_search_visibility.py \
  --base-url http://127.0.0.1:8098/
```

The crawler is sequential and read-only. It records query-bearing links without
expanding query variants, respects robots by default, excludes private/runtime
surfaces through a conservative safe-skip policy, and writes JSON/CSV/Markdown
evidence under `marketing/reports/`.

Primary evidence:

- URL/status/redirect inventory;
- canonical, robots, indexability;
- title, description, headings and document language;
- Open Graph;
- hreflang;
- JSON-LD parse/type inventory;
- internal link graph;
- image alt/dimension observations;
- sitemap membership;
- duplicate metadata and orphan-like findings;
- P0/P1/P2 issue classification;
- representative URLs for the next W3C/Nu validation phase.

Character-length checks for titles/descriptions are review heuristics, not
ranking rules.
<!-- FP_SEO_BASELINE_CRAWLER_V0_1_END -->

<!-- FP_CANONICAL_SITEMAP_FROM_AUDIT_V0_1_START -->
## Canonical sitemap maintenance — 2026-08-18

Persistent builder:

`scripts/maintenance/rebuild_sitemap_from_search_audit.py`

The current sitemap is rebuilt from a completed read-only crawl inventory. A
URL is eligible only when it is:

- HTTP 200;
- indexable;
- not a redirect source;
- self-canonical;
- on the production HTTPS origin;
- free of query/fragment variants;
- outside private, cart, checkout, internal-search and preview surfaces.

The legacy Auto Branding alias is forbidden; the semantic Auto Branding URL and
published Eco business-card URL are required migration guards.

The route-metadata and structured-data inspection scripts no longer hard-code
an exact sitemap URL count. Sitemap size is content-driven; correctness is
validated by URL semantics and per-route contracts.

Current locally generated sitemap count: **190**.

The PHP admin sitemap crawler remains legacy compatibility code for now. Do not
treat its historical `parsing_data` state as the canonical SEO inventory.
<!-- FP_CANONICAL_SITEMAP_FROM_AUDIT_V0_1_END -->

<!-- FP_HEAD_SCHEMA_OWNERSHIP_AUDIT_V0_1_START -->
## SEO head / structured-data ownership checkpoint — 2026-08-19

Post-release crawl v0.3 discovered one additional indexable product URL, so the
local canonical sitemap was rebuilt from that newer crawl inventory.

Local sitemap count after rebuild: **191**.

The next repair batch is intentionally scoped to centralized metadata ownership:

- Organization / LocalBusiness truth;
- Open Graph baseline;
- structured-data correctness;
- representative head generation;
- no per-product alias-specific SEO hacks.

Source/contract evidence is stored in:

`tmp/seo_head_schema_ownership_20260819_093304/report.md`
<!-- FP_HEAD_SCHEMA_OWNERSHIP_AUDIT_V0_1_END -->

<!-- FP_UKRAINIAN_SLUG_URL_POLICY_V0_1_START -->
## Ukrainian slug / URL migration policy — 2026-08-19

The canonical slug generator for Ukrainian content must use one documented,
testable transliteration policy based on the Ukrainian transliteration table
(Cabinet of Ministers Resolution No. 55), normalized for web slugs:

- lowercase ASCII;
- `и -> y`, `і -> i`;
- context-aware `є/ї/й/ю/я`;
- `г -> h`, `ґ -> g`;
- apostrophe and soft sign are omitted;
- `зг -> zgh`;
- words are separated with `-`;
- Russian-specific letters are not silently interpreted in Ukrainian mode.

Future multilingual support must make the content locale explicit. A translated
display name must not silently change an already-published URL identity.

**Migration rule:** fixing the generator does not authorize bulk regeneration
of existing published aliases. Existing public URLs are migrated only through
an explicit old->new mapping, permanent redirect, internal-link update,
canonical update, sitemap update and post-release crawl acceptance.

Current read-only audit evidence:

`tmp/ukrainian_slug_audit_20260819_095921/summary.md`
<!-- FP_UKRAINIAN_SLUG_URL_POLICY_V0_1_END -->

<!-- FP_CANONICAL_UK_SLUG_GENERATOR_V0_1_START -->
## Canonical Ukrainian slug generator — 2026-08-19

Canonical implementation:

`base/libraries/ForPrintSlug.php`

New/admin-generated Ukrainian aliases use one explicit policy, including
`и -> y`, `і -> i`, `г -> h`, `ґ -> g`, position-aware `є/ї/й/ю/я`,
`зг -> zgh`, lowercase ASCII and hyphen separators.

Integrated generation paths:

- canonical admin alias creation in `BaseAdmin`;
- admin editor upload slug generation;
- product image upload optimizer slug generation.

**Existing published aliases are intentionally untouched.** Bulk URL migration
is deferred to the final SEO migration phase. That later phase requires an
audited old->new mapping, collision review, permanent redirects, internal-link,
canonical and sitemap updates, plus Search Console acceptance.

Future Russian/multilingual behavior must use an explicit locale rather than
guessing language from Cyrillic characters.
<!-- FP_CANONICAL_UK_SLUG_GENERATOR_V0_1_END -->

<!-- FP_HEAD_OG_SCHEMA_V0_1_START -->
## Centralized head / Open Graph / structured-data ownership — 2026-08-19

Canonical public metadata ownership:

- title, description, canonical URL and Open Graph: `base/templates/default/include/header.php`;
- JSON-LD structured data: `base/templates/default/include/structuredData.php`;
- breadcrumb data: `BaseUser::buildBreadcrumbItems()` rendered by `include/breadcrumbs.php`;
- favicon: one canonical `<link rel="icon">`.

Contacts schema falls back to managed `contacts_*` settings when older generic
settings are empty. Existing Product JSON-LD image arrays are unchanged.
Historical/public URL aliases are not migrated in this batch.
<!-- FP_HEAD_OG_SCHEMA_V0_1_END -->

<!-- FP_HEAD_SCHEMA_RELEASE_CHECKPOINT_20260819_START -->
## Accepted head / Open Graph / schema production checkpoint — 2026-08-19

Accepted: centralized JSON-LD breadcrumb owner, navigation-only visible breadcrumbs, `Organization` homepage identity, `LocalBusiness` contacts identity, 191-URL production sitemap, representative Product schema preserved, communication/storage acceptance OK.

Snapshot: `../../docs/status/snapshots/2026-08-19_search_visibility_release_state_v0_1.md`

Next plan: `../../docs/marketing/plans/organic_search_measurement_next_stage_plan_v0_1.md`
<!-- FP_HEAD_SCHEMA_RELEASE_CHECKPOINT_20260819_END -->
