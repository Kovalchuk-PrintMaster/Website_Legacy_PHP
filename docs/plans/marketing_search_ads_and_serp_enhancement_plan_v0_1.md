# Marketing, Search, Ads and SERP Enhancement Next-Stage Plan v0.1

**ID:** `FP-WEB-PLAN-MKT-20260820-01`
**Version:** `v0.1`
**Date:** `2026-08-20`
**Status:** `planned`
**Depends on:** `2026-08-20_marketing_search_ads_working_state_v0_1.md`

## 1. Objective

Move ForPrint from a technically valid search/Ads foundation to a controlled acquisition system with:

- correct advertiser identity;
- actually serving Google Ads campaigns;
- reliable conversion measurement;
- clean organic search presentation;
- stronger product-image eligibility in Google Search and Google Images;
- commercially sensible price presentation;
- separate, measurable performance optimization.

## 2. Workstream A — Google Ads identity and verification

### A1. Payments profile

- wait for Google Support to process or respond to the legal organization-name correction;
- keep the existing payments profile unless Google explicitly requires another compliant path;
- preserve the existing Tax/VAT/address identity when those details already belong to the correct legal entity;
- do not create a second billing identity merely to bypass an access problem.

### A2. Advertiser verification

- reset/correct the accidental Agency/client relationship;
- verify as the business advertising its own products and services;
- confirm the final advertiser disclosure shown by Google;
- confirm whether any account-level serving restriction existed while verification was incomplete or inconsistent.

### A3. Access

- retain Google Ads Admin access;
- resolve Payments-profile permission separately from Google Ads Admin role if Google requires it;
- keep account identity and billing permissions conceptually separate.

## 3. Workstream B — Google Ads policy cleanup

- wait for the in-progress `Unapproved substances` appeal on `FP-SEARCH-04-SIGNS-KYIV`;
- if approved, repeat the same evidence-based dispute workflow for the other genuinely misclassified affected campaign;
- if rejected, inspect Google's exact reason before using another appeal attempt;
- remove or detach the legacy `Third Party Consumer Technical Support` callout from current active advertising scope;
- retain historical removed/disapproved assets only as account history, not as active inheritance.

## 4. Workstream C — Google Ads serving and controlled launch

After the verification/account dependency is clarified:

1. repeat Ad Preview and Diagnosis on the PROBE campaign;
2. verify that known eligible keywords are recognized by the serving diagnostic;
3. inspect any explicit account-level, policy, billing, location, language, bid, or Ad Rank blocker;
4. only then adjust CPC caps/bidding if the evidence says the auction is the limiting factor;
5. prove impressions on one PROBE campaign first;
6. prove click/landing-page behavior;
7. verify conversion recording;
8. only after a successful PROBE, activate the main campaign set in controlled batches;
9. verify start dates, budgets, bidding, geography and query relevance before each batch;
10. do not turn all pending campaigns live simultaneously.

## 5. Workstream D — conversion measurement

Before scaling spend:

- audit the canonical conversion goals;
- verify website communication/request events;
- verify phone/call measurement where actually used;
- verify lead-form measurement where actually used;
- ensure primary versus secondary conversions are intentional;
- test one real non-production-value conversion path where safe;
- keep bidding strategy aligned with the amount and reliability of conversion data.

Do not optimize to `Maximize conversions` merely because Google recommends it if conversion measurement is not yet trusted.

## 6. Workstream E — SEO Batch B guarded release

Release the already prepared SEO/semantics batch separately from performance work:

- canonical title composition;
- product communication modal semantics;
- bounded `goods.content` heading updates;
- bounded `news.content` heading updates;
- canonical contacts metadata cleanup.

Requirements:

- fresh production backup immediately before mutation;
- exact code publication set;
- exact database-row preconditions and rollback SQL;
- production acceptance crawl/contracts;
- exact Git checkpoint and push after acceptance.

Do not use a full hosting/database sync for this bounded release.

## 7. Workstream F — richer organic search presentation

### F1. Goal

Increase eligibility for visually rich Google Search / Google Images presentation for real product pages.

Desired user-facing outcome:

- representative product imagery in organic results where Google supports it;
- stronger chance of multiple product images for image-rich product experiences;
- accurate product identity and commercial data;
- clean mobile presentation.

Important boundary:

**Google chooses the final search-result layout. The website can maximize eligibility and provide preferred data, but cannot guarantee that Google will always render exactly one image, three images, a carousel, or a specific snippet format.**

### F2. Product-page image contract

For eligible single-product pages:

- expose multiple real product images in normal crawlable HTML;
- ensure images are visible/relevant to the marked-up product;
- provide descriptive `alt` text;
- keep image URLs stable, crawlable and indexable;
- keep intrinsic dimensions;
- add responsive `srcset`/`sizes` in the separate image-delivery performance workstream;
- provide a stable preferred/primary product image;
- provide multiple high-resolution `Product.image` values when the page really contains those images;
- target useful aspect-ratio coverage, especially 1:1, 4:3 and 16:9 when the source material supports it;
- ensure `og:image` and structured-data primary-image choices do not point to a generic logo when a representative product image exists.

Do not fabricate extra images only for markup.

### F3. Image discovery

Audit:

- whether important product images are discoverable directly from `<img src>` / `<picture>` fallbacks;
- whether an image sitemap would materially improve discovery;
- Search Console URL Inspection for selected representative product/image URLs;
- image indexing and crawlability;
- generic/non-descriptive legacy filenames as a secondary cleanup, without breaking stable indexed URLs unnecessarily.

