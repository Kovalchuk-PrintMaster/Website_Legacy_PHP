# Decision: version normalized Google Ads research and gate all spend

**Date:** 2026-08-01
**Status:** accepted working decision
**ID:** `FP-ADS-ADR-2026-08-01-001`

## Context

Seven Keyword Planner research plans were prepared for ForPrint. Google exports
contain useful evidence but also transient forecasts, duplicate variants and
large noisy idea sets.

## Decision

1. Normalized positive keywords, campaign negatives, forecast summaries,
   priority and landing-page mapping are versioned.
2. Raw Google CSV exports remain local evidence and are ignored by Git.
3. Forecast conversions and CPA are modelling only.
4. No campaign is approved by keyword research.
5. The first candidate is labels/stickers/product labels, with business
   cards/leaflets/flyers second.
6. A campaign must remain paused until measurement, landing page, geography,
   negatives, final URLs and budget pass the launch gates.
7. Kyiv is used for physical installation directions; Ukraine is used for
   shippable print products.
8. LED and flexible neon are supported; glass gas-discharge neon is not.

## Consequences

The project gets a reproducible research source without treating mutable Google
forecasts as canonical business truth. Campaign creation and spend remain
separate controlled checkpoints.
