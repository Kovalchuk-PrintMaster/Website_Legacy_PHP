# ForPrint frontend CSS ownership and layout strategy v0.1

**Status:** active working policy  
**Scope:** public ForPrint website frontend  
**Purpose:** define how the modern visual layer is built, loaded, migrated, tested, and maintained while the legacy CSS is gradually removed.

## 1. Strategic direction

The website is moving from inherited legacy CSS to a project-owned modern CSS layer.

The modern layer uses files named `forprint-*.css` and selectors prefixed with `fp-`.

Legacy styles remain temporarily available only as a fallback for surfaces that have not yet been migrated. They are not the foundation for new work.

The migration principle is:

> Every accepted frontend change must reduce or at least not increase dependence on legacy CSS.

## 2. Current legacy boundary

The following files are treated as legacy or inherited until explicitly migrated:

- `base/templates/default/assets/css/style.css`
- `base/templates/default/assets/css/lk.css`
- `base/templates/default/assets/css/animate.css`
- any other non-`forprint-*` project stylesheet without an explicit modern ownership record

Rules:

1. Do not add new product or layout features to `style.css`.
2. Do not copy large legacy selector blocks into modern files.
3. Do not globally rewrite legacy selectors without a clean checkpoint and a scoped migration plan.
4. Legacy rules may be edited only for:
   - safe removal after migration;
   - a narrowly scoped emergency compatibility fix;
   - temporary isolation needed to let a modern owner take control.
5. Every temporary legacy edit must be recognizable and removable.

## 3. Modern CSS ownership map

### `forprint-layout.css`

Owns the global geometry contract:

- usable page width;
- fixed right rail reservation;
- maximum content width;
- horizontal gutters;
- shared section spacing;
- full-width versus contained layout;
- core responsive layout variables;
- reusable layout utilities.

It must not own product-card appearance, home content styling, forms, or page-specific decoration.

### `forprint-home.css`

Owns only the home surface:

- home hero inner composition;
- home product-group composition;
- about block;
- advantages block;
- news block;
- home-only section spacing;
- home responsive behavior.

Full-width backgrounds may belong to home sections, but their inner content must use the shared layout contract.

### `forprint-product-cards.css`

Owns reusable product-card and product-grid presentation:

- product-card structure;
- card image ratio;
- card title, description, price, and action area;
- reusable catalog and related-product grids;
- shared card responsive behavior.

It does not own the outer page width.

### `forprint-product-detail.css`

Owns the single-product surface:

- gallery;
- product information;
- price and feature presentation;
- product tabs;
- related-product section;
- product-detail responsive behavior.

Existing local width constants must be gradually replaced by shared layout variables where appropriate.

### `forprint-product-communication.css`

Owns product communication and request interfaces:

- request forms;
- communication panels;
- modal presentation related to product enquiries;
- their responsive behavior.

### `forprint-search-suggestions.css`

Owns only search suggestion presentation and positioning.

### Future modern owners

New files are added by responsibility, not for every small block. Likely owners:

- `forprint-shell.css` — header, footer, fixed right rail, shared search bar;
- `forprint-catalog.css` — catalog page composition and filters;
- dedicated surface files only when a surface becomes large enough to justify clear ownership.

## 4. Global layout contract

The target layout foundation uses shared custom properties.

Initial contract:

```css
:root {
    --fp-layout-content-max: 1600px;
    --fp-layout-gutter: clamp(18px, 3vw, 64px);
    --fp-layout-rail-width: 80px;
    --fp-layout-section-space: clamp(48px, 6vw, 96px);
}
```

Responsive rail example:

```css
@media (max-width: 1600px) {
    :root {
        --fp-layout-rail-width: 60px;
    }
}
```

Core utilities:

```css
.fp-layout-page {
    width: calc(100% - var(--fp-layout-rail-width));
}

.fp-layout-full {
    width: 100%;
}

.fp-layout-container {
    box-sizing: border-box;
    width: 100%;
    max-width: var(--fp-layout-content-max);
    margin-inline: auto;
    padding-inline: var(--fp-layout-gutter);
}
```

The exact initial values may be tuned visually. The contract and ownership must remain stable.

## 5. Full-width and contained sections

A section may have a full-width background while its content remains aligned.

Correct pattern:

```html
<section class="fp-layout-full fp-home-section">
    <div class="fp-layout-container">
        ...
    </div>
</section>
```

Expected full-width elements:

- global search strip;
- promotional strip;
- slider background;
- footer background;
- fixed right rail.

Expected contained content:

- header inner content;
- slider text and image composition;
- product grids;
- about content;
- advantages;
- news;
- footer columns;
- catalog content;
- product-detail content.