### F4. Structured product identity

For each rich-result candidate, classify the page correctly:

- single concrete product;
- product with variants;
- configurable product/service;
- category/listing page.

Rules:

- do not apply Product rich-result markup to generic category/list pages merely to obtain a richer snippet;
- use Product/Offer only where the page actually represents a product/offer;
- consider ProductGroup and variant markup where variants are genuinely modeled as variants;
- preserve the current rule against invented reviews, availability, identifiers, or prices.

### F5. Merchant listing / Merchant Center evaluation

Evaluate whether ForPrint's purchasable product subset should also be supplied through Google Merchant Center / free listings.

This is optional, but Google explicitly states that combining on-page structured data with Merchant Center product data can maximize eligibility for shopping-related experiences.

Do not create a feed until canonical product IDs, URLs, prices and availability semantics are trustworthy.

## 8. Workstream G — price presentation policy

### G1. Business preference

For search presentation, ForPrint prefers:

- **exact-price product:** show the exact price;
- **real price range/configurable product:** prefer the real minimum/base price as the primary commercial signal (`від X грн`) when that minimum is genuinely obtainable and visible on the page;
- avoid a snippet that visually emphasizes only an unattractive upper bound;
- if Google/markup semantics require a range, expose the truthful range rather than a misleading synthetic single price.

### G2. Standards boundary

Do not manipulate structured data only to force a lower-looking search price.

Implementation must first determine the canonical pricing model:

1. If there is one actual offer at one price: use an `Offer` with that exact price.
2. If a concrete minimum configuration can actually be ordered at the stated price: the page may visibly present `від X грн`, and the structured offer model may use that concrete minimum only if it accurately describes a real purchasable offer.
3. If the page represents multiple genuine offers, use the applicable offer aggregation semantics.
4. If the difference is a true product-variant model, use product-variant semantics rather than misusing `AggregateOffer`.
5. If Google chooses to display a range despite valid data, treat that as Google's presentation decision rather than falsifying the source data.

### G3. Required audit before code change

For every product currently producing price rich results, capture:

- rendered visible price text;
- canonical database price fields;
- current JSON-LD type (`Offer` / `AggregateOffer` / other);
- current `price`, `lowPrice`, `highPrice`, `priceCurrency`;
- whether the minimum is a real orderable price;
- whether the maximum has a defined business meaning;
- whether the product has variants or quantity/configuration-based pricing.

Then define one canonical pricing contract and update templates/structured data from that contract.

## 9. Workstream H — SERP baseline and acceptance

Create a representative SERP baseline for:

- exact-price product;
- range-priced product;
- image-rich product;
- product currently showing one image;
- product/category currently showing no image;
- signage/outdoor-advertising page.

Acceptance checks after implementation:

- Rich Results Test;
- Search Console URL Inspection;
- Product snippets / Merchant listings reports where applicable;
- structured-data contract regression;
- image crawlability;
- visible price/markup consistency;
- screenshots after Google recrawl/index refresh.

Do not use a visual SERP screenshot alone as proof that markup is correct; Google rendering is dynamic and query-dependent.

## 10. Workstream I — separate performance optimization

After the structured-search presentation contract is stable:

- identify representative LCP elements;
- optimize hero/LCP discovery and fetch priority;
- build responsive image delivery;
- compress/modernize image variants;
- reduce unnecessary blocking CSS/JS;
- remeasure representative mobile/desktop routes.

Do not reopen the closed intrinsic-dimensions Lighthouse Batch A investigation unless new evidence specifically requires it.

## 11. Organic growth after technical correctness

Once the technical and rich-result foundation is stable:

- inspect Search Console queries/pages;
- identify high-impression, low-CTR pages;
- identify commercial content gaps;
- improve category/product copy where it has actual user value;
- create dedicated stable landing pages only for meaningful commercial intents;
- improve internal linking between categories, products, services and useful articles;
- monitor indexing/crawl changes after each release.

## 12. Deferred legacy site

Restoration of the legacy `e-machine.com.ua` site is deferred until ForPrint website and acquisition work are stable.

When that project starts, treat it as a separate site recovery:

- restore from backup in isolation;
- establish its own canonical identity, redirects and Search Console state;
- ensure it does not accidentally inherit or conflict with ForPrint Ads/analytics/verification configuration.

## 13. Immediate next sequence

While waiting for Google Support:

1. document and implement the guarded SEO Batch B release;
2. audit current product-image and structured-price ownership;
3. design the canonical multi-image Product structured-data contract;
4. design the canonical exact-price / minimum-price / range-price contract;
5. select representative pages for SERP baseline and Rich Results testing;
6. keep Google Ads account changes minimal until support clarifies verification;
7. when Google responds, resume controlled PROBE serving diagnostics;
8. after a successful PROBE, scale campaigns gradually.

## 14. Standards references

Primary external references for implementation:

- Google Search Central — Product structured data:
  `https://developers.google.com/search/docs/appearance/structured-data/product`
- Google Search Central — Product snippets:
  `https://developers.google.com/search/docs/appearance/structured-data/product-snippet`
- Google Search Central — Merchant listings:
  `https://developers.google.com/search/docs/appearance/structured-data/merchant-listing`
- Google Search Central — Product variants:
  `https://developers.google.com/search/docs/appearance/structured-data/product-variants`
- Google Search Central — Image SEO best practices:
  `https://developers.google.com/search/docs/appearance/google-images`
- Schema.org Product:
  `https://schema.org/Product`
- Schema.org Offer:
  `https://schema.org/Offer`
