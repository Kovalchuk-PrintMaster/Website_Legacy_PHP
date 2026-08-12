# Legacy SEO-to-marketing migration map v0.1

- **Date:** 2026-08-11
- **Status:** planning reference
- **Mutation:** none in MARKETING.01

| Current area | Target ownership |
|---|---|
| `seo/config/measurement_event_contract_v0_1.yaml` | review into `config/marketing/measurement/` |
| `seo/config/seo_source_registry_v0_1.yaml` | review into `config/marketing/` |
| `seo/google-ads/keyword-research/` | `marketing/research/keywords/google-ads/` |
| Ads `account-exports/` | external/`marketing/data/raw/google-ads/` plus manifest |
| Ads `editor-imports/` | campaign execution/evidence |
| Ads `audit-reports/` | `marketing/reports/campaigns/google-ads/` |
| `seo/google-business-profile/forprint/` | `marketing/local-presence/google-business-profile/forprint/` |
| `seo/data/` | `marketing/data/` after classification |
| `seo/reports/` | `marketing/reports/` |
| `docs/seo/` | classify into `docs/marketing/` |
| SEO ADRs | remain in `docs/decisions/` |
| SEO historical snapshots | remain in `docs/status/snapshots/` |
| `scripts/seo/` | `scripts/marketing/` |

Do not bulk-rename before classification. Review current/historical meaning,
secrets/private data, raw/curated status, duplication, references, provider IDs,
and supersession first.
