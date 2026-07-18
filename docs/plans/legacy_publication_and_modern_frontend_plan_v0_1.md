# Legacy Publication and Modern Frontend Plan v0.1

Status: active plan<br>
Date: 2026-07-18<br>
Decision source: `docs/decisions/2026-07-18__dual_track_legacy_stabilization_and_modern_frontend.md`

## 1. Goal

Publish a sufficiently reliable legacy website without waiting for the final redesign, while building the new frontend in an isolated and controllable way.

The two tracks share data and routes but have separate presentation acceptance.

## 2. Operating principles

- Publication means actual deployment to the Internet.
- Git commits and pushes are checkpoints, not publications.
- Legacy work is limited by practical publication value.
- Modern work is reviewed visually in the browser.
- New HTML, CSS and JavaScript are project-owned.
- Temporary preview pages are not indexed.
- Existing unrelated work is not mixed into frontend checkpoints.
- Every checkpoint has an explicit file scope and focused validation.

## 3. Phase 0 — documentation alignment

Deliver:

- current legacy frontend reference;
- persistent inspection and maintenance tool registry;
- accepted dual-track decision;
- dual-track execution plan;
- updated documentation indexes and cross-references.

Exit criteria:

- canonical documents are linked from `docs/README.md`;
- historical coordination reports are clearly non-canonical;
- current legacy composition and known constraints are documented;
- the next engineering step is unambiguous.

## 4. Phase 1 — isolated modern preview foundation

Create a local preview surface, provisionally `/test-home/`.

Minimum implementation:

- separate controller or delegated route handler;
- separate template;
- separate scoped CSS entry;
- separate scoped JavaScript entry;
- `noindex, nofollow`;
- HTTP 200;
- shared header/footer decision documented;
- no effect on `/`, `/catalog/` or product routes.

The first page may contain only:

```text
header
main
footer
```

Exit criteria:

- preview opens in the browser;
- legacy routes keep their current assets and behavior;
- modern assets load only on the preview;
- a focused smoke verifies isolation.

## 5. Phase 2 — legacy publication stabilization

This phase prepares the inherited frontend for actual public deployment.

### 5.1 Critical user routes

Verify:

- `/`;
- catalog;
- product;
- search results;
- contacts;
- information pages;
- promotions and special offers;
- news where enabled;
- error and missing-page behavior.

### 5.2 Essential interface quality

Correct only high-value defects:

- broken links;
- unreadable text;
- obviously broken mobile layouts;
- missing images without fallback;
- misleading buttons;
- incomplete controls that should be hidden;
- contact data and communication actions;
- search usability;
- product-page pricing and communication presentation.

### 5.3 Publication infrastructure

Prepare:

- production configuration without committed secrets;
- database backup and import procedure;
- webroot exposure rules;
- HTTPS;
- robots;
- sitemap;
- title and description defaults;
- canonical URL policy;
- logging and error visibility;
- post-deployment smoke sequence.

Exit criteria:

- publication checklist is passed;
- critical pages return successful responses;
- incomplete capabilities are hidden or explicitly deferred;
- rollback and backup steps are documented;
- owner approves actual deployment.

## 6. Phase 3 — modern homepage design

Build the modern homepage from top to bottom.

### 6.1 Header and navigation

Include:

- logo;
- primary navigation;
- contact actions;
- search;
- mobile menu;
- controlled call to action.

### 6.2 Hero and first commercial message

Include:

- primary offer;
- supporting visual;
- one or two clear actions;
- responsive composition.

The inherited design may be used as an initial orientation, but new semantic markup and isolated CSS are required.

### 6.3 Categories and commercial core

Include:

- catalog categories;
- popular products;
- special offers;
- product cards;
- clear links to real routes.

### 6.4 Trust and information

Include:

- advantages;
- company information;
- production or service evidence;
- news or useful materials;
- contact block;
- footer.

### 6.5 Visual acceptance loop

For every block:

1. render real data;
2. inspect desktop;
3. inspect tablet/mobile;
4. receive owner feedback;
5. adjust hierarchy, spacing and content;
6. freeze the accepted block contract.

Exit criteria:

- owner accepts overall visual direction;
- all important links work;
- no legacy CSS dependency is required for the modern surface;
- accessibility and responsive basics are present.

## 7. Phase 4 — controlled modern rollout

Possible sequence:

```text
/test-home/
controlled_v1
/
```

Before replacing `/`:

- compare functional coverage against the legacy home contract;
- verify indexing metadata;
- verify analytics and canonical URLs;
- verify search, catalog and product navigation;
- verify performance and image behavior;
- run a temporary rollback plan;
- obtain explicit owner approval.

The inherited frontend remains available until the modern rollout is accepted.

## 8. Parallel backlog rules

Track A and Track B must not be mixed accidentally.

A checkpoint belongs to Track A when it improves current publication readiness without introducing modern preview dependencies.

A checkpoint belongs to Track B when it changes only the isolated modern surface and shared contracts explicitly approved for reuse.

Shared backend changes require their own scope and tests.

## 9. Immediate next checkpoints

1. `Document legacy state and dual-track frontend strategy`
2. `Establish isolated home v2 preview surface`
3. `Audit legacy publication blockers`
4. `Stabilize critical legacy publication routes`
5. `Implement modern header and hero preview`

Checkpoint names may be refined, but their boundaries should remain separate.

## 10. Deferred decisions

The following are deliberately not fixed yet:

- final modern route name;
- final visual style;
- final homepage block order;
- framework versus plain JavaScript;
- whether the modern header/footer are shared across all future pages;
- the date when the modern frontend replaces `/`.

These decisions depend on browser prototypes and owner review.
