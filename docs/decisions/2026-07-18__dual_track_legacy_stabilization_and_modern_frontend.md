# Decision: Dual-Track Legacy Publication and Modern Frontend

Date: 2026-07-18<br>
Status: accepted<br>
Decision scope: public frontend strategy

## Context

The inherited homepage was progressively inspected, documented and divided into controlled presentation components. This work established reliable knowledge about routes, data sources, search behavior, frontend profiles, shared assets and runtime output.

Continuing to extract every remaining legacy block with byte-identical HTML checks would consume disproportionate time while producing little visible improvement. The project also needs a usable public website before a complete modern design is ready.

## Decision

The project adopts two explicit frontend tracks.

### Track A — legacy publication stabilization

The inherited website remains the publication candidate.

Work is limited to practical readiness:

- repair broken or misleading interface details;
- keep core navigation, search, catalog, product and contact flows working;
- hide incomplete capabilities;
- improve the most visible mobile and desktop defects;
- prepare metadata, robots, sitemap and error behavior;
- validate the actual deployment.

Track A is not a full redesign and not a full legacy refactor.

### Track B — isolated modern frontend

A new frontend is built through a separate preview route and separate presentation layer.

The modern frontend:

- uses newly written semantic HTML;
- uses isolated, project-owned CSS and JavaScript;
- reuses stable server-side data, routes and business behavior;
- may initially resemble the inherited visual composition;
- may freely move toward modern visual patterns after owner review;
- is developed block by block with browser review;
- remains excluded from indexing until accepted.

The provisional local route is `/test-home/`. The final route name may be changed by an implementation decision without changing this strategy.

## Consequences

- Deep mechanical extraction of the legacy homepage stops.
- Existing extracted components and checks remain as a legacy safety net.
- The remaining catalog-navigation markup may stay in legacy `index.php`.
- Byte-identical output remains relevant only when stabilizing legacy behavior.
- Modern HTML is not required to match the inherited DOM.
- Modern CSS must not be built by extending the large legacy `style.css`.
- The legacy site may be publicly deployed before the modern design is complete.
- Switching `/` to the modern frontend requires separate visual and functional acceptance.

## Data and controller policy

The modern preview should reuse stable data preparation from the current home runtime or an extracted data provider.

It must not duplicate SQL or model queries merely to avoid understanding the current controller.

A separate controller is acceptable when it delegates to shared data preparation and selects a separate template.

## Asset-isolation policy

Modern assets must be separately addressable and must not load on legacy routes unless explicitly required.

Recommended initial locations:

```text
base/templates/default/test-home.php
base/templates/default/surfaces/home-v2/
base/templates/default/assets/css/surfaces/home-v2.css
base/templates/default/assets/js/surfaces/home-v2.js
```

The exact names may be refined before implementation.

## Preview indexing policy

Until the modern surface is accepted, it must provide:

```html
<meta name="robots" content="noindex, nofollow">
```

A public preview deployment must also be protected by route policy, authentication or deployment configuration when appropriate.

## Non-goals

This decision does not:

- authorize immediate replacement of `/`;
- authorize removal of the legacy frontend;
- change canonical product or order ownership;
- require a JavaScript framework;
- require copying the existing visual design exactly;
- define the final modern information architecture.

## Acceptance boundary

The decision is considered implemented when:

1. the legacy-publication backlog is explicitly tracked;
2. an isolated modern preview route returns HTTP 200;
3. modern assets are scoped and do not leak to legacy pages;
4. the first modern homepage skeleton is available for owner review;
5. the legacy publication can proceed independently of modern design completion.
