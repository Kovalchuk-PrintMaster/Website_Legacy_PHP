# Google Ads research: notebooks, diaries and planners

**Research plan:** `08-notebooks-planners`  
**Proposed campaign:** `FP-SEARCH-06-NOTEBOOKS-UA-PROBE`  
**State:** research only — no Google Ads Editor import is authorized yet

## Purpose

This package prepares a controlled preflight for notebook, diary and planner
advertising from the canonical production inventory.

It deliberately does not invent Keyword Planner volume, competition, CPC or
forecast values.

## Proposed ad-group model

- `AG-01-NOTEBOOKS-BRANDED`
- `AG-02-PLANNERS-DIARIES`
- `AG-03-NOTEBOOKS-SPRING`

The final group set must follow actual production landing pages. Empty or weak
groups must be removed rather than filled with unrelated URLs.

## Run

```bash
cd /srv/software_development/forprint-project/forprint_website

INVENTORY="marketing/data/staged/website-surface/2026-08-02/production_ad_inventory_2026-08-02.csv"

python3   marketing/research/google-ads/keyword-research/2026-08/08-notebooks-planners/scripts/build_notebooks_research_preflight.py   "$INVENTORY"
```

Review:

```bash
column -s, -t <   marketing/research/google-ads/keyword-research/2026-08/08-notebooks-planners/landing-page-candidates.csv   | less -S
```

## Research sequence

1. Generate and manually review landing-page candidates.
2. Remove false matches and confirm the final URL for each ad group.
3. Upload `seeds/positive-keyword-seeds.csv` phrases to Keyword Planner.
4. Export keyword statistics and forecast data.
5. Record only supported phrases in the canonical positive-keyword file.
6. Review every negative candidate against the actual commercial offer.
7. Approve geography, campaign total budget and maximum CPC.
8. Create the Editor import only after the launch gates pass.

## Safety boundary

Do not create or post the campaign when:

- a production URL is missing or irrelevant;
- Keyword Planner evidence has not been captured;
- negative keywords have not been commercially reviewed;
- budget or maximum CPC is not approved;
- the landing page lacks a truthful offer, form or price signal.

`candidate-research-only` and `candidate-review-required` are not launch
statuses.
