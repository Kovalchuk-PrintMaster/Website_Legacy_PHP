# ForPrint Batch A image-dimensions production + Lighthouse checkpoint

Generated: `2026-08-20T17:19:28.938329+03:00`

## Accepted production state

- Guarded selective Batch A publication completed.
- Production image dimensions: **191/191 pages, 9851 image instances, 0 missing dimensions**.
- Database mutation: **none**.
- Full hosting sync: **not used**.
- Batch B remains outside production.
- Fresh off-host rollback snapshot exists from the Batch A release.

## Lighthouse causal measurement

- Canonical BEFORE runs: **36/36 valid**.
- AFTER runs: **36/36 valid**.
- Same immutable Docker/Lighthouse toolchain and stable configuration signature.
- TBT is a Lighthouse lab diagnostic and is not field INP.

- Home mobile LCP: `41983.4` → `35922.2` ms (-14.44%).
- Catalog desktop LCP: `6149.1` → `4332.8` ms (-29.54%).
- Image-heavy product desktop LCP: `3453.8` → `2403.1` ms (-30.42%).
- Image-heavy product desktop TBT median: `0.0` → `1575.4` ms.
- Image-heavy product desktop AFTER TBT per run: `[1575.433, 1390.9809999999998, 2068.2240000000006]` ms.

## Anomaly interpretation

The image-heavy desktop TBT signal reproduced in all three AFTER runs, so it is not treated as a one-run outlier. At the same time the request count and script transfer size stayed effectively unchanged, application page bootup did not show a matching increase, and the dominant long tasks are reported as `Other` / `Unattributable`. Therefore the current evidence does **not** establish an application-JavaScript regression caused by intrinsic width/height attributes. Keep the signal documented and do not collapse it into a field-INP claim.

## Git checkpoint boundary

- Branch: `main`; upstream: `origin/main`.
- Stage production-live `base/**` files only when local SHA256 exactly equals production.
- Stage selected search/image inspection and maintenance tooling.
- Stage compact Batch A/Lighthouse evidence only; raw Lighthouse run JSON is not added by this checkpoint.
- Explicitly exclude Batch B `header.php`, `productCommunicationButtons.php`, and local DB migrations.
- Do not use this checkpoint as authorization for Batch B production publication.

Production-equal base files selected: **10**.
