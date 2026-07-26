# ForPrint Website frontend working state — 2026-07-20

**ID:** `FP-WEB-STATUS-2026-07-20-001`
**Status:** active working checkpoint
**Publication/deployment:** not performed

## 1. Scope of this snapshot

This snapshot records the current working state after global layout, header, homepage hero, product-price presentation, CSS ownership, and local preview-service work.

It is not a publication record and not a declaration of final responsive acceptance.

## 2. Global frontend geometry

Current direction:

- shared `fp-layout-*` contract;
- fluid side space;
- fixed right-rail reservation;
- aligned header and homepage content;
- full-width background bands separated from contained inner content.

Primary owner:

```text
base/templates/default/assets/css/forprint-layout.css
```

## 3. Shared header

Implemented working behavior:

- project-owned header composition;
- logo plus configurable slogan;
- years marker moved into the header;
- configurable years caption;
- email, phone, and callback separated;
- primary navigation aligned to the functional header zone;
- responsive fallback before contact/navigation overflow.

Primary owners:

```text
base/templates/default/include/header.php
base/templates/default/assets/css/forprint-shell.css
base/core/base/settings/Settings.php
base/core/base/settings/internal_settings.php
```

## 4. Homepage structure

The homepage is divided into controlled components under:

```text
base/templates/default/surfaces/home/
```

Extracted components:

- hero;
- product groups;
- about;
- advantages;
- feedback;
- news;
- search.

The home surface is scoped by `data-fp-surface="home"`.

## 5. Homepage hero

Working behavior:

- four visible `sales` rows;
- linked promotional slides;
- compact two-column geometry;
- top-aligned text;
- image cover-crop centered in both axes;
- project-owned previous/next controls;
- fraction pagination;
- autoplay with pause-on-hover;
- reduced-motion handling.

Data migration was not required for the current `sales` table.

Pending:

- dedicated homepage media namespace;
- automatic logical renaming;
- conversion/compression profile;
- final responsive review;
- accessibility labels and focused inspection.

## 6. Product pricing presentation

Working price modes:

```text
exact
range
request
```

Current behavior:

- exact value with optional old/new price;
- range output such as `400–800 грн.`;
- request fallback such as `Ціна за запитом`;
- optional custom request text;
- card rows preserve visual rhythm;
- product detail and reusable cards use a shared price-state resolver.

Cart/checkout remains deferred.

## 7. CSS ownership cleanup

Current project-owned styles:

```text
forprint-layout.css
forprint-shell.css
forprint-home.css
forprint-product-cards.css
forprint-product-detail.css
forprint-product-communication.css
forprint-search-suggestions.css
```

Architecture direction:

- no new product/layout work in legacy `style.css`;
- one canonical owner per component;
- temporary refinements consolidated before moving on;
- `animate.css` remains vendor-owned;
- modern CSS loading centralized where possible.

## 8. Local preview runtime

A local systemd service is used on `s01`:

```text
forprint-website-preview.service
```

Runtime:

```text
/usr/bin/php8.2
127.0.0.1:8098
document root: base/
```

Validation observed:

- service active;
- service enabled;
- listener present;
- five repeated HTTP checks returned 200;
- no fatal/startup errors in the inspected journal window.

Non-fatal PHP 8.2 `Undefined array key` warnings remain.

## 9. Known technical debt

- inherited `style.css` remains active;
- PHP 8.2 warning cleanup;
- homepage media pipeline;
- complete 1920/1600/1366/1024/768/390 visual pass;
- final CSS asset-registration audit;
- exact staging and working checkpoint commit;
- package manifest and generated documentation ZIP refresh.

## 10. Next stage

Recommended order:

1. document and validate the canonical frontend state;
2. finish homepage slider media upload pipeline;
3. perform responsive acceptance;
4. address PHP 8.2 warning baseline;
5. finish deferred catalog/product sorting and filtering;
6. create a controlled Git checkpoint;
7. continue the next homepage or catalog component.
