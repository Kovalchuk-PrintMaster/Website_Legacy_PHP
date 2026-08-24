# ForPrint admin UI modernization plan v0.2

**ID:** `FP-WEB-PLAN-ADMIN-UI-002`
**Version:** `v0.2`
**Date:** `2026-08-24`
**Status:** `active`
**Supersedes:** `docs/plans/admin_ui_modernization_plan_v0_1.md`
**Depends on:** `docs/decisions/2026-08-23__canonical_admin_css_ownership_and_migration_order.md`
**Visual contract:** `docs/reference/admin_ui_visual_refinement_contract_v0_1.md`

## Goal

Continue admin modernization from a completed structural foundation into a
controlled visual-refinement stage without reopening backend/runtime contracts
or creating a second CSS architecture.

## Completed structural stage

The first modernization stage is complete:

```text
Phase 1 = CLOSED
Phase 2 = CLOSED
Phase 3 = CLOSED
Phase 4 = CLOSED
Phase 5 = CLOSED
Phase 6 = CLOSED
Phase 7 = CLOSED
```

Accepted structural commit:

```text
eb5f0a314f633ab0a7f33af529e7b9f0072ae26c
admin: complete structural UI modernization through phase 7
```

`origin/main` was verified at the same commit.

The structural stage established:

- shared project-owned admin CSS ownership;
- bounded legacy fallback;
- semantic form primitives;
- specialized Goods/gallery/ordering owners;
- runtime extraction where justified;
- accessibility/responsive baseline;
- exact staging/commit discipline.

## Phase 8 — visual system refinement

**Status:** active.

This is the second major admin stage.

### 8.1 Shared visual tokens

Centralize the reusable visual language in `forprint-admin.css`:

- radius scale;
- spacing scale;
- card shadow;
- body/label/hint font sizes;
- control height;
- neutral badge colors;
- action-button geometry.

Definition of done:

```text
one shared token changes equivalent admin components together
no new generic presentation in main.css
no permanent duplicate override generation
```

### 8.2 Goods top-down polish

Start at the top of the Goods create/edit form and proceed downward.

Order:

1. top action bar;
2. Name / URL / visibility / parent / position fields;
3. price section;
4. main image;
5. gallery;
6. keywords/editors;
7. promotions;
8. filters;
9. related goods;
10. service tabs;
11. bottom action bar.

For every group:

```text
owner resolution
bounded patch
local checks
screenshot review
accept / refine same canonical rule
move down
```

### 8.3 Shared propagation

When a Goods correction represents a shared semantic primitive, apply it at the
shared owner so equivalent controls across the admin inherit it automatically.

Do not manually repeat:

```text
button radius
card radius
field spacing
label typography
hint typography
control height
neutral badges
focus treatment
```

across separate pages.

Representative regression surfaces should be checked after shared-token changes.

### 8.4 Image and gallery refinement

Keep upload/delete/FileList/runtime behavior unchanged.

Polish:

- compact upload/delete controls;
- allocate more visual space to preview;
- align gallery cards/actions with the shared visual language;
- preserve accessible delete labels and confirmed deletion behavior.

### 8.5 Collapsible large sections

After the first non-collapsible polish is stable, identify large sections that
benefit from collapse.

Policy:

- default open;
- prefer native `details/summary`;
- one consistent disclosure presentation;
- no unnecessary JavaScript;
- preserve form controls and editor state.

### 8.6 Remaining admin surfaces

After Goods establishes the accepted visual language, review other surfaces
top-down and reuse the shared system:

```text
Settings
Header/Footer
Information/News
Collections/Filters
Technical screens
Media Processing
Gallery-oriented editors
other generic CRUD surfaces
```

Media Processing is a reference for block composition, not a separate style
system.

### 8.7 Responsive and accessibility visual closure

After desktop visual convergence:

```text
1920
1600
1366
1024
768
390 px
```

Review:

- column collapse;
- no horizontal overflow;
- touch target usability;
- readable typography;
- focus visibility;
- labels and accessible names;
- editor/gallery fit.

### 8.8 Final admin visual acceptance

Final pass is screenshot-driven.

Check:

- equivalent controls look equivalent;
- spacing rhythm is consistent;
- no visually aggressive informational badges;
- no isolated square legacy buttons;
- no accidental duplicate shadows/radii;
- no large wasted regions around small controls;
- action placement follows the shared convention;
- visual polish does not mask a functional regression.

## Current exact entry point

Read-only evidence already completed:

```text
tmp/admin_refactor/121_phase8_goods_visual_system_baseline_audit_20260824_1756.md
tmp/admin_refactor/122_phase8_goods_visual_contract_exact_owner_resolver_20260824_1802.md
```

Resolver 122 proved:

- shared Save/Delete owner: `forprint-admin.css`;
- shared field/card owner: `forprint-admin.css`;
- shared label/hint owner: `forprint-admin.css`;
- shared image actions owner: `forprint-admin.css`;
- Goods count badge owner: `forprint-admin-goods-form.css`;
- existing semantic markup is sufficient for the first slice;
- no PHP markup mutation is currently justified.

**Next implementation action:**

```text
FIRST_PHASE8_VISUAL_PATCH =
shared tokens
+ action buttons
+ field/card surface
+ label/hint typography
+ spacing rhythm
+ neutral Goods count badge
+ compact image actions
```

Do not start collapsible-section markup before this first slice is visually
accepted.

## Working rules

- current code/runtime is the factual source of truth;
- accepted decisions define architecture;
- this plan defines the current execution order;
- screenshots are required for visual acceptance;
- reports are evidence, not architecture;
- nontrivial changes use unique timestamped guarded scripts;
- never reuse an applied script filename;
- exact staging only;
- unrelated dirty worktree must remain untouched;
- no production deployment unless explicitly started as a separate release
  workflow.

## Completion criteria

Phase 8 is complete when the authenticated admin has one coherent visual
language, shared components respond to shared tokens, major surfaces have been
reviewed, and remaining legacy presentation is either invisible, bounded or
explicitly deferred.
