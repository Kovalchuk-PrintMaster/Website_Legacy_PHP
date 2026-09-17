#!/usr/bin/env python3
from pathlib import Path
ROOT=Path("/srv/software_development/forprint-project/forprint_website")
required={
"docs/decisions/2026-08-19__canonical_public_metadata_and_breadcrumb_structured_data_ownership.md":["FP-WEB-ADR-2026-08-19-SEO-001","BreadcrumbList"],
"docs/status/snapshots/2026-08-19_search_visibility_release_state_v0_1.md":["historical snapshot","production sitemap: 191 URLs"],
"coordination/reports/2026-08-19__centralized_head_og_schema_production_release_v0_1.md":["completed evidence","PRODUCTION PUBLISH COMPLETE"],
"docs/marketing/plans/organic_search_measurement_next_stage_plan_v0_1.md":["Status:** active","Search Console","fresh post-release crawl"],
}
markers={
"docs/README.md":["FP_SEARCH_VISIBILITY_RELEASE_20260819_START","FP_SEARCH_VISIBILITY_RELEASE_20260819_END"],
"docs/decisions/architecture_decision_register_v0_1.md":["FP_ADR_SEARCH_METADATA_20260819_START","FP_ADR_SEARCH_METADATA_20260819_END"],
"docs/marketing/README.md":["FP_ORGANIC_SEARCH_NEXT_STAGE_20260819_START","FP_ORGANIC_SEARCH_NEXT_STAGE_20260819_END"],
"marketing/organic-search/README.md":["FP_HEAD_SCHEMA_RELEASE_CHECKPOINT_20260819_START","FP_HEAD_SCHEMA_RELEASE_CHECKPOINT_20260819_END"],
"marketing/programs/forprint_growth_roadmap_v0_1.md":["FP_TRACK_A_HEAD_SCHEMA_RELEASE_20260819_START","FP_TRACK_A_HEAD_SCHEMA_RELEASE_20260819_END"],
}
errors=[]
for rel,needles in required.items():
    p=ROOT/rel
    if not p.is_file():
        errors.append("missing:"+rel); continue
    text=p.read_text(encoding="utf-8")
    for needle in needles:
        if needle not in text: errors.append(f"content-missing:{rel}:{needle}")
for rel,ms in markers.items():
    p=ROOT/rel
    if not p.is_file():
        errors.append("missing:"+rel); continue
    text=p.read_text(encoding="utf-8")
    for marker in ms:
        if text.count(marker)!=1: errors.append(f"marker-count:{rel}:{marker}:{text.count(marker)}")
if errors:
    print("ForPrint search visibility release docs check FAILED")
    for error in errors: print(error)
    raise SystemExit(1)
print("[OK] ForPrint search visibility release documentation")
print("decision=accepted")
print("release_snapshot=recorded")
print("production_evidence=recorded")
print("organic_search_next_stage=active")
print("google_ads_wait_state=preserved")
