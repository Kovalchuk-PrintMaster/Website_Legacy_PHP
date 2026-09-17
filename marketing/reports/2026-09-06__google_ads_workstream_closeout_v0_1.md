# ForPrint — Google Ads workstream closeout v0.1

**ID:** `FP-MARKETING-GOOGLE-ADS-CLOSEOUT-001`
**Date:** 2026-09-06
**Status:** closed / reopen on explicit trigger
**Scope:** Google Ads measurement setup and current optimization workstream

## Closed state

The Google Ads measurement workstream is closed for now.

Accepted technical state:

```text
Google tag: AW-959055246
Conversion destination: AW-959055246/3ccOCP6mntocEI6LqMkD
Production measurement enabled: true
Production test mode: false
Consent -> Google loader: accepted
Release-health Google measurement: PASS
Full production sync: PASS
```

Production runtime activation is hosted in protected hosting-owned runtime and
survives the canonical active-development mirror.

The browser consent flow has been accepted with:

```text
consent=granted
ready=true
granted=true
gtag=function
loader=true
```

## Important boundary

No controlled real lead conversion was fired during this workstream closeout.

Therefore:

- do not infer conversion-count quality from the current Google Ads account;
- do not switch bidding to Maximize Conversions solely because the tag is now
  technically active;
- keep current campaign optimization decisions separate from measurement
  installation;
- do not enable Search Partners, Display expansion, auto-apply targeting, or
  other scope-changing recommendations merely to improve Optimization Score.

The current bidding baseline remains **Maximize clicks** until valid conversion
data exists and a later Ads review explicitly changes that decision.

## Campaign optimization status

Measurement setup is complete.

Previously identified optimization work such as negative-keyword cleanup,
sitelinks, search-term review, redundant-keyword cleanup, Merchant Center, or
network expansion is not automatically continued by this closeout.

Any Google Ads Editor batch or account-side Post state that was not explicitly
confirmed in operator evidence must not be invented later.

## Reopen triggers

Reopen this workstream only when at least one of these is true:

1. the operator explicitly asks to continue Google Ads optimization;
2. Google Ads diagnostics show a new measurement/tag problem;
3. enough valid lead conversions exist to review bidding strategy;
4. a controlled real lead-conversion E2E test is explicitly authorized;
5. search-term, keyword, sitelink, geography, budget, or network optimization is
   explicitly requested.

## Source-of-truth distinction

Website/runtime measurement health belongs to the website release-health
system.

Google Ads campaign strategy belongs to the marketing/Ads workstream.

A healthy website tag does not by itself authorize campaign, budget, network,
keyword, or bidding changes.
