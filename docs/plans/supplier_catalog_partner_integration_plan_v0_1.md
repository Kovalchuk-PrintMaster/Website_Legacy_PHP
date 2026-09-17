# Supplier catalog / partner channel implementation plan v0.1

**Date:** 2026-09-06
**Status:** active
**Pilot:** Totobi
**Production publication:** not authorized by this plan alone

## Goal

Let customers browse suitable blank/base products on ForPrint without leaving
the ForPrint journey, then continue into ForPrint branding/quotation actions.

## Phase S1 — backend foundation NOW

- implement one normalized Python data model;
- implement a streaming YML parser;
- implement Totobi discovery/adapter without hardcoding an access key;
- provide a read-only inspector that writes only runtime/report evidence;
- validate categories, offers, images, prices, stock and parameters;
- preserve supplier/external identifiers and source URLs.

## Phase S2 — current ForPrint owner audit

Resolve before database writes:

- canonical public catalog/product route/controller;
- existing product/category database tables and relations;
- current menu/navigation owner;
- existing image/media ownership;
- current search/indexation metadata owner;
- existing migration naming/execution convention.

No parallel product catalog may be invented if an existing suitable managed
entity can safely represent the supplier layer.

## Phase S3 — supplier storage contract

After S2, choose the narrowest safe model.

Required semantics:

```text
supplier
external_id
group_id
vendor_code
name
category
source_url
price
old_price
currency
availability
stock
incoming_stock
incoming_date
brand
branding_methods
images
attributes
variants/sizes
source_updated_at
last_seen_at
active
```

The exact SQL schema is not approved until S2 evidence is reviewed.

## Phase S4 — local PHP presentation

Create a local-preview surface using ForPrint's existing shell/layout.

Initial UI:

- one small navigation item;
- supplier catalog landing page;
- category/product cards;
- product detail;
- stock/variant facts;
- explicit ForPrint action: branding consultation / calculation.

Do not reproduce the supplier header/footer/frontend.

## Phase S5 — ForPrint value layer

Add ForPrint-owned value:

- compatible branding methods;
- quantity guidance;
- artwork/logo upload/request;
- consultation CTA;
- saved shortlist if justified by existing architecture;
- internal linking to ForPrint services.

## Phase S6 — search and measurement

Default imported supplier records to `noindex`.

Only promote stable pages that have substantial ForPrint-owned value. Keep
supplier-only descriptions from becoming a mass SEO-content generator.

Measure useful ForPrint-side events rather than trying to inflate dwell time.

## Phase S7 — production

Requires:

- confirmed commercial right to reuse required supplier content/media;
- local acceptance;
- database/storage migration acceptance;
- focused checks;
- explicit production authorization.

## Totobi-specific current facts

Totobi publicly documents an hourly-updated YML catalog intended for structured
product import. The documented examples include categories, offers, price,
currency, pictures, availability/stock and arbitrary product parameters.
Textile examples also document size-level data.

The adapter must discover/use the feed through runtime configuration or the
public documentation endpoint; do not commit the feed access-key URL into
source.

<!-- FP_SUPPLIER_S2_OWNER_BINDING_2026_09_06 -->
## Phase S2 execution checkpoint — 2026-09-06

S1 foundation is present. The first live Totobi probe returned an advisory
failure, so S2 starts by hardening public YML discovery/download and by resolving
the existing ForPrint catalog/database/menu owners.

Do not create supplier DB tables or PHP routes until the S2 report identifies:

- existing `catalog` and `goods` read/write ownership;
- current public menu/header ownership;
- current SQL schema/migration convention;
- whether supplier records should remain isolated from canonical `goods` or can
  safely reference existing entities without ownership ambiguity.

The public supplier feed access key is treated as runtime/source metadata and is
never committed or printed unredacted in reports.
<!-- /FP_SUPPLIER_S2_OWNER_BINDING_2026_09_06 -->

<!-- FP_SUPPLIER_S3_LOCAL_STORAGE_2026_09_06_V3 -->
## Phase S3 local storage checkpoint v0.3 — 2026-09-06

S3 v0.1 and v0.2 both stopped before supplier DB mutation and rolled their owned
files back cleanly.

The v0.2 evidence identified a legacy CLI bootstrap issue: `base/config.php`
must be loaded with the application's `VG_ACCESS` guard already defined.
v0.3 defines that guard before requiring the protected local config, buffers any
bootstrap output, and requires a successful framed read-only DB probe before any
supplier schema mutation.

The supplier storage remains isolated from canonical `goods` and `catalog`.
Python owns feed retrieval/normalization; the CLI-only PHP bridge reuses the
existing local website DB runtime without printing credentials.

No supplier media or public PHP route is introduced by S3.
<!-- /FP_SUPPLIER_S3_LOCAL_STORAGE_2026_09_06_V3 -->

<!-- FP_SUPPLIER_S4_LOCAL_SURFACE_2026_09_06_V6 -->
## Phase S4 local PHP surface checkpoint — 2026-09-06

