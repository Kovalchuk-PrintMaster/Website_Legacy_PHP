# ForPrint admin UI modernization plan v0.1

**Status:** active
**Date:** 2026-08-23
**Depends on:** `docs/decisions/2026-08-23__canonical_admin_css_ownership_and_migration_order.md`

## Goal

Modernize the inherited administration interface without introducing a second
unbounded CSS cascade and without changing public/business behavior.

## Baseline evidence

Read-only audit result:

```text
classification=ADMIN_UI_READ_ONLY_INVENTORY_COMPLETE

admin files=127
admin CSS files=8
inline style attributes=7
inline style tags=2
inline event attributes=0
project-owned admin classes in templates=191
table-specific branches=21
root admin JS files=7

Goods visual-pattern evidence=120
Media Processing visual-pattern evidence=90
```

The audit explicitly concluded:

```text
public_template_mutation=NOT_JUSTIFIED_BY_THIS_AUDIT
database_migration=NOT_IMPLIED
```

## Phase 1 — ownership consolidation

1. Treat `main.css` as legacy fallback only.
2. Treat `forprint-admin.css` as the shared project-owned admin foundation.
3. Inventory duplicate selectors between `main.css`, `forprint-admin.css`,
   `forprint-admin-ui.css` and specialized files.
4. Extract shared visual tokens/base controls from the accepted Goods and Media
   Processing patterns.
5. Remove only proven duplicate modern shell ownership.
6. Do not perform a broad visual redesign in this phase.

Definition of done:

```text
one documented owner for shared admin primitives
duplicate shell/sidebar owner list known
no new generic fp-* rules added to main.css
Goods and Media Processing regression checks green
```

## Phase 2 — first bounded migration group

Surfaces:

```text
System Settings
Visual Assets
Header settings
Footer settings
```

Implementation sequence:

1. inspect current DOM and functional controls;
2. add/normalize stable semantic `fp-admin-*` classes only where needed;
3. use shared card/grid/field/action primitives from `forprint-admin.css`;
4. preserve surface-specific structure where it is meaningful;
5. explicitly neutralize bounded `main.css` conflicts;
6. validate at desktop/tablet/mobile widths;
7. validate save/edit/add/navigation actions;
8. commit accepted files with exact staging.

Visual Assets is deliberately included because the audit found controller
branches but no established project-owned visual pattern.

## Phase 3 — product/category/filter editors

Migrate:

```text
Goods list/index composition that is not already owned
categories/groups
filters/filter groups
ordering controls
related editor utilities
```

Do not rewrite the accepted Goods create/edit surface merely for consistency.
Reuse its tokens/patterns while preserving its canonical owner.

## Phase 4 — information/news/marketing editors

Migrate:

```text
information/content cards
contacts/about editors
news
sales/promotional editors
marketing-oriented admin surfaces
```

TinyMCE integration is preserved unless a separate editor-functionality defect
is proven.

## Phase 5 — utility/technical screens

Migrate lower-frequency technical/admin utilities after business-critical
editors are stable.

## Phase 6 — responsive/accessibility closure

Review at approximately:

```text
1920
1600
1366
1024
768
390 px
```

Check:

```text
horizontal overflow
sidebar/topbar composition
focus visibility
label/control association
touch target usability
modal/gallery reachability
long text wrapping
validation/error visibility
```

## Phase 7 — legacy CSS reduction

Only after migrated surfaces pass acceptance:

1. identify `main.css` blocks with no remaining runtime owner;
2. remove dead project-specific version/override layers in bounded commits;
3. reduce unnecessary `!important`;
4. remove remaining inline presentation only where replacement ownership exists;
5. keep third-party/vendor CSS untouched.

Complete removal of `main.css` is not a prerequisite for this modernization.

## Working-tree policy

The repository may remain dirty during development.

Admin modernization scripts/checks must:

- never require a clean tree merely for inspection;
- record the active committed baseline;
- exact-stage only the accepted migration files;
- preserve unrelated modified/untracked/deleted paths;
- fail closed if an intended target already contains unrelated edits.

## Release policy

Admin modernization is internal and must not trigger a full production sync.

Any production publication later uses the normal exact-file release process and
only after local/preview acceptance.
