# ForPrint — frontend / Technical Requirements / Admin working state

**Date:** 2026-09-13
**Status:** historical snapshot
**Scope:** state observed immediately before returning focus to first-release frontend

## Public frontend

### Header

Working:

- logo left;
- slogan `ПРІНТ-СТУДІЯ ПОВНОГО ЦИКЛУ`;
- slogan vertical alignment substantially improved;
- Technical Requirements is present in primary navigation.

Outstanding:

- slogan decorative line is too wide;
- textual quote/proрахунок copies remain visible in/near the navigation contract;
- quote utility icon/badge presentation is not yet the accepted light outline;
- final owner-level consolidation is still required.

### Technical Requirements

Working:

- landing/detail routes;
- original ForPrint technical content;
- breadcrumbs/detail body;
- reusable inherited `knoweleges` entity.

Outstanding:

- left navigation still does not match the canonical catalog category tree;
- browser-default bullets/link colors remain;
- branch toggle behavior is not accepted.

## Admin

The Technical Requirements edit surface progressed from a broken raw-HTML short-circuit
to the normal Admin shell and a modern server-rendered card/grid presentation.

Observed current layout:

- normal Admin sidebar/topbar restored;
- top Save/Delete action bar;
- two-column metadata layout;
- main image and gallery side by side;
- rich-text sections below.

Known visual/system debt:

- label/hint inline rhythm;
- vertical spacing consistency;
- canonical dual ordering composite;
- shared image/gallery presentation and action behavior;
- broad settings/Admin surface consistency.

Decision at this checkpoint:

> Defer broad Admin visual normalization until the public frontend reaches release
> readiness. Keep the Admin operational and avoid reopening it as the blocking workfront.

## Release position

No production publication is recorded in this snapshot.

Next public work:

1. header finalization;
2. Technical Requirements catalog-tree parity;
3. responsive acceptance;
4. release preflight;
5. explicit canonical hosting mirror.
