# ForPrint marketing, Google Ads, search visibility and website quality roadmap v0.1

**Status:** active execution roadmap
**Date:** 2026-08-18
**Scope:** ForPrint website, Google Ads, organic search, technical web quality, measurement
**Owner boundary:** ForPrint only; E-Machine is outside this roadmap and must not be modified

<!-- FP_GROWTH_HORIZON_2026_09_06_V2 -->
## Live planning override — 2026-09-06

This block is the current planning authority for the next execution horizon.
Older sections remain historical evidence and do not override this block.

### Accepted current state

- canonical production full sync on 2026-09-06: **PASS**;
- production code + project-managed media/userfiles + FULL local DB mirror:
  **accepted**;
- protected hosting runtime: **preserved**;
- Google Ads measurement runtime: **PASS**;
- browser consent -> Google loader: **PASS**;
- Google Ads optimization workstream: **closed / reopen on explicit trigger**;
- bidding baseline: **Maximize clicks**;
- controlled real lead-conversion E2E: **not fired**.

### P0 / fatal blockers

No currently verified P0/fatal website condition blocks local feature development.
Production release health is green.

A historical defect is not promoted to P0 without fresh reproduction.

### P1 NOW — search/indexation evidence

1. Run a fresh production crawl after the 2026-09-06 full sync.
2. Reconcile Google Search Console ownership/property state.
3. Submit or refresh the canonical production sitemap in Search Console.
4. Use URL Inspection on representative home/category/product/contact URLs.
5. Record current indexation/enhancement evidence.
6. Re-check the older product `BreadcrumbList` anomaly from fresh production
   evidence; repair only if reproduced.

This P1 work is important, but it does **not** block local development of the
supplier-catalog channel.

### Strategic project NOW — supplier catalog / partner channel

Goal: stop sending ForPrint customers away to supplier websites for product
selection.

Architecture direction:

```text
supplier feed/API
-> supplier-specific Python adapter
-> normalized supplier catalog model
-> controlled local synchronization
-> ForPrint PHP presentation
-> ForPrint branding/request workflow
```

Pilot supplier: **Totobi**.

Rules:

- one supplier adapter, one normalized internal model;
- do not iframe or reverse-proxy the supplier storefront;
- do not copy supplier frontend HTML;
- imported supplier content is `noindex` by default until ForPrint adds
  substantial unique branding/service value;
- supplier source identity, external SKU, timestamps and source URL remain
  traceable;
- do not publish supplier images/descriptions to production until commercial
  reuse permission is confirmed;
- local parsing/normalization/prototyping may proceed before that confirmation;
- source-feed credentials/access tokens, if any, are runtime configuration and
  never hardcoded into repository source.

Phase S1 starts now with a read-only Totobi YML adapter and normalized data
contract. Database integration follows only after the current PHP catalog/data
owners are resolved.

### Admin/frontend tracked work

After the first supplier backend slice and search/indexation evidence:

- resume Admin Phase 8 at main Footer settings visual closure;
- finish responsive/accessibility visual closure;
- do not reopen already accepted Footer child forms without a new regression.

Tracked but not urgent:

- homepage slider media upload/rename/compression pipeline;
- PHP 8.2 warning cleanup;
- deferred catalog/product sorting/filtering;
- bounded legacy CSS reduction.

### Google Ads event horizon

**2026-09-09..2026-09-13 (+3–7 days):** diagnostics only.

**2026-09-13..2026-09-20 (+7–14 days):** first evidence review only if enough
fresh data exists.

**On/after 2026-10-06 OR after meaningful valid conversion volume:** strategy
review; calendar age alone does not authorize bidding changes.

### Monthly recurring evidence

- Search Console queries/pages/indexation/Core Web Vitals;
- 404/redirect/canonical/sitemap drift;
- structured-data regressions;
- recurring production crawl;
- Ads evidence only when enough new data exists.

<!-- /FP_GROWTH_HORIZON_2026_09_06_V2 -->

## 1. Purpose

This roadmap coordinates the work that can proceed now while Google Support is
handling account/payment/advertiser-verification issues, and the work that must
resume immediately after Google replies.

