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
