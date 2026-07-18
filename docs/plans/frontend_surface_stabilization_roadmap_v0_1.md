# Frontend Surface Stabilization Roadmap v0.1

## Goal

Convert the legacy frontend from globally coupled layouts into four independently manageable surfaces while preserving current routes, data and business behaviour.

This is a progressive refactor, not a pre-release rewrite.

## Four managed surfaces

### 1. Product page

The product page is the reference implementation.

Planned work:

- inventory page templates, partials, CSS, JavaScript and controller data;
- identify remaining global selectors;
- define surface markers and asset-loading rules;
- establish desktop, tablet and mobile contracts;
- preserve isolated product-detail and communication components.

### 2. Home page

Planned work:

- inventory every home section;
- separate page layout from shared product cards;
- isolate banners, product groups, navigation and search strip;
- remove uncontrolled fixed positioning;
- introduce predictable responsive behaviour per section.

### 3. Catalogue and filters

Planned work:

- isolate filter layout, product grid and sort controls;
- preserve current query and ordering behaviour;
- separate desktop sidebar and mobile filter interaction;
- make grid columns predictable;
- prevent overlap during resize and browser zoom.

### 4. Search results and search interaction

Planned work:

- isolate result-page layout from catalogue layout;
- retain shared product cards;
- keep suggestions, history and matching in dedicated assets;
- distinguish “show all results” from direct product navigation;
- improve ranking conservatively.

## Execution sequence

### Phase 0 — Read-only inventory

For each surface record:

- route and controller;
- page template;
- included partials;
- CSS and JavaScript assets;
- global stylesheet selectors;
- shared components;
- server-side data contract;
- observed breakpoints.

Deliverable: source map and dependency matrix.

### Phase 1 — Product-page reference contract

Use the product page to define:

- page-level asset naming;
- surface markers;
- shared-component rules;
- smoke-test structure;
- screenshot widths;
- accepted legacy boundaries.

### Phase 2 — Home-page isolation

Create a home-specific layout layer and remove its primary-layout dependence on global positioning rules.

### Phase 3 — Catalogue and filters isolation

Create an independent catalogue layout while retaining shared cards and current sorting/filter semantics.

### Phase 4 — Search-surface isolation

Create an independent results layout and finish controlled search suggestions and history.

### Phase 5 — Responsive harmonization

Review all four surfaces at:

- 1920 px;
- 1440 px;
- 1280 px;
- 1024 px;
- 768 px;
- 390 px.

## Definition of done per surface

A surface is complete when:

- its page-level rules live in an explicitly named asset;
- shared components remain shared;
- primary layout no longer depends on unknown global positioning;
- changing the surface does not unexpectedly alter the other three;
- route and syntax checks pass;
- the width matrix is reviewed;
- remaining legacy dependencies are documented.

## Out of scope for the first pass

- framework migration;
- PHP routing replacement;
- database redesign;
- redesign of every secondary page;
- full deletion of `style.css`;
- visual perfection at every browser zoom level.

<!-- FP_ROADMAP_DUAL_TRACK_REDIRECT_V0_1 -->
## Roadmap redirect — 2026-07-18

The controlled legacy home surface has reached a sufficient stabilization checkpoint for publication work.

Deep byte-identical extraction is no longer the default next step.

Active continuation:

1. document current legacy ownership;
2. establish an isolated modern preview;
3. audit and resolve actual legacy publication blockers;
4. design the modern homepage through owner-reviewed browser iterations;
5. switch `/` only after explicit acceptance.

Detailed plan: `docs/plans/legacy_publication_and_modern_frontend_plan_v0_1.md`.