The roadmap intentionally joins three tracks:

1. website technical quality and search readiness;
2. Google Ads account/campaign recovery and controlled activation;
3. measurement and long-term organic/paid growth.

The PHP website remains the runtime product. Python is the preferred project
tooling language for crawl, validation, inspection, reporting, regression and
marketing-data work.

## 2. Current accepted baseline — 2026-08-18

### Website / production

- production site is stable after canonical full-sync and recovery hardening;
- `Еко-візитки` are published;
- `/product/brenduvannya-avto/` returns HTTP 200;
- legacy `/product/obmna-vishivka-na-kepkah/` permanently redirects to the
  semantic Auto Branding URL;
- protected email/Telegram communication acceptance passes;
- production hosting contains no persistent deployment backup payloads;
- canonical SSH transport has keepalive/connection reuse and bounded retry
  only for read-only, idempotent or deterministically restartable operations;
- normal production DB import remains fail-closed.

### Google Ads

- account: customer ID `742-917-3412`;
- ForPrint Phase 1 structure has been posted through Google Ads Editor;
- three false-positive keyword posting errors were resolved and posted;
- old ForPrint PROBE campaigns may remain active under operator budget control;
- new/expanded ForPrint structure remains subject to final status/budget review
  before broad activation;
- all ForPrint campaign families are intended to remain available through the
  end of 2026 for observation and optimization;
- E-Machine must not be structurally edited as part of ForPrint work.

### Google Support / legal-account state

Continue the existing support case:

`[9-5345000041894]`

Pending items:

- restore/identify administrator access to Ads business Payments Profile
  `7425-3175-6083`;
- correct the organization legal name from `For-Print Group` to the actual
  legal advertiser:
  `ТОВАРИСТВО З ОБМЕЖЕНОЮ ВІДПОВІДАЛЬНІСТЮ «НАПЕЧАТЬ СОЛЮШН»`;
- preserve the existing tax/address identity already tied to the correct
  company;
- do not use `Change who pays` unless Google Support explicitly requires it;
- do not close payment profiles;
- do not modify personal Payments Profile `4995-3114-4899`;
- reset advertiser verification from the incorrect agency/client flow to
  advertising our own business;
- if the Reset verification control remains unavailable, ask Support to reset
  it manually in the same case.

## 3. Track A — work that proceeds NOW while waiting for Google

### A1. Build a read-only Python website crawler/auditor

Create a reusable project-owned inspection tool that can crawl local preview
and production without mutating either environment.

Required output per URL:

- source/discovery URL;
- final URL;
- HTTP status;
- redirect chain;
- response content type;
- canonical;
- robots meta and X-Robots-Tag;
- indexability classification;
- document language;
- title;
- meta description;
- H1 count and text;
- H2/H3 structure summary;
- Open Graph fields;
- structured-data types;
- internal links;
- image count;
- missing/empty alt classification;
- explicit image dimensions/aspect-ratio signal;
- hreflang relationships when present;
- sitemap membership;
- issue severity: `P0`, `P1`, `P2`, `INFO`.

Artifacts:

- machine-readable JSON;
- review CSV;
- concise Markdown summary;
- reproducible command documented in `marketing/organic-search/`.

### A2. HTML validity and semantic markup

Run W3C/Nu-style HTML validation on representative page classes and then on
the crawl set where practical.

Representative templates:

- home;
- catalog root;
- category;
- product/service detail;
- contacts;
- search;
- information/news page;
- each actual language variant.

Classify findings:

- invalid nesting / parser-impacting markup;
- duplicate IDs;
- invalid or obsolete attributes;
- form-label/accessibility problems;
- harmless legacy warnings.

HTML validation is a quality check, not a standalone ranking score.

### A3. `<head>` / metadata audit

Verify one coherent metadata contract for every indexable page:

- unique descriptive `<title>`;
- useful page-specific meta description;
- one preferred canonical URL;
- correct `robots`;
- correct `html lang`;
- charset;
- accessible viewport;
- favicon/site identity;
- page-level metadata generated from controlled application data.

Do not allow controllers/templates to invent conflicting global SEO rules.

### A4. Heading and visible-content semantics

