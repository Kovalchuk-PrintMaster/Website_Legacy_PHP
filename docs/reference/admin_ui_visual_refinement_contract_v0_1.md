# ForPrint admin UI visual refinement contract v0.1

**ID:** `FP-WEB-REF-ADMIN-VISUAL-001`
**Version:** `v0.1`
**Date:** `2026-08-24`
**Status:** `active`
**Scope:** authenticated administration UI visual language after structural modernization

## Purpose

This document defines the shared visual direction for the second major stage of
ForPrint admin modernization: visual refinement after structural ownership,
runtime boundaries and compatibility behavior have been stabilized.

It is a presentation contract, not a backend contract.

## Stage boundary

Structural admin modernization through Phase 7 is complete.

The new stage is:

```text
ADMIN_UI_STAGE_B = VISUAL_REFINEMENT
```

The goal is not to rebuild the admin again. The goal is to make the already
modernized structure visually coherent, calm, readable and efficient.

## Visual language

The target admin should feel:

- light;
- block-based;
- calm rather than visually aggressive;
- slightly rounded rather than square;
- compact without becoming cramped;
- readable at normal desktop distance;
- consistent between equivalent controls and sections.

Media Processing is a useful visual reference for block composition and
labelled groups, but it is not a literal template and does not override the
shared ownership model.

## Shared design-token rule

Equivalent visual properties must come from one shared admin token contract.

Primary owner:

```text
base/core/admin/views/css/forprint-admin.css
```

Examples of centrally controlled properties:

```text
radius
card border
card shadow
section spacing
control height
body text size
label size
hint size
button geometry
badge colors
focus ring
muted text
```

A value that should change across the whole admin must not be copied separately
into each surface stylesheet.

Surface-specific CSS is allowed only when the layout or behavior is genuinely
specific to that surface.

## Existing ownership boundaries

```text
forprint-admin.css
    shared admin shell, tokens, fields, actions and reusable primitives

forprint-admin-goods-form.css
    Goods-specific form layout and Goods-only presentation

forprint-admin-gallery.css
    gallery-specific presentation

forprint-admin-ordering.css
    ordering/save-state presentation

forprint-admin-ui.css
    bounded specialized UI/runtime-adjacent presentation

main.css
    legacy fallback only; no new admin presentation
```

Do not create a parallel global admin stylesheet.

## First visual target: Goods create/edit

Work proceeds from the top of the Goods editor downward.

Initial priorities:

1. make `Зберегти` and `Видалити` visually equivalent controls;
2. normalize action-button radius, padding, hover, focus and shadow;
3. normalize field/card radius, border, subtle shadow and vertical spacing;
4. make label/hint typography consistent and more readable;
5. remove uneven top/bottom spacing between equivalent blocks;
6. make numeric/count badges neutral and secondary rather than dominant;
7. preserve the useful two-column desktop composition;
8. allow three/four columns only for genuinely compact data groups;
9. compact image upload/delete controls so the preview receives the space;
10. preserve gallery behavior while aligning its visual language;
11. keep action bars left-aligned unless a surface has a proven reason not to.

## Collapsible sections

Large sections may become collapsible after their markup/runtime contract is
resolved.

Target behavior:

- default state: open;
- a clear small disclosure indicator;
- whole section can be collapsed;
- prefer native `details` / `summary` when compatible;
- do not add custom keyboard handling when native semantics already provide it;
- do not introduce heavy JavaScript only for presentation.

Collapsing is a later slice, not a prerequisite for the first token/button/card
polish.

## Spacing rhythm

Equivalent blocks should have one predictable vertical rhythm.

Avoid:

- one field with 3px gap and another equivalent field with 10px for no reason;
- arbitrary per-page margins;
- large empty strips around small actions;
- stacking new override blocks at the end of a stylesheet.

Use shared spacing tokens where the spacing meaning is shared.

## Typography

Equivalent semantic roles must look equivalent:

```text
section title
field label
field hint
body/control text
secondary metadata
badge/count
```

Hints must remain secondary but readable. Very small text should be treated as
a review candidate, not automatically retained just because it existed in
legacy CSS.

## Buttons

Shared action buttons should have one contract:

- same base radius;
- same minimum height;
- same horizontal padding;
- same font sizing;
- same focus treatment;
- same subtle resting presentation;
- delete may use danger color on hover/focus, but should not look like a
  completely unrelated square control.

## Cards and field surfaces

Shared cards/field surfaces should use:

- light border;
- modest radius;
- very subtle or no permanent shadow;
- consistent internal padding;
- consistent spacing to adjacent cards;
- clear hierarchy without heavy dark borders.

## Count badges

Counts such as Goods filter-group count are informational.

They should:

- remain legible;
- have modest contrast;
- use compact padding;
- not visually dominate the control;
- not look like an error/status alert unless they actually represent one.

## Layout direction

Default desktop form composition:

```text
two columns
```

Use:

```text
three/four columns
```

only for compact homogeneous controls where this improves scanning.

On narrower widths, collapse progressively to fewer columns using the existing
responsive baseline.

## Visual acceptance workflow

For each bounded visual slice:

1. inspect exact owner and current source;
2. change shared tokens/primitives first;
3. add surface-specific rules only when necessary;
4. run syntax/diff/smoke checks;
5. review screenshots;
6. adjust the same canonical rules rather than appending permanent override
   generations;
7. accept the slice;
8. move downward to the next visible group.

A small cosmetic difference is not automatically a structural blocker.
Accessibility, readability, broken controls and layout failures are blockers.

## Functional non-goals

Visual refinement must not casually change:

- backend field names;
- form submission semantics;
- routing/authentication;
- AJAX payloads/actions;
- gallery upload/FileList behavior;
- TinyMCE data contract;
- database data/schema;
- production deployment.

## Communication and review

The project owner supplies screenshots, observations and command/report output.

The assistant should translate those observations into reusable shared visual
rules when the same concept appears across the admin, rather than fixing one
page manually and repeating the same CSS elsewhere.
