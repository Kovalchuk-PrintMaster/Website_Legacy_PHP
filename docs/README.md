# ForPrint Website — індекс документації

**Пакет:** `forprint_website_documentation_pack_v0_1`
**Дата зрізу:** 2026-07-16
**Статус:** базовий пакет поточного етапу підготовки сайту до публікації

## Призначення

Цей каталог є головною навігаційною точкою технічної документації ForPrint Website. Пакет не замінює вже наявні `development/`, `launch_readiness/` або `coordination/reports/`.

Він фіксує:

- архітектуру репозиторію і runtime;
- межі legacy та модернізованих компонентів;
- робочий процес через Debian terminal, `tmp.php` і `tmp.py`;
- стан розробки на 2026-07-16;
- план мінімально безпечної публікації;
- прийняті рішення, які не треба щоразу обговорювати заново.

## Розділи

| Каталог | Призначення |
|---|---|
| `architecture/` | Архітектура, потоки, межі старого і нового, frontend strategy |
| `workflow/` | Робочий процес, tmp-протокол, checks і Git |
| `status/` | Датовані snapshots фактичного стану |
| `plans/` | Версійні плани запуску й стабілізації |
| `decisions/` | Реєстр прийнятих рішень |
| `reference/` | Карта репозиторію, критичні файли, словник |
| `documentation/` | Політика документації та маніфест пакета |
| `development/` | Уже наявні feature-level документи |
| `launch_readiness/` | Уже наявні readiness-документи |

## Порядок читання

1. `architecture/system_architecture_overview_v0_1.md`
2. `architecture/legacy_and_modern_boundaries_v0_1.md`
3. `workflow/operator_assistant_workflow_v0_1.md`
4. `status/snapshots/2026-07-16_development_state_v0_1.md`
5. `plans/launch_preparation_plan_v0_1.md`
6. `decisions/architecture_decision_register_v0_1.md`

## Джерела істини

- Код, схема БД і runtime-конфігурація визначають фактичну поведінку.
- Snapshot описує історичний стан на конкретну дату.
- Plan визначає погоджену чергу конкретного етапу.
- Decision діє, доки нове рішення явно його не замінить.
- `coordination/reports/` підтверджує виконання окремих блоків.
- `development/` пояснює реалізацію окремих функцій.

## Базовий принцип

Поточна мета — контрольовано підготувати наявний сайт до публікації, а не переписати його повністю. Стабільна legacy-основа зберігається. Блок, який уже неможливо безпечно підтримувати дрібними латками, замінюється цілісним ізольованим компонентом.

<!-- FRONTEND_CHECKPOINT_INDEX_START -->
## Active first-release frontend checkpoint

- [First Release Frontend Checkpoint v0.1](status/first_release_frontend_checkpoint_v0_1.md)
- [First Release Checkpoint Commit Manifest v0.1](status/first_release_checkpoint_commit_manifest_v0_1.md)
- [Frontend Surface Stabilization Roadmap v0.1](plans/frontend_surface_stabilization_roadmap_v0_1.md)
- [Frontend Surface Isolation Strategy v0.1](architecture/frontend_surface_isolation_strategy_v0_1.md)
- [Decision: Freeze First-Release Scope and Start Progressive Frontend Refactor](decisions/2026-07-17__first_release_scope_freeze_and_frontend_refactor.md)
<!-- FRONTEND_CHECKPOINT_INDEX_END -->
