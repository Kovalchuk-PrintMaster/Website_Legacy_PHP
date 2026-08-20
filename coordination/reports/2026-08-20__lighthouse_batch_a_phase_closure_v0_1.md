# ForPrint Lighthouse Batch A phase closure

Generated: `2026-08-20T17:31:19.412903+03:00`

## Status

**CLOSED. No additional Lighthouse reruns are required for Batch A.**

Accepted Git checkpoint before closure: `93b1905cb0276740e12f4fb5cb2a004986722e73`.

## Measurement boundary

- Canonical BEFORE matrix: **36/36 valid runs**.
- Batch A AFTER matrix: **36/36 valid runs**.
- Same immutable Docker/Lighthouse toolchain and stable configuration signature.
- Production Batch A image dimensions were accepted at **191/191 pages, 9851 image instances, 0 missing dimensions**.
- Batch B remained outside production during the causal measurement.
- TBT is a Lighthouse lab diagnostic and is **not** field INP.

## Final interpretation

The intrinsic image-dimension release is accepted as correct production behavior, but the Lighthouse result is mixed and must not be described as a universal LCP improvement.

For the image-heavy desktop route, the high AFTER TBT reproduced across all three runs. However, the existing evidence does **not** support a direct application-JavaScript regression caused by the width/height change:

- Requests remained stable: `True`.
- Script payload delta: `-7.0` bytes.
- Script payload stable: `True`.
- Application bootup median: `521.7680000000173` → `365.8730000000086` ms.
- Direct application-JS regression supported: `False`.
- Browser / `Other` / `Unattributable` lab signal supported: `True`.
- Image-heavy desktop LCP median: `3453.8199999999997` → `2403.1171824646` ms.
- Image-heavy desktop TBT median: `0.0` → `1575.433` ms.

A high desktop TBT also appeared once on the representative product route in the AFTER sequence, while the next two runs of that route returned to zero. This further supports treating the signal as a Lighthouse/browser/runtime diagnostic rather than a proven width/height-induced JS regression.

## Closure decision

1. Keep Batch A accepted and released.
2. Do not run more Lighthouse tests for this Batch A investigation.
3. Do not claim a field-INP regression from the Lighthouse TBT signal.
4. Preserve the anomaly evidence for any future dedicated performance workstream.
5. Do not mix future performance changes into the separate Batch B SEO semantics/metadata release.

Production mutation performed by this closure: **none**.

Database mutation performed by this closure: **none**.