For important indexable pages verify:

- one clear primary H1;
- logical H2/H3 hierarchy;
- no headings used only for styling;
- meaningful visible text around commercial content;
- category and product/service pages explain what can actually be ordered;
- no keyword stuffing or near-duplicate thin landing pages.

### A5. Multilingual URL and hreflang audit

The project supports two languages, therefore first document the actual
production URL/language model before changing markup.

Then verify:

- each language has a stable URL;
- reciprocal hreflang pairs where applicable;
- self-referencing language entry;
- correct language/region values;
- canonical does not collapse one real language version into another;
- internal language switcher links are crawlable;
- sitemap strategy is consistent with the language model.

Do not add hreflang until the URL pairing is proven.

### A6. Open Graph and social-preview contract

Use the same page metadata authority for social metadata.

Baseline fields:

- `og:type`;
- `og:title`;
- `og:description`;
- `og:url`;
- `og:image`;
- `og:site_name`;
- locale / locale alternate when the real multilingual model supports it.

Add X/Twitter Card metadata only if it remains maintainable from the same
metadata object.

Verify that social images:

- return 200;
- are crawlable;
- have stable absolute URLs;
- have suitable dimensions and file size.

### A7. Structured data

Implement only factual structured data that matches visible content.

Sequence:

1. `Organization` / appropriate `LocalBusiness`;
2. `WebSite`;
3. `BreadcrumbList`;
4. classify each commercial entity as category, product, configurable product
   or service;
5. add `Product`/`Offer` only where real offer data is accurate;
6. use `Service` where the page is genuinely a service rather than a concrete
   product;
7. validate generated JSON-LD and rich-result eligibility.

Never invent ratings, reviews, availability, delivery terms or prices.

### A8. URL, canonical, redirect and error policy

Audit:

- HTTP → HTTPS;
- preferred host;
- trailing-slash consistency;
- case variations;
- old aliases;
- tracking parameters;
- sorting/filtering parameters;
- pagination;
- 404/410 handling;
- redirect chains/loops;
- soft-404 patterns.

Every indexable page must have one preferred canonical URL.

Permanent URL changes require direct 301/308 redirects to the closest semantic
replacement, plus internal-link and sitemap updates.

### A9. robots.txt, noindex and sitemap

Verify and then formalize:

- public indexable routes;
- admin/private routes;
- internal search;
- filters/sorting;
- preview/diagnostic routes;
- error pages.

Generate XML sitemap from canonical indexable 200 URLs only.

Sitemap must exclude:

- redirects;
- `noindex`;
- errors;
- admin/private;
- internal search;
- arbitrary duplicate filters.

Keep sitemap discoverable from `robots.txt`.

### A10. Internal linking and crawl depth

Measure:

- orphan pages;
- pages reachable only from search/JS;
- crawl depth from home/category hubs;
- broken internal links;
- links to redirecting URLs;
- generic/empty anchor text;
- product ↔ category relationships;
- related products/services;
- breadcrumbs.

Important pages must be reachable through normal `<a href>` links.

### A11. Image SEO and media quality

Audit:

- meaningful `alt` for informative imagery;
- `alt=""` for decorative imagery;
- descriptive filenames where practical;
- crawlable image URLs;
- explicit width/height or stable aspect ratio;
- appropriate source dimensions;
- responsive variants;
- compression / modern formats;
- below-the-fold lazy loading;
- hero/LCP prioritization;
- duplicate oversized downloads.

Do not rename established public image URLs without a deliberate migration
policy.

### A12. Performance / Core Web Vitals baseline

Measure representative templates on mobile first and desktop second.

Track:

- LCP;
- INP/lab interaction proxies until field data exists;
- CLS;
- TTFB;
- render-blocking CSS/JS;
- duplicate/obsolete libraries;
- cache headers;
- image transfer size;
- long tasks.

Measure before/after every meaningful optimization.

### A13. Accessibility-adjacent web quality

Include baseline checks that also improve crawler/user robustness:

- keyboard navigation;
- visible focus;
- form labels;
- image alternatives;
- semantic landmarks;
- zoom not unnecessarily disabled;
- valid link/button semantics;
- reasonable contrast;
- no hidden essential content that requires unsupported interaction.

