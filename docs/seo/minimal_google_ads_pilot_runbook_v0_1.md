# Minimal Google Ads pilot runbook v0.1

**ID:** `FP-WEB-SEO-WF-ADS-PILOT-001`
**Date:** 2026-07-29
**Status:** blocked until account issue is resolved

## Current blocker

The operator screenshot shows an account/payments issue and a
campaign-not-running diagnostic. The exact in-account instruction must be
completed before ads can run.

## Pilot design

The first campaign is a narrow Search campaign:

- one approved region;
- a small set of high-intent printing queries;
- one or two existing useful landing pages;
- direct HTTPS final URLs;
- no cross-domain redirect;
- an approved small daily budget;
- manual review every day during the pilot;
- broad expansion disabled.

## Destination gate

Before enablement, every final URL must:

- return `200` after the accepted canonical redirects;
- stay on `forprint.net.ua`;
- work on mobile and desktop;
- be crawlable;
- contain content that matches the ad;
- expose a working contact path;
- avoid obstructive or misleading experiences.

## Measurement during the temporary no-code phase

Until consent-aware analytics and the event contract are implemented locally:

- use Google Ads clicks and spend as platform metrics;
- use aggregate accepted lead counts as an operational comparison;
- do not claim precise conversion attribution;
- do not deploy an unreviewed tracking tag directly on production;
- record the pilot as exploratory rather than optimized-for-conversion.

## Stop conditions

Pause the pilot when any of these occurs:

- landing page or form failure;
- certificate or redirect problem;
- irrelevant search terms dominate;
- account or policy warning;
- spend reaches the approved cap without useful inquiries;
- production and local state diverge unexpectedly.
