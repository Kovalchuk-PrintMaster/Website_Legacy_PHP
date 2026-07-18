# Frontend Visual System v0.1

**Document ID:** `FP-WEB-VISUAL-001`
**Status:** active baseline, partially provisional
**Scope:** home, catalog, search and product surfaces

## Purpose

Create one controlled visual language for the website so that headings, buttons, tabs, cards, menus, search, hover states and responsive layouts do not evolve independently.

This document is the canonical source for new frontend presentation work. Existing legacy values may remain temporarily, but every touched component must move toward these rules.

## Frontend profiles

The future presentation switch uses named profiles:

| Machine key | Human meaning | Status |
|---|---|---|
| `legacy` | Existing legacy interface | fallback until controlled interface acceptance |
| `controlled_v1` | Structurally modernized and centrally managed interface | planned primary profile |
| `future_redesign` | Reserved profile for later visual redesign | reserved |

Rollback must be performed through the profile configuration, not by manually undoing unrelated code.
Profile selection is environment-backed:

- configuration key: `FP_WEB_FRONTEND_PROFILE`;
- allowed values: `legacy`, `controlled_v1`, `future_redesign`;
- resolver owner: `base/core/user/controllers/BaseUser.php`;
- missing, blank or unsupported values fall back to `legacy`;
- `.env.website.local` is loaded by the existing preview Make workflow;
- profile rollback is performed by changing the environment value and restarting the application process.

## Canonical core palette

The core grayscale palette contains exactly five colors:

| Token | RGB | Hex | Intended role |
|---|---:|---:|---|
| `dark-graphite` | `rgb(15 15 15)` | `#0F0F0F` | darkest menus, strong controls, primary text where appropriate |
| `medium-graphite` | `rgb(35 35 35)` | `#232323` | secondary dark surface and dark hover state |
| `light-gray` | `rgb(225 225 225)` | `#E1E1E1` | light controls, tabs, search and neutral surfaces |
| `gray` | `rgb(200 200 200)` | `#C8C8C8` | borders, hover changes and secondary neutral surfaces |
| `dark-gray` | `rgb(150 150 150)` | `#969696` | stronger neutral separators, disabled or selected states |

Utility values such as white, transparent and inherited text color are not counted as core palette colors, but they must also be referenced through semantic tokens where practical.

### Required CSS token form

New CSS must use custom properties rather than scattered literals:

```css
:root {
    --fp-color-dark-graphite: rgb(15 15 15);
    --fp-color-medium-graphite: rgb(35 35 35);
    --fp-color-light-gray: rgb(225 225 225);
    --fp-color-gray: rgb(200 200 200);
    --fp-color-dark-gray: rgb(150 150 150);
}
```

### Color normalization rule

New or modernized CSS must not introduce equivalent colors in mixed notation:

- no duplicate `#...`, `rgb(...)`, `rgba(...)` and named-color forms for the same role;
- no `lightgrey`, `gray`, `black` or similar names inside components;
- no component-owned near-duplicate grays;
- no direct color literals when a semantic token exists.

Existing legacy colors will be inventoried and migrated gradually rather than replaced globally in one unsafe operation.

## Semantic color roles

Exact state mapping may be refined during surface implementation, but only canonical tokens may be used.

Initial role model:

- dark navigation surface: `dark-graphite`;
- dark navigation hover: `medium-graphite`;
- light control or tab surface: `light-gray`;
- light control hover: `gray`;
- stronger neutral selected/disabled state: `dark-gray`;
- product card neutral surface: semantic card token based on the canonical palette;
- search surface: semantic search token based on the canonical palette.

## Typography

The current typeface inventory must be audited before final selection.

Rules:

- maximum permitted families in the controlled interface: five;
- working target: two or three;
- preferred roles:
  - body and form text;
  - headings and section titles;
  - optional accent or technical role;
- the same semantic role must use the same family, weight, size range and color across all four surfaces;
- components must use typography tokens, not hard-coded font stacks;
- a section heading on home, catalog, search and product must come from one shared heading role;
- fallback stacks must be documented.

