# ForPrint SEO documentation

## Canonical documents

- `seo_growth_governance_architecture_v0_1.*`
- `https_migration_and_canonical_origin_runbook_v0_1.*`
- `analytics_measurement_and_attribution_policy_v0_1.*`
- `search_ads_and_content_growth_roadmap_v0_1.*`
- `near_term_search_growth_plan_v0_1.*`

## Related existing policy

`docs/architecture/search_visibility_and_web_quality_strategy_v0_1.md`
remains the detailed search-quality strategy. This directory adds HTTPS,
measurement, promotion operations and a concrete execution order.

<!-- FP-TWO-STAGE-SEARCH-GROWTH-V0-1-START -->
## Active two-stage search-growth execution plan

- [Two-Stage Search Growth Execution Plan v0.1](two_stage_search_growth_execution_plan_v0_1.md)
- [Machine-readable plan](two_stage_search_growth_execution_plan_v0_1.yaml)

Current operating rule:

```text
Stage 1: launch the strongest existing pages and collect real demand signals.
Stage 2: expand category structure, missing products, content and seasonal pages.
```

Immediate next checkpoint: `SEO.MARKET.01`.
<!-- FP-TWO-STAGE-SEARCH-GROWTH-V0-1-END -->

<!-- FP-WEBSITE-MEASUREMENT-CONTRACT-V0-1-START -->
## Website measurement contract

- [Website Measurement Contract v0.1](website_measurement_contract_v0_1.md)

Current rule:

```text
generate_lead fires only after a confirmed, non-duplicate stored request.
No customer-entered personal data is sent to dataLayer.
Google Tag Manager remains disabled until runtime activation is explicit.
```

Checkpoint: `SEO.MEASURE.01C`.
<!-- FP-WEBSITE-MEASUREMENT-CONTRACT-V0-1-END -->

<!-- FP-GOOGLE-BUSINESS-PROFILE-V0-1-START -->
## Google Business Profile preparation

- [Transition plan](google_business_profile_transition_plan_v0_1.md)
- [Current state snapshot](../status/snapshots/2026-07-30_google_business_profile_state_v0_1.md)
- Preparation workspace: `../../seo/google-business-profile/forprint/`

Current rule: keep Print Master active as the equipment/service business, keep
Друкарня Smile active during preparation, and do not create a duplicate
ForPrint profile before the permanent sign and Google-supported transition.
<!-- FP-GOOGLE-BUSINESS-PROFILE-V0-1-END -->
