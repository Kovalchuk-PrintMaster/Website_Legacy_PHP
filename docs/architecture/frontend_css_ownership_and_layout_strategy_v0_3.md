# ForPrint frontend CSS ownership and layout strategy v0.3

**Status:** active working policy
**Date:** 2026-07-20
**Supersedes:** `frontend_css_ownership_and_layout_strategy_v0_2.md`
**Scope:** public ForPrint website frontend
**Purpose:** define one maintainable owner for global geometry, shared shell, homepage, product presentation, communication UI, and search suggestions while the inherited stylesheet remains a temporary fallback.

## 1. Motivation

The public frontend inherited a large `style.css` with broad selectors, fixed dimensions, and multiple historical breakpoint layers. During modernization, project-owned `forprint-*.css` files were introduced to isolate new behavior.

A second risk then appeared: accepted visual corrections were repeatedly appended as new versioned override blocks. The page improved visually, but one component could be controlled by several generations of selectors at once. This made the cascade difficult to reason about and allowed older arrow, spacing, and positioning rules to reappear.

The canonical policy is therefore:

> A component may have only one active project-owned geometry contract in its owner stylesheet.

Temporary refinements are permitted only during a short browser-review cycle. Before the component is accepted or work moves to the next feature, the refinement must be merged into the canonical block and the superseded block removed.

## 2. Legacy boundary

The following remain inherited compatibility assets until a separate migration removes them:

- `base/templates/default/assets/css/style.css`
- `base/templates/default/assets/css/lk.css`
- `base/templates/default/assets/css/animate.css`
- third-party vendor styles

Rules:

1. Do not add new project layout or component features to `style.css`.
2. Do not copy large legacy selector blocks into modern files.
3. Do not rewrite global legacy rules without a scoped migration checkpoint.
4. A migrated component must explicitly neutralize only the legacy properties that conflict with its accepted presentation.
5. `animate.css` is a vendor file and must not be used as a project component owner.

## 3. Canonical loading order

The intended transitional order is:

1. normalization and vendor CSS;
2. inherited `style.css` fallback;
3. `forprint-layout.css`;
4. `forprint-shell.css`;
5. shared component styles:
   - `forprint-product-cards.css`
   - `forprint-product-detail.css`
   - `forprint-product-communication.css`
   - `forprint-search-suggestions.css`
6. controller-owned surface styles such as `forprint-home.css`.

Modern global styles use one registration path. Surface files remain controller-owned where practical. Hardcoded duplicate `<link>` tags are not an accepted long-term loading mechanism.

## 4. Ownership map

### `forprint-layout.css`

Owns only the global geometry contract:

- usable page width;
- fixed right-rail reservation;
- content ceiling;
- horizontal side space;
- shared section spacing;
- `.fp-layout-page`;
- `.fp-layout-full`;
- `.fp-layout-container`;
- shared header/footer inner width.

It does not own component appearance.

### `forprint-shell.css`

Owns the shared site shell:

- public header composition;
- logo and slogan;
- years marker;
- contact row;
- primary navigation;
- responsive header fallback;
- shared footer shell when migrated;
- shared fixed-rail integration when migrated.

It must not redefine the global width formula already owned by `forprint-layout.css`.

### `forprint-home.css`

Owns only the homepage surface under `[data-fp-surface="home"]`:

- hero slider composition;
- hero text and media geometry;
- hero controls and pagination;
- home product groups;
- about block;
- advantages block;
- news block;
- home-only spacing;
- homepage responsive behavior.

### `forprint-product-cards.css`

Owns reusable product-card and product-grid presentation:

- card geometry;
- image ratio;
- title and description rhythm;
- exact/range/request price presentation;
- action area;
- catalog and related-product grids.

It does not own page width or product-detail composition.

### `forprint-product-detail.css`

Owns the single-product surface:

- gallery;
- main product information;
- exact/range/request price output;
- characteristics;
- optional tabs;
- related-product section;
- product-detail responsive behavior.

### `forprint-product-communication.css`

Owns product enquiry and communication UI:

- request buttons;
- request modal;
- request form;
- validation/status presentation;
- communication-specific responsive behavior.