## 4. Track B — actions immediately AFTER Google replies

### B1. Resolve account/payment identity first

Using the existing support case:

1. confirm administrative access to Payments Profile `7425-3175-6083`;
2. correct the organization legal name to
   `ТОВ «НАПЕЧАТЬ СОЛЮШН»` / the exact legal form requested by Google;
3. verify address/tax identity remains the existing company identity;
4. do not transfer payer unless Support explicitly changes its earlier guidance;
5. keep personal Payments Profile `4995-3114-4899` untouched.

Capture screenshots/notes of the accepted final state into a dated marketing
working-state report.

### B2. Correct advertiser verification

Reset the incorrect agency/client verification flow.

Expected business relationship:

- ForPrint is advertising its own business;
- legal advertiser is the selected company entity;
- do not create fictitious agency/client relationships.

If UI reset is unavailable, continue in support case `[9-5345000041894]`.

### B3. Policy cleanup / re-review

Re-check all account and ad policy statuses after identity verification.

Known historical items:

- Ad ID `819220363637` had `Destination not working` for
  `/catalog/beydzh/` after an earlier HTTP 503; production availability must be
  revalidated and the ad sent for review if still disapproved;
- account-level Callout Asset ID `21140561664`
  `Удаленная техподдержка` belongs to the E-Machine context and was disapproved
  under third-party consumer technical support policy.

E-Machine itself must remain untouched. ForPrint campaigns should use their own
campaign-level callouts/assets so unrelated account-level E-Machine messaging
does not define ForPrint ad copy.

Do not remove or edit global assets blindly if that changes E-Machine behavior.

### B4. Campaign status/budget matrix

Download the account fresh into Google Ads Editor after Support changes.

Create one review table containing:

- campaign;
- status;
- budget;
- budget type;
- bidding strategy;
- CPC cap where applicable;
- start/end dates;
- location;
- language;
- Search Partners;
- Display Network;
- landing page;
- ad groups;
- approved/pending/disapproved counts.

Rules:

- E-Machine: inspect only, no structural edit;
- old ForPrint PROBE campaigns may remain active if the operator wants them
  active and budget is controlled;
- new/expanded ForPrint campaigns are activated deliberately, not by accidental
  bulk toggle;
- all intended ForPrint campaign families remain available through 2026-12-31
  for observation unless later data justifies consolidation.

### B5. New/expanded ForPrint campaign families

Confirm the already prepared expansion remains present and correct, including:

- envelopes;
- operational printing;
- large-format Kyiv;
- auto branding Kyiv;
- expanded signs/outdoor-advertising ad groups;
- local Borshchahivka structure;
- existing product-family campaigns.

Auto Branding now has a valid semantic product URL and the old wrong product
slug has a permanent redirect. Decide whether the campaign should keep its
category landing or use the product-detail landing based on intent and content
quality.

Add `Еко-візитки` to the most semantically appropriate existing/new campaign
structure only after keyword/landing review; do not create a separate campaign
solely because the product became visible.

### B6. Assets and RSA quality

For each active ForPrint campaign:

- verify final URL;
- verify headlines/descriptions against actual landing content;
- remove stale/unsupported claims;
- add/verify ForPrint campaign-level callouts;
- verify sitelinks;
- verify business name/logo assets if available;
- check policy status;
- improve RSA Ad Strength only when wording remains useful and truthful.

Do not optimize for the Ad Strength label at the expense of message quality.

### B7. Conversion measurement before conversion-based bidding

Current lead-form conversion setup is not yet a reliable optimization signal.

Required conversion:

> successful accepted website lead submission

It must not fire on:

- page view;
- modal open;
- form focus;
- validation error;
- failed send.

Instrument the existing communication success path without breaking
Telegram/email delivery or CSRF/security behavior.

Validate locally and on production in a non-sending/test-safe way where
possible, then verify the Google tag/measurement state.

Keep Maximize Clicks while reliable conversion volume is absent. Do not switch
the whole account to conversion-based bidding merely because a conversion
action exists.

### B8. Controlled campaign activation

After legal/verification/policy and landing checks are clean:

- activate intended paused ForPrint campaigns in controlled batches;
- preserve operator budget control;
- confirm no accidental total-budget/daily-budget mismatch;
- confirm locations and language;
- confirm search network settings;
- monitor first serving closely.

Do not enable every campaign simply to remove `Paused` status; activation is an
explicit business decision.

### B9. First data-review window

Once impressions begin, establish the first evidence window.

Review after sufficient traffic, with an initial operational checkpoint around
7–14 days rather than making same-day structural decisions.

Track:

- impressions;
- clicks;
- CTR;
- average CPC;
- spend;
- search terms;
- conversions;
- conversion rate;
- cost per accepted lead;
- campaign/ad-group/keyword serving;
- policy/limited status;
- landing-page failures.

Use actual search terms to grow negatives and identify missing commercial
landing pages.

## 5. Track C — Google Search Console and indexing

This can be prepared now, but final submission/recrawl work follows technical
fixes.

### C1. Search Console ownership and properties

- verify production domain/property ownership;
- confirm preferred production HTTPS host;
- preserve access for the real business owner;
- record property ownership in project documentation without storing secrets.

### C2. Submit sitemap

After sitemap audit/fix:

- expose canonical production sitemap;
- reference it from `robots.txt`;
- submit in Search Console;
- monitor fetch/parse errors.

### C3. URL Inspection

Request indexing/recrawl selectively for important changed URLs, including:

- home if metadata changes materially;
- key category pages;
- newly published `Еко-візитки`;
- `/product/brenduvannya-avto/`;
- other high-value newly added/updated commercial pages.

Do not submit redirected legacy URLs as preferred pages.

### C4. Indexation monitoring

Review:

- indexed/not indexed;
- crawled currently not indexed;
- discovered currently not indexed;
- duplicate/canonical selections;
- soft 404;
- redirect issues;
- blocked/noindex;
- server errors.

Compare Search Console findings with our own crawler rather than treating either
source as the sole truth.

### C5. Rich results / structured data monitoring

After JSON-LD deployment:

- validate before release;
- monitor Search Console enhancements where supported;
- fix factual/schema errors;
- do not chase unsupported rich-result types.

## 6. Track D — recurring SEO/web-quality regression

After the first cleanup, technical SEO becomes a recurring engineering check.

Target automated regression:

- crawl production;
- compare URL inventory to previous baseline;
- fail/report new 4xx/5xx;
- report new redirect chains;
- detect missing/duplicate titles;
- detect missing canonicals;
- detect indexable pages absent from sitemap;
- detect sitemap pages that redirect/error/noindex;
- detect missing H1;
- detect broken internal links;
- detect broken Open Graph images;
- validate JSON-LD parseability;
- detect accidental language/hreflang regression;
- sample HTML validation;
- store dated report under `marketing/reports/`.

The recurring audit must be read-only.

## 7. Priorities

### P0 — blockers / correctness

- production 5xx;
- broken high-value landing;
- wrong canonical to another page/language;
- accidental global `noindex`;
- sitemap containing large sets of errors/redirects;
- broken advertiser/payment identity;
- incorrect advertiser verification;
- broken lead conversion implementation;
- policy disapproval that prevents intended serving.

### P1 — important

- duplicate/missing titles;
- weak/missing descriptions on commercial pages;
- heading hierarchy;
- internal broken links;
- hreflang consistency;
- Open Graph;
- structured-data baseline;
- image alt/dimensions;
- campaign asset/landing consistency;
- search-term negative maintenance.

### P2 — polish / measured optimization

- additional schema coverage;
- content expansion;
- asset bundling/minification;
- deeper CWV optimization;
- richer social previews;
- long-tail landing pages;
- budget/bidding sophistication after enough data.

## 8. Execution order from this checkpoint

1. Record this roadmap and keep Google Support wait state explicit.
2. Build Phase-A read-only Python crawler/auditor.
3. Produce baseline production report before broad SEO edits.
4. Fix P0 technical/indexation findings.
5. Implement one metadata/head ownership contract.
6. Fix canonical/robots/sitemap/language/hreflang.
7. Add Open Graph and factual JSON-LD.
8. Improve internal linking, image semantics and performance.
9. When Google replies, execute Track B without stopping safe website-quality work.
10. Submit/refresh sitemap and important URLs in Search Console.
11. Start recurring crawl + Ads performance review.
12. Build longer-term content/landing-page plan from real Search Console and
    Google Ads search-query evidence.