S4 v0.1-v0.4 hardened header and whitespace handling without touching
production. S4 v0.5 reached the local runtime but returned HTTP 404.

The route audit identified the concrete cause: the previous Settings transform
could match the `projectTables['catalog']` metadata row instead of the actual
`routes['user']['routes']` routing owner. S4 v0.6 resolves that nested route block
explicitly, inserts the mapping there, and proves it is unique before HTTP
acceptance.

The first public-facing shape is deliberately a local-preview surface, not a
production publication.

Route: `/supplier-catalog/`.

Contract:

- reads only from isolated `supplier_catalog_*` tables;
- keeps canonical `goods` / `catalog` untouched;
- route is enabled for localhost/127.0.0.1 by default;
- future production enablement requires explicit
  `FP_SUPPLIER_CATALOG_PUBLIC_ENABLED=1`;
- response sends `X-Robots-Tag: noindex, nofollow, noarchive`;
- imported supplier content is not added to sitemap/indexation;
- Totobi image URLs may render only in enabled preview; S4 does not download or
  cache supplier media;
- one controller-owned surface stylesheet owns the component:
  `assets/css/forprint-supplier-catalog.css`;
- header supplier-catalog navigation is visible only while the surface gate is
  enabled.

S4 is a visual/data proof. Next: selection -> branding method -> quantity/logo
-> ForPrint communication request.
<!-- /FP_SUPPLIER_S4_LOCAL_SURFACE_2026_09_06_V6 -->

<!-- FP_SUPPLIER_S5_TRANSIENT_QUOTE_LIST_2026_09_06_V1 -->
## Phase S5 transient quote-list checkpoint — 2026-09-06

S5 replaces cart/order semantics with a transitional **quote list**:

- card action: `Додати на прорахунок`;
- header utility: `На прорахунок` + item count;
- list heading: `Список на прорахунок`;
- final communication action: existing Telegram/Email request flow.

Persistence contract:

- quote-list items live only in browser `sessionStorage`;
- no cart/order table is created;
- no server-side quote-list persistence is introduced;
- loss of the list with browser-session lifecycle is acceptable at this stage;
- full website mirror/deployment does not preserve or reconcile quote-list state.

Submission contract:

- `communicationRequestForm.php` and `communication-request.php` remain the
  single request transport;
- CSRF, honeypot, idempotency, phone validation, delivery, `request_id`, and
  measurement success semantics remain unchanged;
- quote-list items are serialized into the existing `message` field immediately
  before submit;
- no second lead endpoint and no second conversion path are added.

Presentation contract:

- visible supplier/pilot/development branding is removed from customer UI;
- supplier identity remains internal technical context only;
- the supplier surface moves toward the canonical catalog composition with
  breadcrumbs, title, left category navigation, toolbar, and product grid;
- zero/nonpositive prices render as `Ціна за запитом`;
- route remains local/explicit-gate only and `noindex`.
<!-- /FP_SUPPLIER_S5_TRANSIENT_QUOTE_LIST_2026_09_06_V1 -->

<!-- FP_SUPPLIER_S5_1_QUOTE_UX_2026_09_06_V1 -->
## Phase S5.1 quote-list UX refinement — 2026-09-06

Manual browser review confirmed the transient quote-list architecture works.
S5.1 refines usability without changing persistence or request transport.

Accepted refinements:

- repeated card click toggles membership: add -> remove -> add;
- a fixed right-rail `ПРОРАХУНОК` shortcut with live count is visible while the
  supplier feature gate is enabled;
- the existing top navigation shortcut remains available;
- quote-list rows gain a small product thumbnail and stronger card hierarchy;
- quote-list content is visually narrower than the full catalog surface;
- the shared communication form is constrained to a practical reading width;
- supplier breadcrumb/title alignment stays inside the same page content bounds;
- no cart/order DB or server quote persistence is introduced.

The right-rail shortcut is an interim quote-list utility, not a checkout/cart
control. The final request still uses the existing communication-request flow.
<!-- /FP_SUPPLIER_S5_1_QUOTE_UX_2026_09_06_V1 -->

<!-- FP_SUPPLIER_S5_2_SAFE_HEADERLESS_QUOTE_ICON_2026_09_07_V1 -->
## Phase S5.2 safe quote icon + product detail — 2026-09-07

S5.2 artifacts v0.1-v0.4 stopped without a surviving source mutation while
trying to identify or rewrite the legacy header/search markup. The v0.4
pre-mutation PHP lint proved that continuing to rewrite `header.php` for a
cosmetic quote utility is not justified.

The accepted S5.2 implementation therefore changes strategy:

- `header.php` is read-only and its exact bytes must remain unchanged;
- the existing supplier navigation item remains the feature-gate signal;
- the old quote text controls and legacy fixed rail are visually suppressed
  through the canonical shell stylesheet rather than risky PHP surgery;
- `forprint-supplier-quote-list.js` creates one static clipboard/list icon only
  when the gated supplier navigation is present;
