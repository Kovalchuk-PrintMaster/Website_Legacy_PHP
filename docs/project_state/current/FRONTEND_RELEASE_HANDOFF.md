# ForPrint Website — current frontend release handoff

**Status:** active current handoff
**Date:** 2026-09-13
**Scope:** public frontend completion, Technical Requirements, release sequencing
**Audience:** operator and any replacement assistant starting with zero chat context

## 1. Mission

Finish the current local public frontend to a releaseable first version, validate it
across the agreed viewport baseline, then publish through the existing controlled
hosting mirror workflow.

Do not use the remaining Admin visual debt as a reason to postpone the public
frontend once the Admin is operational enough to manage the initial content.

## 2. Authority and runtime

- Repository root:
  `/srv/software_development/forprint-project/forprint_website`
- Branch: `main`
- Local preview: `http://127.0.0.1:8098/`
- Website runtime: inherited PHP 8.2 application.
- Engineering/inspection/operator tooling: Python.
- Local code/content/database/media remain authoritative during active development.
- Hosting is a controlled publication mirror.
- Production publication is a separate explicitly authorized operation.
- Preserve hosting runtime/secrets/configuration and the existing supplier runtime
  gates.
- Before and after any authorized hosting publication, run the canonical release
  health checks; do not send real Telegram/email test messages unless separately
  authorized.

## 3. Accepted architecture

Public frontend ownership:

- `forprint-layout.css` — global width/rail/layout geometry;
- `forprint-shell.css` — shared public header/shell;
- `forprint-home.css` — homepage-only presentation;
- `forprint-product-cards.css` — shared cards/grid;
- `forprint-product-detail.css` — product detail;
- `forprint-product-communication.css` — enquiry/communication UI;
- `forprint-search-suggestions.css` — search suggestions.

Do not accumulate permanent versioned override blocks for the same component.
Consolidate accepted behavior into the canonical owner.

## 4. Technical Requirements — public state

Implemented locally:

- `/technical-requirements/`;
- `/technical-requirements/<slug>/`;
- inherited backend entity `knoweleges` reused rather than creating a parallel CMS
  entity;
- seeded top-level sections and Digital Printing child content;
- public content is original ForPrint wording based on general prepress practice.

Still blocking release:

### Left navigation

The current left rail is not visually/behaviorally equivalent to the catalog tree.
Observed problems:

- browser-like bullets remain;
- links retain browser/legacy blue-purple presentation;
- toggle geometry is wrong;
- branch close/reopen behavior is not yet correct.

Next implementation must reuse or extract the exact canonical catalog category-tree
primitive: markup, typography, rows, arrows, counts and same-page open/close
behavior. Do not create another parallel approximation.

## 5. Public header — current state

Accepted direction:

- logo remains left;
- slogan is `ПРІНТ-СТУДІЯ ПОВНОГО ЦИКЛУ`;
- slogan vertical position is now close to the desired top row;
- primary navigation remains a separate lower row;
- `ТЕХНІЧНІ ВИМОГИ` appears before `КОНТАКТИ`.

Still blocking release:

1. the thin decorative line under/next to the slogan is too wide; constrain it to
   the slogan text width/owned slogan geometry;
2. textual quote/proрахунок copies must not appear in the primary navigation;
3. the dedicated quote control belongs in the right-side utility area and must use
   the accepted light/outline treatment with its count badge attached;
4. do not solve these with another stacked shell override generation — consolidate
   into the canonical header owner.

## 6. Technical Requirements — Admin state

The Admin has reached an important architectural checkpoint:

- the previous raw/un-styled short-circuit failure demonstrated that Admin content
  must remain inside the canonical `add.php` lifecycle;
- the corrected direction is server-rendered and follows the same Admin lifecycle as
  current surfaces such as `footer_settings`;
- recent operator screenshots show the normal Admin shell/sidebar/topbar restored;
- Technical Requirements metadata is now presented in a two-column modern card
  layout;
- main image and gallery are in one wide two-column media row;
- short/full information editors appear below.

Remaining Admin visual debt is **deferred from the release-blocking workfront**:

- field label + hint should use the same compact inline rhythm as the better current
  Admin surfaces;
- vertical spacing around controls should be normalized;
- `Позиція в списку` should use the same two-control ordering composite and matching
  heights as canonical Admin ordering UI;
- image and gallery widgets need one shared Admin visual contract across entities;
- main-image upload/delete behavior and gallery add/remove behavior should be
  normalized across Admin;
- `settings/?section=controls` and other legacy/generic Admin surfaces still need
  broad modernization;
- this work should reuse existing Admin component owners rather than create
  per-entity visual forks.

Treat the existing Admin modernization plan/contract as the future owner for this
debt. Do not block the first frontend release on a complete Admin redesign.

## 7. Immediate execution sequence

### FR-01 — header finalization

Owner: `forprint-shell.css` plus exact existing header markup/quote-control owner.

Acceptance:

- slogan line width matches the slogan component rather than a historical wider box;
- no textual quote/proрахунок item in primary desktop navigation;
- right-side quote icon is light/outlined;
- count badge is visually attached;
- no regression to mobile accepted navigation.

### FR-02 — Technical Requirements catalog-tree parity

Reuse/extract the existing catalog category-tree contract.

Acceptance:

- no browser bullets/blue-purple default links;
- catalog-equivalent typography/spacing/arrows;
- child counts where the catalog contract exposes them;
- current branch can close/reopen on the same page without navigation;
- no parallel permanent tree component.

### FR-03 — public responsive acceptance

Review approximately:

- 1920;
- 1600;
- 1366;
- 1024;
- 768;
- 390 px.

Check homepage, supplier catalog, Technical Requirements landing/detail and critical
shared header/footer/rail geometry.

### FR-04 — release preflight

Run the repository's existing checks and focused public smokes. Confirm:

- no PHP syntax regressions;
- `make check` passes;
- `git diff --check` passes;
- public local routes return expected HTTP status;
- production supplier gates remain preserved;
- explicit operator acceptance exists.

### FR-05 — hosting publication

Only after explicit operator authorization:

- use the existing canonical development-mirror/full hosting path;
- do not invent ad-hoc SCP/SQL sync;
- run canonical hosting health before and after;
- preserve hosting runtime/environment/secrets;
- verify public homepage, catalog/supplier catalog and Technical Requirements after
  publication.

## 8. Deferred Admin workstream

After the frontend release, resume Admin modernization as one shared-system workstream,
not as isolated per-page cosmetic patches.

Inventory all Admin field/component families first, then normalize shared owners:

- text field label/hint/input;
- radio/choice;
- select;
- ordering composite;
- main image;
- gallery;
- rich-text editor;
- action bars;
- content cards;
- collection/repeater rows.

Use production/current best surfaces such as `footer_settings` as reference evidence,
but keep one canonical component owner for each shared pattern.

## 9. Operator workflow

Preferred loop:

1. assistant prepares one bounded Python artifact for one coherent slice;
2. operator replaces repository-root `tmp.py`;
3. operator runs only `python tmp.py`;
4. script performs guarded mutation + checks + rollback/report;
5. operator sends report and only the screenshots needed for visual acceptance.

Do not manufacture extra probe steps after ownership is already established.

## 10. Communication convention

- Ukrainian;
- friendly `ти`;
- operator is addressed as a man;
- assistant uses feminine self-reference in Ukrainian.

This convention is not a technical authority rule.

## 11. Next action

Proceed with **FR-01 + FR-02 frontend correction as the next bounded implementation
package**, keeping Admin source unchanged except for checks that prove no regression.

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