### `forprint-search-suggestions.css`

Owns only search suggestion positioning and presentation.

## 5. Global layout contract

The current working geometry uses these semantic variables:

```css
:root {
    --fp-layout-content-ceiling: 144rem;
    --fp-layout-side-space: 10vw;
    --fp-layout-rail-width: 5rem;
    --fp-layout-section-space: clamp(3rem, 6vw, 6rem);
}
```

The exact values may be tuned after visual review. Ownership must not change:

- `forprint-layout.css` owns the variables and width formula;
- header, hero, product grids, catalog, product detail, and footer reuse that contract;
- a fixed right rail is reserved once, not independently by every component;
- full-width background bands may extend across the page, while their content remains aligned to `.fp-layout-container`.

## 6. Header policy

The modern header uses explicit Grid composition rather than inherited flex dimensions.

Required behavior:

- the header container aligns with the same global geometry as homepage content;
- the logo, slogan, years marker, contacts, and navigation remain inside the usable page width;
- contact fields never overlap;
- navigation switches composition before an item, including `КОНТАКТИ`, leaves the container;
- the responsive fallback is triggered by composition failure, not by device marketing names;
- service nodes such as overlay, sidebar, hidden menu, and callback remain outside the content-column calculation.

## 7. Homepage hero policy

The homepage hero uses one canonical block inside `forprint-home.css`.

Required behavior:

- the outer hero uses the shared layout width;
- text is top-aligned in the left column;
- the media area fills the right column;
- images use `object-fit: cover` and `object-position: center center`;
- cropping is balanced rather than anchored only to the top edge;
- only one visible previous control and one visible next control exist;
- inherited SVG arrows and Swiper default glyphs are hidden when project-owned CSS arrows are used;
- controls are vertically centered and inset from the left/right edges;
- fraction pagination is positioned over the media in the lower-right corner;
- the next homepage block has an explicit small separation from the hero.

## 8. Cascade and selector policy

1. Modern selectors use the `fp-` prefix.
2. Surface rules are scoped by a modern root where useful.
3. Use BEM-like naming.
4. Avoid global element selectors.
5. Avoid IDs for styling.
6. Avoid inline presentation attributes.
7. Keep specificity predictable.
8. `!important` is allowed only as a documented temporary legacy-isolation measure.
9. Do not keep `v0.5`, `v0.6`, `v0.7` component blocks active together after acceptance.
10. Comments describe ownership and intent, not a permanent history of every visual iteration.

## 9. Responsive validation baseline

Every accepted global or component layout change is checked at approximately:

- 1920 px;
- 1600 px;
- 1366 px;
- 1024 px;
- 768 px;
- 390 px.

A layout accepted on one monitor is not complete while another baseline width has overflow, overlap, unreachable controls, or unreadable content.

## 10. Migration workflow

1. Start from a known Git state.
2. Inspect the actual DOM, loaded assets, and computed styles.
3. Identify the canonical owner.
4. Make a small scoped edit.
5. Refresh and capture a screenshot.
6. Measure geometry when visual judgment is ambiguous.
7. Consolidate temporary refinements into one canonical block.
8. Run syntax and focused checks.
9. Review all baseline widths.
10. Commit only accepted files with exact staging.
11. Update architecture/status documentation when ownership or runtime behavior changes.

## 11. Definition of an accepted migrated component

A component is accepted only when:

- its main structure has project-owned semantic classes;
- one named stylesheet owns its presentation;
- its outer geometry uses the shared layout contract;
- no earlier project-owned version block is still overriding it;
- legacy dependence is explicit and bounded;
- baseline widths remain usable;
- a focused inspection or documented manual review exists;
- the work is recorded in a clean checkpoint.

## 12. Current known debt

- inherited `style.css` remains globally available;
- some legacy classes are retained in markup for compatibility;
- the complete mobile/tablet visual pass is still pending;
- slider media upload/renaming/compression is not yet migrated to the homepage media namespace;
- PHP 8.2 warning cleanup remains separate from CSS architecture;
- final publication/deployment has not occurred.
