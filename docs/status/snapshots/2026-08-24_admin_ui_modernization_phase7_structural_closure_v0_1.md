# Admin UI modernization — Phase 7 structural closure

**ID:** `FP-WEB-STATUS-ADMIN-2026-08-24-001`
**Version:** `v0.1`
**Date:** `2026-08-24`
**Status:** `completed`

## Scope

This snapshot records the structural completion of the authenticated admin UI modernization program through Phase 7. The work modernized presentation/runtime ownership while preserving the historical PHP backend, routing, authentication, generic CRUD contracts, media behavior, TinyMCE integration, AJAX payloads, field names and database behavior.

## Structural outcome

Phases 1–6 are closed structurally. Phase 7 legacy reduction is also structurally closed.

The final Phase 7 closure audit reported:

- 24 structural closure checks passed;
- 0 PHP lint failures across `base/core`;
- `git diff --check` passed;
- local preview smoke passed for legacy admin CSS, project-owned admin CSS, ordering CSS and `/admin`;
- no database, staging or production mutation.

## Final ownership decisions

- `main.css` remains a centrally registered legacy fallback. It is not a destination for new admin presentation.
- `forprint-admin.css` is the shared project-owned admin foundation.
- `forprint-admin-ordering.css` is the canonical owner of ordering/save-status presentation.
- `forprint-admin-ui.js` owns the extracted generic price-mode runtime.
- `scripts.js` and `frameworkfunctions.js` remain bounded compatibility layers where consumers still depend on them.
- admin presentation remains isolated from the public frontend through `fp-admin-*` ownership boundaries.

## Phase 7 completed reductions

- executable inline price-mode runtime was extracted to `forprint-admin-ui.js`;
- dead legacy sidebar `is-saving` presentation was removed;
- dead legacy sidebar `is-dragging` presentation was removed;
- duplicate/superseded sidebar scrollbar rules were removed from legacy CSS;
- dead sidebar `is-saved` pseudo-element branches were removed;
- live sidebar `has-save-error` residual presentation was converged into `forprint-admin-ordering.css`;
- competing live sidebar error rules were removed from `main.css` and `forprint-admin.css`.

## Explicit retained compatibility

The following are intentional and are not Phase 7 blockers:

- `main.css` remains loaded until consumers are migrated individually;
- three `data-fp-admin-gallery` guards remain in `scripts.js` because the modern gallery still depends on shared legacy upload/preview/FileList behavior;
- `frameworkfunctions.js` remains a compatibility boundary;
- footer inline JavaScript remains a bootstrap data/config contract for PATH, admin mode, TinyMCE areas and editor upload configuration;
- login inline runtime remains isolated from the shared authenticated admin shell;
- sidebar link-level `grab` / `grabbing` cursor presentation remains because the current sortable runtime can initiate drag from the whole link.

## Canonical Phase 7 post-state hashes

```text
main.css
e9c5dbc1dc8f9252f7d0ee948bf867405c64b976f111ec04b5e7636030a12bd6

forprint-admin.css
f76f3d44e1619bc7aa711136da77253e2d337a1cdfed3c2808ff46a8a560027e

forprint-admin-ordering.css
fe94b9559f50ab53c798f1af3c28a002eb385857f2eb50c5baff35522fc43732

forprint-admin-ui.css
1dbc16312eeb6cebbf416a3a3a3a95eb8c7eeeadf6a1ad24a4294e78b7a6ee4a
```

## Deferred non-blocking work

- broader `main.css` retirement requires consumer-by-consumer migration;
- gallery upload/FileList ownership may be migrated later as a dedicated runtime task;
- footer bootstrap may later move under a CSP/bootstrap architecture while preserving its data contract;
- isolated login runtime can be modernized separately;
- final screenshot/pixel polish remains a later visual pass and does not block structural closure.

## Repository workflow state

Structural modernization is complete through Phase 7. The next repository step is exact-path staging of accepted admin modernization files and this snapshot, followed by a focused commit. Unrelated dirty files must remain unstaged.