- browser DOM logic mounts that icon into the actual search band and reuses the
  existing `[data-fp-quote-count]` update contract;
- the in-catalog `На прорахунок` shortcut remains.

Product detail remains the same bounded S5.2 design:

- listing image and title link internally to
  `/supplier-catalog/?product=<external_id>`;
- the existing supplier controller reads the same isolated
  `supplier_catalog_*` tables;
- detail uses available `images_json`, `attributes_json`, and
  `supplier_catalog_variants`;
- detail remains `noindex, nofollow, noarchive`;
- no new router, cart/order DB, supplier-media download, production mutation, or
  second request/conversion flow is introduced.

This is intentionally conservative: the legacy header is not rewritten merely
to move a utility icon.
<!-- /FP_SUPPLIER_S5_2_SAFE_HEADERLESS_QUOTE_ICON_2026_09_07_V1 -->

<!-- FP_SUPPLIER_S5_3_STYLE_ALIGNMENT_2026_09_07_V1 -->
## Phase S5.3 style alignment + quote rail relocation — 2026-09-07

Accepted browser review after S5.2 showed the feature logic is correct, but the
surface still diverges from the primary ForPrint catalog language.

Bounded S5.3 therefore keeps the same isolated supplier data and existing
sessionStorage quote-list flow, but refines presentation only:

- the quote utility leaves the grey search band and becomes a fixed right-side
  rail control with a white clipboard/list icon and live badge;
- the rail control stays hidden until at least one item is present;
- old duplicate top quote controls remain visually suppressed;
- supplier listing cards receive a flatter primary-catalog action bar treatment;
- bare supplier brand lines in the cards/summary are visually removed;
- product detail text changes from `Доступні способи нанесення` to
  `Рекомендовані методи нанесення`;
- product detail gallery thumbnails are aligned vertically on desktop to more
  closely match the main ForPrint product-detail rhythm.

No production mutation, supplier media download, new DB tables, or new request
transport is added in S5.3.
<!-- /FP_SUPPLIER_S5_3_STYLE_ALIGNMENT_2026_09_07_V1 -->

<!-- FP_SUPPLIER_S5_4_TAXONOMY_AND_CANONICAL_STYLE_AUDIT_2026_09_07_V1 -->
## Phase S5.4 taxonomy grouping + canonical style audit — 2026-09-07

S5.3 browser review confirmed that continuing to imitate the primary catalog
with supplier-only card/detail CSS will create unnecessary visual drift.

S5.4 therefore separates two concerns:

1. Safe UX corrections that do not depend on canonical component discovery:
   - supplier categories render from the existing `parent_external_id`
     hierarchy instead of one flat list;
   - parent categories become compact disclosure groups;
   - the selected child automatically opens its parent group;
   - the product-detail gallery explicitly places thumbnails in the left column
     and the main image in the right column on desktop;
   - characteristics receive a temporary bounded width adapter;
   - the quote-list rail control moves to the upper part of the right rail and
     remains hidden when the list is empty.

2. Read-only canonical component discovery:
   - inspect `forprint-product-cards.css` and `forprint-product-detail.css`;
   - inspect PHP templates that actually use their `fp-*` classes;
   - report exact reusable class families and registration evidence;
   - do **not** add another supplier-specific card/detail imitation layer.

The next style pass must reuse the canonical component owners/classes where the
evidence is unambiguous. A small supplier adapter may remain only for supplier
taxonomy, quote-list state, and data-specific differences.

No production mutation, supplier DB write, new order DB, supplier-media
download, or new request transport is introduced.
<!-- /FP_SUPPLIER_S5_4_TAXONOMY_AND_CANONICAL_STYLE_AUDIT_2026_09_07_V1 -->

<!-- FP_SUPPLIER_S5_5_CANONICAL_COMPONENTS_SORT_SEARCH_2026_09_07_V1 -->
## Phase S5.5 canonical components + sort + search bridge — 2026-09-07

S5.4 established canonical owners for the main catalog:
`include/goodsGridItem.php`, `forprint-product-cards.css`, `product.php`, and
`forprint-product-detail.css`.

S5.5 stops extending the supplier-only S5.3 card/detail imitation and binds the
supplier markup to the actual canonical class families resolved from those
owners. Supplier CSS remains only as an adapter for supplier taxonomy, quote
state, toolbar differences, and partner-data presentation.

This phase also adds name/price sorting, 12/24/48 display limits, a compact
characteristics section band, refined quote-list wording, and supplier-product
results inside the existing global search suggestion surfaces under the group
`Продукція для брендування`.

The temporary supplier search JSON mode uses the existing isolated supplier
tables and existing supplier route. It adds no persistent search index and no
new order database. The bridge is active only when the existing supplier
feature-gate navigation is present, so production remains disabled by default.

No production mutation, supplier DB write, media download, new order DB, or new
request/conversion transport is introduced.
<!-- /FP_SUPPLIER_S5_5_CANONICAL_COMPONENTS_SORT_SEARCH_2026_09_07_V1 -->
