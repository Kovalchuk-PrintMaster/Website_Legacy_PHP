# Decision: canonical admin CSS ownership and migration order

**Date:** 2026-08-23
**Status:** accepted working architecture decision
**Scope:** legacy PHP administration interface under `base/core/admin/`
**Purpose:** establish one maintainable owner per admin UI concern before bounded visual migration

## Context

The read-only admin ownership audit at Git checkpoint
`e6bb814ab0c473dde3b805c6cfcaeb5395f2dfef` found:

```text
admin files: 127
admin CSS files: 8
admin PHP files checked: 31
PHP syntax failures: 0

main.css:
  104862 bytes
  4648 lines
  269 classes
  101 fp-* classes
  52 media queries
  1006 !important declarations

forprint-admin.css:
  14840 bytes
  729 lines
  77 classes
  65 fp-* classes
  9 media queries
  31 !important declarations

forprint-admin-ui.css:
  7797 bytes
  348 lines
  26 classes
  23 fp-* classes
  primarily shell/media-processing patterns

forprint-admin-goods-form.css:
  explicitly identifies itself as the canonical Goods form surface owner
```

The audit also found many historical `v0.6.x` and override blocks inside
`main.css`, while current project-owned classes are already spread across
`main.css`, `forprint-admin.css`, `forprint-admin-ui.css` and the specialized
admin stylesheets.

This creates the same class of cascade risk already resolved on the public
frontend: more than one project-owned layer can influence the same component.

## Decision

### 1. `main.css` becomes legacy fallback only

`base/core/admin/views/css/main.css` remains loaded during migration because the
legacy administration interface still depends on it.

It is not a valid owner for new ForPrint admin components.

Rules:

- do not add new `fp-*` component features to `main.css`;
- do not append new permanent versioned override blocks;
- do not perform a broad rewrite or removal of `main.css`;
- migrated components explicitly neutralize only the bounded legacy properties
  that conflict with their canonical owner;
- dead legacy blocks are removed only after the affected surface has completed
  responsive/functional acceptance.

### 2. `forprint-admin.css` is the canonical shared admin foundation

`base/core/admin/views/css/forprint-admin.css` owns reusable administration
primitives shared by more than one surface:

```text
admin shell
top bar
sidebar
workspace
generic content/entity/index cards
generic settings cards
action bars and action buttons
generic text/number/binary field presentation
shared labels, hints and control rhythm
shared focus/disabled/error states
shared spacing, border, surface and typography tokens
```

New generic `fp-admin-*` primitives belong here unless a more specific
component owner is explicitly defined below.

### 3. `forprint-admin-ui.css` is a transitional specialized owner

`base/core/admin/views/css/forprint-admin-ui.css` currently contains accepted
Media Processing card presentation plus some shell/sidebar overlap.

Until consolidation:

- Media Processing presentation may remain here;
- no new generic shell/sidebar rules may be added here;
- duplicate shell/sidebar ownership must be removed in a bounded migration;
- after consolidation the file must either be narrowed to Media Processing only
  or renamed in a separate explicit migration.

Do not rename it opportunistically during the first ownership checkpoint.

### 4. Specialized stylesheets keep bounded ownership

```text
forprint-admin-goods-form.css
  -> Goods create/edit surface only

forprint-admin-collections.css
  -> collection/filter/sortable-card behavior and presentation

forprint-admin-ordering.css
  -> shared ordering/drag-position presentation

forprint-admin-gallery.css
  -> admin image gallery and gallery dialog

forprint-admin-login.css
  -> login surface only
```

Specialized files may reuse variables/base controls from `forprint-admin.css`
but must not redefine the shared shell or generic field system.

### 5. Vendor assets remain vendor-owned

TinyMCE and other third-party styles/scripts under their vendor directories are
not project component owners and are not rewritten as part of admin CSS
modernization.

## Canonical loading model

The transitional loading model is:

```text
1. legacy main.css fallback
2. forprint-admin.css shared project-owned foundation
3. bounded specialized admin stylesheets
4. surface-specific isolation only when required
```

A later build/minification step may optimize delivery, but source ownership
remains separated and explicit.

## Selector policy

The admin modernization follows the same maintainability rules as the public
frontend:

1. use `fp-` prefixed semantic classes;
2. use BEM-like naming;
3. scope surface-specific rules under a stable modern root;
4. avoid IDs for styling;
5. avoid new inline presentation attributes;
6. keep specificity predictable;
7. use `!important` only as a documented temporary legacy-isolation measure;
8. do not keep several permanent `v0.x` correction blocks for one component;
9. consolidate accepted refinements before moving to the next surface.

## Accepted visual references

The existing Goods form and Media Processing settings card are the accepted
visual references for the modernization.

They establish the desired direction:

```text
compact but readable spacing
white card surfaces
soft neutral page background
clear section hierarchy
muted explanatory text
consistent form control geometry
visible labels/hints
bounded card borders
responsive grid collapse
clear primary action
```

The goal is not to clone their markup blindly. Shared visual semantics are
extracted into reusable primitives while surface-specific composition remains
in the surface owner.

## First bounded migration group

The first implementation group is:

```text
System Settings
Visual Assets
Header settings
Footer settings
```

Rationale:

- Settings already has nine table/surface-specific branches;
- Visual Assets has two explicit controller branches but no established modern
  visual pattern in the audit;
- Footer already has a substantial project-owned card/collection composition;
- Header settings are part of the settings surface and should use the same
  shared primitives;
- this group can establish the shared foundation without destabilizing the
  already accepted Goods form.

Media Processing remains a visual reference and regression target, not the
first surface to redesign.

## First-group constraints

The first group must not:

- change production/customer-facing templates;
- change database schema or business data;
- change product/catalog semantics;
- change Goods form behavior;
- remove `main.css`;
- broadly restyle every admin page;
- alter TinyMCE/vendor assets;
- rely on a clean Git worktree.

Controller/model changes are allowed only when required to add stable semantic
classes or preserve the existing functional contract.

## Acceptance

A migrated admin surface is accepted only when:

- one stylesheet has explicit presentation ownership;
- legacy dependence is bounded/documented;
- form submission and existing actions still work;
- keyboard focus remains visible;
- controls have associated labels where the existing data contract supports it;
- no new inline style/event attributes are introduced;
- PHP syntax is clean;
- focused admin checks pass;
- baseline responsive widths are usable;
- exact files are staged and committed separately from unrelated dirty work.

## Consequences

Positive:

- one place to change shared admin UI primitives;
- no additional growth of the 4648-line legacy cascade;
- Goods and Media Processing remain stable references;
- migration can proceed screen group by screen group;
- rollback remains file-based.

Costs:

- duplicate selectors must be classified before removal;
- `main.css` remains loaded during transition;
- some `!important` isolation may temporarily remain;
- visual acceptance still requires browser review.
