# Marketing, Search and Google Ads Working State — 2026-08-20 v0.1

**ID:** `FP-WEB-STATUS-MKT-20260820-01`
**Version:** `v0.1`
**Date:** `2026-08-20`
**Status:** `historical snapshot`
**Scope:** ForPrint public website, organic search visibility, Google Ads delivery, rich search presentation, performance boundary.

## 1. Purpose

This snapshot records the accepted working state after the technical SEO baseline, the intrinsic image-dimensions production release, the closed Lighthouse Batch A investigation, and the current Google Ads investigation.

It does not supersede frontend architecture documents and does not authorize any production or Google Ads mutation by itself.

## 2. Accepted technical SEO state

The local search-readiness baseline is currently accepted with:

- 191 public sitemap URLs;
- actionable P0/P1/P2 findings: 0;
- missing intrinsic image dimensions: 0 pages;
- heading-level gaps: 0 pages;
- description-length heuristic findings: 0;
- stale PrintMaster descriptions: 0;
- unique titles: 191;
- unique descriptions: 191;
- three accepted long-title advisory cases only;
- canonical head/social metadata regression contracts green;
- breadcrumb and structured-data contracts green;
- LocalBusiness schedule contract green;
- Product structured data restricted to actually eligible product pages;
- no invented availability, ratings, or other unsupported commercial claims.

## 3. Batch A image-dimensions and Lighthouse state

Batch A was released selectively to production and accepted.

Production state:

- intrinsic dimensions present across the full 191-page production crawl;
- database mutation: none;
- full hosting sync: not used;
- Batch B semantics/metadata changes were kept outside the causal performance release.

Lighthouse state:

- canonical BEFORE matrix: 36/36 valid runs;
- Batch A AFTER matrix: 36/36 valid runs;
- root-cause review completed;
- direct application-JavaScript regression from intrinsic width/height was not established;
- the image-heavy desktop TBT signal is retained as a Lighthouse/browser `Other` / `Unattributable` lab diagnostic;
- TBT is not interpreted as field INP;
- the Batch A Lighthouse investigation is closed and no additional reruns are required for that release.

## 4. SEO Batch B prepared locally, not yet released

The following later SEO/semantics changes are prepared locally but intentionally remain outside the accepted Batch A production checkpoint:

- canonical document-title composition cleanup;
- product communication modal heading-semantics cleanup;
- bounded seven-row `goods.content` heading migration;
- bounded three-row `news.content` heading migration;
- canonical contacts metadata cleanup;
- local rebaseline with actionable SEO P2 findings at zero.

Batch B must be released separately with exact code staging and bounded database preconditions/rollback.

## 5. Google Ads: destination / HTTP 503 investigation

The historical Google Ads `Destination not working` signal has been investigated technically.

Current ForPrint destination behavior:

- the affected ForPrint landing page currently responds successfully;
- AdsBot desktop/mobile checks are currently successful;
- canonical and tracking-parameter variants are currently reachable;
- robots.txt is reachable;
- current testing did not reproduce an active 503 destination failure;
- the current campaign URL tracking test reports `Landing page found`.

Conclusion:

- a historical or transient hosting 503 remains plausible;
- no current server-side repair is justified solely to satisfy the old policy label;
- any future `Destination not working` recurrence must be diagnosed from the exact Final URL / Expanded URL / tracking chain and contemporaneous server evidence.

The legacy `e-machine.com.ua` site is outside the current ForPrint release scope. Its restoration is deferred. It must not be treated as a cause of a current ForPrint destination issue unless a real Google Ads URL, asset, redirect, or tracking dependency still points to it.

## 6. Google Ads: advertiser verification and account identity

The Google Ads advertiser-verification workflow is currently configured under the wrong relationship model.

Observed account UI currently asks for:

- agency information;
- client information;
- completion of a client task or sending a task link to a client;
- an ad-disclosure relationship funded by the old `For-Print Group` identity.

The business is not intentionally operating this Google Ads account as an agency advertising for a separate client.

Current action:

- legal organization-name correction has been requested from Google Support;
- advertiser-verification reset/correction has been requested so the account can represent a business advertising its own products and services;
- agency/client verification tasks are not to be completed while that relationship model is known to be wrong;
- Google Support response is pending.

## 7. Google Ads: policy state

Current known policy work:

- `Destination not working`: no longer reproduced technically in the current ForPrint environment;
- `Unapproved substances`: appears unrelated to the actual printing/signage products and an appeal using `Dispute decision` has been submitted for `FP-SEARCH-04-SIGNS-KYIV`;
- appeal status: in progress at snapshot time;
- strike history shown in the account: no multistrike history;
- old `Third Party Consumer Technical Support` callout remains legacy cleanup work and should not be appealed as a ForPrint production requirement.

## 8. Google Ads: delivery investigation

A controlled PROBE campaign has been inspected rather than changing all campaigns at once.

For `FP-SEARCH-01-BADGES-UA-PROBE`:

- campaign: Enabled / Eligible;
- ad group: Eligible;
- visible keywords: Eligible;
- targeting: Ukraine;
- languages: Ukrainian and Russian;
- Search Network enabled;
- active start/end dates at snapshot time;
- budget present;
- bidding: Maximize clicks with a maximum CPC limit;
- tracking template: empty;
- Final URL suffix: normal UTM / ValueTrack parameters;
- campaign URL test: `Landing page found`;
- impressions: 0;
- clicks: 0.

Ad Preview and Diagnosis, with Ukrainian language and a query corresponding to an existing eligible keyword, reported that no keywords in the account matched the query.

This is treated as an account/serving/verification diagnostic signal until Google Support resolves the incorrect advertiser-verification state. CPC, keyword architecture, and broad account settings should not be changed blindly before that account-level dependency is clarified.

## 9. Organic search-result presentation: new accepted workstream

Mobile Google Search screenshots show materially different presentation quality across ForPrint and competitors:

- some ForPrint product results already receive a product image and price treatment;
- some ForPrint/category-style results appear as plain text without a representative image gallery;
- competitors may receive richer visual treatments with multiple product images;
- some ForPrint product snippets expose a price range that can emphasize an unattractive upper bound.

The next search-presentation workstream must improve eligibility for richer visual product results while staying within Google and schema.org semantics.

The target is not to force a specific Google layout. Google ultimately chooses the rendered search presentation.

## 10. Current execution boundary

Parallel work is allowed in two tracks:

1. **Google Ads/account track**
   Wait for Google Support, continue policy/account diagnostics without speculative campaign-wide changes, then resume controlled serving work.

2. **Website/search optimization track**
   Continue the separate SEO Batch B release and the new rich-search/image/price presentation workstream using controlled website changes and search validation.

No Lighthouse reruns are required for the closed Batch A investigation.