## 9. Evidence and change discipline

For every material change:

```text
requirement / evidence
→ scoped local implementation
→ focused validation
→ production-safe deployment
→ post-release acceptance
→ dated report
→ Git checkpoint when accepted
```

Do not combine unrelated URL migrations, visual rewrites, metadata changes,
Google Ads structural edits and production recovery into one unreviewable
change.

## 10. Related project areas

- `marketing/campaigns/` — paid campaign working material;
- `marketing/organic-search/` — SEO/Search Console work;
- `marketing/measurement/` — conversions and measurement;
- `marketing/reports/` — dated evidence;
- `marketing/research/google-ads/` — keyword/landing research;
- `docs/.../search_visibility_and_web_quality_strategy_v0_1.md` — canonical
  website search-quality architecture/policy wherever it currently lives.

This roadmap coordinates execution. It does not replace the deeper search
architecture policy.

<!-- FP_SEO_BASELINE_CRAWLER_STATUS_V0_1_START -->
### Track A1 implementation checkpoint — 2026-08-18

Status: **baseline crawler implemented and first production crawl completed**.

Persistent tool:

`scripts/inspection/audit_search_visibility.py`

Latest baseline report:

`marketing/reports/2026-08-18__seo_baseline_v0_1`

Next action:

1. review P0/P1 findings;
2. verify intent against the real route/language model;
3. run representative HTML documents through W3C/Nu validation;
4. only then begin scoped PHP/template metadata repairs.
<!-- FP_SEO_BASELINE_CRAWLER_STATUS_V0_1_END -->

<!-- FP_SEO_BASELINE_V0_2_STATUS_START -->
### Track A1 baseline refinement — crawler v0.2

The initial v0.1 baseline exposed an audit-model issue: the HTTP client follows
redirects, so a redirect source could inherit final-page metadata and appear as
an indexable duplicate.

Crawler v0.2 records requested and final HTTP status separately and excludes
redirect sources from duplicate title/description/canonical analysis.

Corrected report:

`marketing/reports/2026-08-18__seo_baseline_v0_2`

Site changes must be based on v0.2-or-later evidence.
<!-- FP_SEO_BASELINE_V0_2_STATUS_END -->

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

<!-- FP_EXTERNAL_AD_BANNER_MIGRATION_V0_1_START -->
### Wide-format banner semantic split — 2026-08-18

Two visible products intentionally serve different catalog contexts:

- `goods.id=156`, parent `19`: `Широкоформатні банери`,
  `/product/shirokoformatn-baneri/` — wide-format printing context;
- `goods.id=222`, parent `8`: `Рекламні банери для фасадів і конструкцій`,
  `/product/reklamni-baneri-dlya-fasadiv-ta-konstruktsiy/` — outdoor-advertising / facade / construction context.

The former id=222 URL `/product/shirokoformatn-baneri-222/` permanently redirects to the
new semantic URL. Sitemap, canonical metadata and internal route contracts must
use only the new URL.

This is a semantic split, not a duplicate-page merge.
<!-- FP_EXTERNAL_AD_BANNER_MIGRATION_V0_1_END -->

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

<!-- FP_TRACK_A_HEAD_SCHEMA_RELEASE_20260819_START -->
## Track A checkpoint — centralized head/schema release complete

Status date: **2026-08-19**

Completed: centralized head/Open Graph ownership, canonical JSON-LD structured data and breadcrumbs, factual `Organization`/`LocalBusiness` identity, 191-URL sitemap acceptance, one guarded production sync with rollback evidence.

Next: fresh production crawl -> Search Console readiness -> measurement source inventory -> next P1 batch from fresh evidence -> recurring read-only regression.

Google Ads Track B remains paused until Google Support replies.
<!-- FP_TRACK_A_HEAD_SCHEMA_RELEASE_20260819_END -->