No font family is declared canonical yet. This remains `pending_inventory`.

## Buttons and interactive controls

Every button must declare a semantic variant:

- primary;
- secondary;
- neutral;
- text/link;
- destructive, where required.

Rules:

- one variant has one default, hover, focus, active and disabled model;
- opposite hover directions for visually identical buttons are prohibited;
- buttons with the same business meaning must look and behave the same across surfaces;
- hover must not move surrounding layout;
- visible keyboard focus is mandatory;
- button text and icon alignment must be shared, not locally improvised.

## Tabs

Product-detail tabs are the current reference interaction.

Rules:

- inactive tabs must not rely on pure white as their only state;
- hover and active states use subtle changes within the canonical grayscale palette;
- identical tab behavior must be reused where tabs appear elsewhere;
- the exact active/hover token mapping will be finalized during controlled product-tab review;
- missing tabs must be treated as a data or rendering issue, not hidden with CSS.

## Cards

Rules:

- one shared product-card foundation;
- home, catalog, search and related-product cards may extend but not fork the core contract;
- hover image scaling must use one standard transform;
- provisional image hover scale: no more than `1.04`;
- cards must not change their external dimensions on hover;
- card title, price and action hierarchy must remain consistent;
- mobile cards must avoid horizontal overflow.

## Motion and effects

Initial shared values:

- standard transition duration: `160ms`;
- standard easing: `ease`;
- hover effects must be subtle;
- animations must respect `prefers-reduced-motion`;
- no decorative effect may hide or delay core content;
- no surface may invent an unrelated transition duration without documenting the reason.

## Surface ownership

Every controlled surface must have one semantic root:

```text
[data-fp-surface="home"]
[data-fp-surface="catalog"]
[data-fp-surface="search"]
[data-fp-surface="product"]
```

New surface rules must be scoped below the corresponding root.

Do not add new generic geometry classes such as `.left`, `.right`, `.half` or `.third` for controlled work.

## CSS architecture

Target entrypoints:

```text
assets/css/shared/tokens.css
assets/css/shared/base.css
assets/css/shared/components/*.css
assets/css/surfaces/home.css
assets/css/surfaces/catalog.css
assets/css/surfaces/search.css
assets/css/surfaces/product.css
```

Rules:

- no new surface-specific rules in the giant legacy `style.css`;
- shared components require explicit shared ownership;
- avoid ID selectors for styling;
- `!important` requires a documented legacy-override reason;
- inline `style` attributes must not be introduced in controlled templates;
- one component must not depend on unrelated page markup.

## JavaScript architecture

Target entrypoints:

```text
assets/js/shared/bootstrap.js
assets/js/surfaces/home.js
assets/js/surfaces/catalog.js
assets/js/surfaces/search.js
assets/js/surfaces/product.js
```

Rules:

- use data attributes for behavior hooks;
- do not bind new behavior to visual utility classes;
- avoid global `window` state unless explicitly owned;
- surface JavaScript must tolerate absent optional components;
- legacy and controlled profiles must not initialize the same component twice.

## Responsive contract

- mobile-first controlled rules;
- content-driven breakpoints;
- no fixed-width dependency for the main layout;
- no horizontal page overflow;
- touch targets must remain usable;
- typography must stay readable without browser zoom;
- images must scale inside their containers;
- desktop behavior must remain recognizable until redesign work begins.

## Accessibility baseline

- semantic landmarks where applicable;
- meaningful image `alt`;
- visible keyboard focus;
- form labels and error relationships;
- sufficient contrast;
- reduced-motion support;
- controls must remain usable without hover.

## Change governance

When a new visual rule is proposed:

1. map it to an existing semantic token or role;
2. extend the machine-readable file when a new role is truly required;
3. implement it in the correct shared or surface-owned file;
4. add focused validation;
5. verify all affected surfaces;
6. update this document when the contract changes.
