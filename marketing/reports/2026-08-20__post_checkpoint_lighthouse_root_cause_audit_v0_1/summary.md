# ForPrint post-checkpoint Lighthouse root-cause audit

Generated: `2026-08-20T17:27:15.305231+03:00`

## Boundary

- Git checkpoint: `93b1905cb0276740e12f4fb5cb2a004986722e73` on `main` and upstream.
- Existing Lighthouse JSON only; no reruns.
- Production mutation: **none**.
- Database mutation: **none**.
- Batch B remains outside this analysis.

## Image-heavy desktop comparison

- Performance score: `74.0` → `50.0`.
- LCP: `3453.8199999999997` → `2403.1171824646` ms.
- TBT: `0.0` → `1575.433` ms.
- Requests: `106.0` → `106.0`.
- Script bytes: `303782.0` → `303775.0` (delta `-7.0`).
- Application bootup median: `521.7680000000173` → `365.8730000000086` ms.

## Interpretation

The available Lighthouse evidence does not establish a direct application-JavaScript regression from the intrinsic image-dimension release.

The strongest current explanation is a reproducible Lighthouse/browser `Other` / `Unattributable` lab signal on the image-heavy desktop route.

- `requests_stable`: `True`.
- `script_payload_stable`: `True`.
- `application_bootup_regressed_50pct`: `False`.
- `other_or_unattributable_dominant`: `True`.

TBT remains a lab diagnostic and must not be described as field INP.

## Recommended next boundary

Keep Batch A accepted. Investigate LCP discovery/image delivery and the image-heavy desktop lab signal as a separate performance workstream. Do not mix those changes into Batch B SEO semantics/metadata publication.
