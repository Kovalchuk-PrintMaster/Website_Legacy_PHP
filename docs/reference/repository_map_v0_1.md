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
├── tmp.php
└── tmp.py
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