## 6. Responsive design rules

1. Use fluid values such as `clamp()`, `min()`, `max()`, `minmax()`, percentages, and CSS Grid.
2. Avoid fixed content widths such as `755px` unless the value is a deliberate component maximum.
3. Use `minmax(0, 1fr)` for flexible grid columns.
4. Use `aspect-ratio` and `object-fit: cover` for predictable media.
5. Use `max-width` to prevent excessive stretching on large displays.
6. Breakpoints are introduced when composition fails, not merely because of a device name.
7. Baseline visual checks:
   - 1920 px;
   - 1600 px;
   - 1366 px;
   - 1024 px;
   - 768 px;
   - approximately 390 px.
8. A layout accepted at one resolution is not considered complete until the other baseline widths remain usable.

## 7. Selector and naming policy

Modern selectors use the `fp-` prefix.

Preferred structure:

```text
fp-layout-*
fp-site-*
fp-home-*
fp-catalog-*
fp-product-*
```

Rules:

- use BEM-like component naming;
- avoid global element selectors;
- avoid IDs for styling;
- avoid inline style attributes;
- do not base new selectors on legacy class names;
- keep selector specificity low and predictable;
- scope surface-specific rules by a modern surface root where useful;
- use `!important` only as a documented temporary legacy-isolation measure.

## 8. CSS loading policy

Target order:

1. normalization and third-party vendor CSS;
2. temporary legacy fallback;
3. `forprint-layout.css`;
4. modern shared shell and reusable component CSS;
5. page or surface CSS added by the active controller;
6. narrowly scoped emergency overrides, only when explicitly documented.

Current technical debt:

- legacy `style.css` is registered globally;
- some modern `forprint-*.css` files are hardcoded directly in `include/header.php`;
- page CSS such as `forprint-home.css` is added through the controller style array.

Target cleanup:

- modern global files should have one explicit registration location;
- page-specific files should remain controller-owned;
- hardcoded scattered `<link>` tags should be removed after the load order is stabilized;
- `style.css` should eventually be removed from the global registration list.

## 9. Migration workflow

Each migrated surface follows this sequence:

1. Start from a clean Git checkpoint.
2. Capture current visual behavior and relevant source.
3. Identify the modern CSS owner.
4. Add or use `fp-*` classes.
5. Implement the modern presentation after the legacy fallback.
6. Check the baseline viewport widths.
7. Remove the migrated block’s dependency on legacy classes.
8. Verify that no unrelated surface changed.
9. Add or update one focused inspection.
10. Commit the migration as a separate checkpoint.
11. Update this ownership document when responsibility changes.

Preferred feedback cycle:

```text
small edit → browser refresh → screenshot → small correction → check → commit
```

Avoid:

```text
large generator → broad rewrite → difficult rollback → repeated generator revision
```

## 10. Definition of a migrated surface

A surface is considered migrated only when:

- its main structure uses project-owned `fp-*` classes;
- its visual behavior is owned by one or more named `forprint-*.css` files;
- its outer geometry uses the shared layout contract;
- it does not require legacy layout classes for accepted appearance;
- it remains usable at baseline viewport widths;
- a focused inspection or documented manual check exists;
- the migration has a clean Git checkpoint.

## 11. Maintenance rules

- One CSS responsibility has one clear owner.
- Shared variables are changed centrally, not copied into multiple files.
- Page-specific styling must not leak globally.
- New CSS files require a clear ownership reason.
- Small files are preferred, but responsibility is more important than arbitrary line limits.
- Repeated constants should become custom properties.
- Obsolete rules are removed after migration rather than left indefinitely.
- Documentation and actual load order must not diverge.

## 12. Near-term execution order

1. Add and load `forprint-layout.css`.
2. Establish one global content-width and gutter contract.
3. Migrate header inner geometry.
4. Migrate footer inner geometry.
5. Align all home content sections.
6. Preserve full-width search, promotional backgrounds, and fixed right rail.
7. Migrate catalog geometry.
8. Normalize product-detail outer geometry.
9. Move remaining shared shell rules into `forprint-shell.css`.
10. Remove global dependency on `style.css` after the required surfaces are migrated.

## 13. Decision log

### v0.1

- Adopt `forprint-*.css` as the project-owned modern CSS layer.
- Treat legacy styles as temporary fallback, not the development foundation.
- Introduce `forprint-layout.css` as the owner of global geometry.
- Use one shared content container contract across site surfaces.
- Keep full-width backgrounds separate from contained inner content.
- Migrate incrementally with visual feedback and Git checkpoints.