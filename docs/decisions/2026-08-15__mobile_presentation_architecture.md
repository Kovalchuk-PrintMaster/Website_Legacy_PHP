# Mobile presentation architecture

**ID:** `FP-WEB-ADR-2026-08-15-001`  
**Version:** `v0.1`  
**Date:** `2026-08-15`  
**Status:** `accepted working architecture decision`

## Context

ForPrint keeps the public website runtime in PHP while project tooling and
cross-module coordination may use Python.

The public frontend already has shared controller/model data flows,
`frontendSurface`, `frontendProfile`, server-rendered header/footer, and
surface-specific templates. During real-phone validation, the temporary
MOBILE.02C-MOBILE.02E work proved the desired mobile UX, but also showed that
continuing to transform desktop structure with additional CSS/JavaScript
overrides would create avoidable ownership and maintenance debt.

The mobile presentation must therefore be separated at the presentation
boundary without duplicating business/domain logic.

## Decision

### 1. Shared domain and business logic

Mobile and desktop use the same canonical:

- public routes and URLs;
- controllers unless a controller has genuinely different business semantics;
- models and database data;
- product/category/contact/social data;
- validation and server-side business rules.

A separate `MobileModel` or duplicated mobile business controller is not part
of the target architecture.

### 2. Existing presentation context is the integration point

The existing `frontendSurface` and `frontendProfile` concepts remain the
public presentation boundary.

`frontendSurface` identifies the semantic surface, such as `home` or
`catalog`.

`frontendProfile` may select an established presentation contract, but it
must not silently become an ad-hoc User-Agent device detector. Device/browser
detection is not a substitute for explicit presentation semantics.

### 3. Dedicated mobile partials only when semantics really differ

Prefer one semantic DOM with responsive CSS when the content and interaction
semantics are the same.

Use a dedicated mobile presentation partial only when the mobile interaction
or structure is materially different, for example:

- a mobile category discovery list versus a desktop carousel;
- a mobile catalog filter-entry composition;
- a compact shared-shell composition that cannot remain clear as the same
  desktop DOM.

Both presentations must consume the same canonical server data.

### 4. Server owns first-paint state

Any state that must be correct on first paint is server-rendered.

For the catalog, entering directly into the filter chooser must not depend on:

- `window.load`;
- replaying a synthetic click;
- hiding the wrong page until JavaScript catches up;
- an animation used to conceal an intermediate state.

The server must render the catalog filter panel in its initial open state when
an explicit UI-state request asks for it.

The request key must not collide with existing business query inputs.
In particular, scalar `filters=1` is prohibited because `filters[]` is already
canonical catalog filter input.

The exact UI-state key is selected during the catalog-boundary migration after
a collision check.

### 5. Canonical URL remains shared

The canonical catalog resource remains `/catalog/`.

A transient UI-state query parameter may control presentation, but it does not
create a second canonical mobile resource or duplicate catalog content.

No `/mobile/...` route family is introduced for presentation alone.

### 6. JavaScript is progressive enhancement

Project-owned mobile JavaScript may enhance already-correct server-rendered
markup.

It must not be the primary owner of page composition.

Target removals include mobile runtime responsibilities such as:

- moving structural nodes solely to create the mobile page;
- opening the catalog's required initial state by synthetic click;
- constructing shared header/footer navigation in JavaScript;
- fetching duplicate markup for presentation that PHP can render directly.

JavaScript remains appropriate for genuine client interaction after first
paint.

### 7. CSS owns layout and visuals, not semantic reconstruction

Responsive CSS styles an already-correct DOM.

It must not become a stack of structural overrides that converts one unrelated
desktop component into another mobile component.

Rules:

- keep project-owned component prefixes;
- keep one active geometry owner per component;
- add no new `!important`;
- consolidate or remove temporary MOBILE.02E review overrides as each
  presentation boundary is migrated;
- do not add a new generation of override blocks on top of MOBILE.02E.

### 8. Shared shell data stays shared

Header/footer branding, contacts, phones, callback configuration, social
links, and managed media remain shared server data.

Desktop/mobile shell presentation may differ, but neither presentation gets a
second source of truth.

### 9. Admin remains outside this mobile presentation split

This decision applies to the public website presentation.

Admin UI does not inherit the public mobile presentation CSS/assets and is not
given a parallel mobile architecture by this decision.

## Migration order

Migration proceeds one bounded ownership change at a time:

1. catalog initial filter-entry state moves from mobile JS to server render;
2. shared header/footer mobile composition is normalized;
3. homepage category discovery receives its final presentation ownership;
4. search placement is normalized so JS no longer composes page structure;
5. obsolete mobile runtime responsibilities and temporary overrides are
   removed;
6. responsive CSS is consolidated into canonical owners;
7. real-phone validation is repeated;
8. exact staging and a frontend checkpoint commit are performed only after
   acceptance.

## Acceptance criteria

The architecture is considered migrated when:

- mobile and desktop share canonical models/data/business rules;
- no duplicate mobile business controller/model exists;
- catalog filter-entry first paint is server-correct;
- JavaScript is not required to construct the initial mobile page;
- mobile-specific partials exist only where semantics justify them;
- responsive companions contain no new `!important`;
- temporary MOBILE.02E review ownership has been consolidated or retired;
- desktop behavior remains unchanged at accepted desktop breakpoints;
- mobile behavior is validated on a real phone;
- production preview uses the controlled partial-release workflow before any
  full deployment.

## Consequences

### Positive

- explicit ownership;
- less CSS cascade debt;
- less JavaScript page-composition debt;
- one source of truth for catalog/product/contact data;
- easier testing and future redesign;
- no SEO/content duplication from separate mobile URLs.

### Trade-offs

- some presentation partials may intentionally duplicate small amounts of
  markup;
- migration requires several bounded steps instead of one large rewrite;
- shared shell/view-loading contracts must be documented carefully.

These trade-offs are preferred to duplicating domain logic or accumulating
another generation of frontend overrides.
