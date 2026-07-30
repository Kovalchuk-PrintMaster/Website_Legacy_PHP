# Карта репозиторію v0.1

**ID:** `FP-WEB-REF-001`

```text
.
├── base/                         # webroot
│   ├── index.php
│   ├── communication-request.php
│   ├── config.php
│   ├── config.example.php
│   ├── composer.json
│   ├── composer.lock
│   ├── vendor/
│   ├── core/
│   │   ├── base/
│   │   ├── user/
│   │   ├── admin/
│   │   └── plugins/
│   ├── templates/default/
│   ├── libraries/
│   ├── userfiles/
│   ├── log/
│   └── temp/
├── coordination/
├── database_dumps/
│   └── migrations/
├── docs/
│   ├── architecture/
│   ├── development/
│   ├── launch_readiness/
│   ├── workflow/
│   ├── status/
│   ├── plans/
│   ├── decisions/
│   ├── reference/
│   └── documentation/
├── scripts/
│   ├── inspection/
│   ├── maintenance/
│   └── windows/
├── Makefile
├── README.md
├── tmp/work/tmp.php
└── tmp/work/tmp.py
```

## Семантика

- `development/` — feature implementation notes;
- `launch_readiness/` — infrastructure readiness;
- `coordination/reports/` — completion evidence;
- `coordination/status/` — short coordination state;
- `migrations/` — versioned DB changes;
- `inspection/` — read-only/smoke;
- `maintenance/` — explicit controlled mutation.

## High-risk areas

`config.php`, `log/`, `temp/`, `vendor/`, `userfiles/`, SQL dumps і admin upload endpoints.

<!-- FP_REPOSITORY_MAP_FRONTEND_ALIGNMENT_V0_1 -->
## Frontend and tooling additions — 2026-07-18

```text
base/templates/default/surfaces/home/   legacy home presentation components
base/templates/default/assets/css/surfaces/ controlled surface CSS
base/templates/default/assets/js/surfaces/  controlled surface JavaScript
scripts/inspection/                     inspections and runtime validation
scripts/maintenance/                    data-changing maintenance commands
docs/reference/                         current factual references
docs/decisions/                         accepted architectural decisions
docs/plans/                             future execution plans
coordination/reports/                    historical implementation evidence
```

Persistent tool behavior is indexed in `docs/reference/inspection_and_maintenance_tools_v0_1.md`.

<!-- FP-FRONTEND-DOCS-V02-START -->
## Frontend architecture additions — 2026-07-20

```text
base/templates/default/assets/css/
├── forprint-layout.css
├── forprint-shell.css
├── forprint-home.css
├── forprint-product-cards.css
├── forprint-product-detail.css
├── forprint-product-communication.css
└── forprint-search-suggestions.css

base/templates/default/surfaces/home/
├── heroSlider.php
├── productGroups.php
├── about.php
├── advantages.php
├── feedback.php
├── news.php
└── search.php
```

Target homepage slider media namespace:

```text
base/userfiles/frontend/home/slider/
```
<!-- FP-FRONTEND-DOCS-V02-END -->

<!-- FP-PRODUCTION-RELEASE-MAP-V0-1-START -->
## Production release and recovery documentation

```text
docs/workflow/production_release_and_recovery_runbook_v0_1.md
docs/decisions/2026-07-30__s01_source_of_truth_and_controlled_production_mirror.md
docs/status/snapshots/2026-07-30_production_release_state_v0_1.md
scripts/inspection/check_website_production_release_docs.py
tmp/releases/                       generated release archives and safe reports
```

Source-of-truth boundary:

```text
s01 Git repository                    versioned code/docs/tooling authority
production hosting webroot            controlled code mirror
production database and userfiles     separate state; not overwritten by code release
production-only runtime secrets       separate non-Git configuration
Bestname DNS                           authoritative public DNS control
```
<!-- FP-PRODUCTION-RELEASE-MAP-V0-1-END -->
