# ForPrint explicit production release manifest

Generated: `2026-08-20T14:31:05.428087+03:00`

## Decision

**Do not run `make hosting-sync-full` for the next release.**

Excluded current worktree entries: **62**.

## Batch A — image dimensions / performance

Selective webroot publication only:

- `base/core/user/controllers/BaseUser.php` — `52844377bb78a2334f5e4116d151a8c40c6fb81ead20917e1fbc9ec0962c12b7`
- `base/libraries/ForPrintImageDimensions.php` — `420d0cdc130eb74b4e58d5f290c8a42e8bc301ef02d359b95fc30275ab7a9673`
- `base/libraries/generated/forprint_image_dimensions_manifest.php` — `fcf1a2d9accf570baeabfd08d6a1e305d2ed552cc57c7485016ac3baae504153`

Database changes: **none**.

After production acceptance, run the exact same 36-run Docker Lighthouse AFTER matrix before Batch B.

## Batch B — SEO semantics / metadata

Selective code publication:

- `base/templates/default/include/header.php` — `7d8b4278f887418212443ae6a1f0c5555ac03b8e001abd7013a4ffa442927833`
- `base/templates/default/include/productCommunicationButtons.php` — `977f2818eeb7f48924e37a9f03b793afaf75d81819ec8400d84f15e84ffd64f9`

Bounded DB scope:

- 7 reviewed `goods.content` rows.
- 3 reviewed `news.content` rows.
- `information.id=8` (`contacts`) metadata fields only.

No full local DB mirror is approved for this batch.

## Current SEO baseline

- Actionable SEO P2: **0**.
- Accepted long-title advisory: **3**.
- Missing image dimensions locally: **0**.
- Heading-level gaps locally: **0**.
- Short-description heuristic locally: **0**.
- Unique titles/descriptions: **191/191**.

Production mutation performed by this audit: **none**.
