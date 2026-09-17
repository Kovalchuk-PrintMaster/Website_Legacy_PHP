# Decision: supplier catalog adapter and local-first partner integration

**Date:** 2026-09-06
**Status:** accepted working architecture decision
**Scope:** external supplier product catalogs used as branding bases by ForPrint

## Context

ForPrint sells branding/printing services on products that are often sourced
from specialist external suppliers. Sending customers to supplier storefronts
breaks the ForPrint journey, makes product search slow, and gives ForPrint no
direct control over the selection-to-enquiry experience.

The pilot supplier is Totobi.

## Decision

1. ForPrint will not embed a supplier storefront with an iframe and will not
   reverse-proxy/scrape the supplier frontend as its presentation layer.
2. Supplier integrations use source feeds/APIs intended for structured data
   exchange.
3. Python owns supplier retrieval, validation, normalization, comparison and
   controlled synchronization.
4. PHP remains the public website/runtime presentation owner.
5. Each supplier has an adapter behind one normalized supplier-catalog model.
6. Supplier records remain distinguishable from ForPrint's canonical
   service/product authority.
7. Supplier source identity, external IDs/SKUs, source URLs and synchronization
   timestamps remain traceable.
8. Supplier credentials/access tokens are runtime configuration; they are not
   committed to source.
9. Imported supplier pages/content are `noindex` by default until ForPrint adds
   substantial unique service/branding value and explicitly promotes a stable
   page into the canonical search model.
10. Public production reuse of supplier images/descriptions waits for confirmed
    commercial permission/licensing. Local parsing and prototype work may
    proceed first.
11. The pilot is proven with one supplier before additional supplier adapters
    are added.

## Pilot phases

```text
S1  read-only feed adapter + normalized model
S2  resolve existing ForPrint catalog/DB/menu owners
S3  versioned supplier storage/sync contract
S4  local PHP catalog surface + menu entry
S5  branding/request integration
S6  search/indexation policy + analytics
S7  controlled production release after permission/acceptance
```

## Consequences

Positive:

- customer stays inside the ForPrint experience;
- supplier selection becomes reusable across branding workflows;
- stock/price/product data can be refreshed systematically;
- future suppliers reuse one normalization contract;
- ForPrint can measure product-selection and enquiry behavior directly.

Costs/constraints:

- supplier data freshness and outages become operational concerns;
- rights to reuse media/descriptions must be explicit;
- duplicate/thin search pages must be prevented;
- supplier schema changes require adapter-level compatibility handling.

## Rollback

The supplier integration remains isolated from the existing canonical catalog
until its data/storage contract is accepted. Early pilot files can be removed
without changing production data or routes.
