# ForPrint frontend release completion roadmap v0.1

**ID:** `FP-WEB-PLAN-FRONTEND-RELEASE-2026-09-13`
**Version:** v0.1
**Date:** 2026-09-13
**Status:** active
**Supersedes for current execution:** `frontend_next_stage_plan_v0_2.md`
**Scope:** remaining first-release public frontend and release handoff

## Goal

Reach a visually stable, locally accepted public frontend and publish it through the
existing controlled hosting mirror without waiting for complete Admin modernization.

## Roadmap

| Step | State | Scope | Blocking acceptance |
|---|---|---|---|
| FR-01 | next | Header/slogan/quote control finalization | canonical shell owner; no leaked quote text; accepted utility icon/badge |
| FR-02 | next | Technical Requirements left tree exact catalog parity | shared/extracted catalog tree; correct toggle; no browser-default list presentation |
| FR-03 | planned | Responsive/public acceptance | 1920/1600/1366/1024/768/390 review on critical public surfaces |
| FR-04 | planned | Release preflight | repository checks + focused public runtime + operator visual acceptance |
| FR-05 | planned | Explicit hosting publication | canonical mirror workflow + health pre/post + public verification |
| AR-01 | deferred | Cross-Admin field/layout inventory | after frontend release unless operator reprioritizes |
| AR-02 | deferred | Shared Admin component normalization | text/choice/select/ordering/image/gallery/editor/action/card owners |
| AR-03 | deferred | Legacy Admin surfaces modernization | settings controls and remaining inconsistent entity forms |

## FR-01 — Header

Fix in the canonical shell/header owner only.

Required:

- slogan line constrained to current slogan width;
- preserve accepted slogan vertical alignment;
- remove/hide the textual quote menu copy at its real source/owner;
- preserve one dedicated quote utility;
- restore accepted light outline icon and attached badge;
- verify desktop + mobile header.

Do not add another permanent versioned override block.

## FR-02 — Technical Requirements tree

Do not maintain a parallel approximation.

Required:

- use or extract canonical catalog category-tree markup/classes/controller;
- preserve crawlable links;
- use accessible same-page branch toggle;
- match typography, row spacing, arrow and count behavior;
- verify on multiple Technical Requirements detail pages.

## FR-03 — Responsive/public acceptance

Critical surfaces:

- homepage;
- supplier catalog root and category-selected state;
- Technical Requirements landing;
- Technical Requirements detail;
- shared header/right rail;
- key catalog/product surfaces affected by shared owners.

Viewport baseline:

`1920 / 1600 / 1366 / 1024 / 768 / 390`.

## FR-04 — Release preflight

Minimum gates:

- PHP syntax/focused checks as applicable;
- `make check`;
- `git diff --check`;
- local HTTP/public smoke;
- no accidental database/media/schema change in frontend-only slices;
- supplier public runtime gates still enabled;
- explicit operator visual acceptance.

## FR-05 — Hosting publication

Not automatic.

After explicit authorization:

1. canonical hosting health precheck;
2. canonical local-authority -> hosting mirror command;
3. no ad-hoc manual production patch;
4. canonical hosting health postcheck;
5. public route verification;
6. retain report/evidence.

## Deferred Admin roadmap

The current Technical Requirements Admin is operational enough for initial content, but
the Admin visual system remains inconsistent across entities.

After first public release:

1. inventory all current Admin field/rendering patterns;
2. select canonical component owners from the best current surfaces;
3. normalize labels/hints/control heights and spacing;
4. normalize ordering composite;
5. normalize main-image + gallery contract and visual widgets;
6. normalize rich-text/editor cards;
7. refactor remaining legacy settings/entity pages onto shared components;
8. validate desktop and practical smaller Admin viewport behavior.

The Admin modernization must remain a shared-system refactor, not a collection of
entity-specific CSS patches.

## Non-goals for this roadmap

- no complete rewrite of the inherited PHP application;
- no new CMS entity for Technical Requirements;
- no production publication without explicit approval;
- no completion of every Admin modernization issue before the first public frontend
  release;
- no replacement of the active hosting-mirror authority model.

<!-- FP_LEGACY_STYLE_CSS_MIGRATION_POLICY_V0_1_START -->
## Legacy `style.css` migration policy

`base/templates/default/assets/css/style.css` is a compatibility layer for
untouched inherited pages. It is **not** a canonical owner for new/current
ForPrint frontend work.

Rules for ongoing frontend work:

- do not add new component behavior to `style.css`;
- do not make a modern ForPrint component depend on selectors that exist only in
  `style.css`;
- when a touched component still relies on legacy rules, migrate the accepted
  behavior into its existing `forprint-*.css` owner;
- prefer reusing an existing `forprint-*.css` component owner over creating a
  new route-specific stylesheet;
- once all known consumers of a legacy rule family are covered by modern owners,
  remove that legacy family from `style.css` in a separate bounded cleanup pass.

For the catalog/Technical Requirements tree, `forprint-catalog.css` is the
canonical modern presentation owner. `forprint-technical-requirements.css`
owns only Technical Requirements composition/content adjustments.
<!-- FP_LEGACY_STYLE_CSS_MIGRATION_POLICY_V0_1_END -->
