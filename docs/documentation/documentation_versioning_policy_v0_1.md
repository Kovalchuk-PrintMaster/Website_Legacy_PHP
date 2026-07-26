# Політика документації v0.1

**ID:** `FP-WEB-DOC-001`
**Статус:** active

## Типи

| Type | Directory |
|---|---|
| Architecture | `architecture/` |
| Workflow | `workflow/` |
| Snapshot | `status/snapshots/` |
| Plan | `plans/` |
| Decision | `decisions/` |
| Reference | `reference/` |
| Feature note | `development/` |
| Readiness | `launch_readiness/` |
| Evidence | `coordination/reports/` |

## Naming

```text
descriptive_name_v0_1.md
YYYY-MM-DD_development_state_v0_1.md
YYYY_MM_DD_feature_name_v0_1.sql
```

## Metadata

Назва, ID, version, date, status і за потреби `supersedes`.

## Statuses

`draft`, `active`, `accepted`, `planned`, `completed`, `superseded`, `historical snapshot`.

## Незмінність

Historical snapshot і completed plan не переписуються під новий стан. Створюється новий file. Дозволені лише очевидні corrections із приміткою.

## Mutable indexes

Section `README.md` може оновлюватися як index, але не повинен містити єдину копію critical decision.

## Не дублювати

- architecture пояснює модель;
- snapshot фіксує факт;
- plan задає next steps;
- report підтверджує виконання.

Новий великий documentation pack потрібен перед release, після major architecture change або після stabilization, але не після кожного CSS fix.

<!-- FP-FRONTEND-DOCS-V02-START -->
## Frontend package v0.2 application note

The 2026-07-20 frontend architecture checkpoint adds new versioned canonical documents rather than rewriting the historical v0.1/v0.2 records.

Supersession is explicit:

- `frontend_css_ownership_and_layout_strategy_v0_3.md` supersedes the v0.2 strategy;
- the earlier document remains historical evidence;
- status snapshots are date-bound and are not edited into later states;
- package manifests are versioned independently;
- bounded index-marker blocks may be updated idempotently without deleting unrelated index content.
<!-- FP-FRONTEND-DOCS-V02-END -->
