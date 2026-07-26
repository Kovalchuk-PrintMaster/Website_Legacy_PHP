# Маніфест пакета документації v0.2

**Пакет:** `forprint_website_documentation_pack_v0_2.zip`
**Дата зрізу:** 2026-07-20
**Статус:** working architecture package

## Призначення

Пакет фіксує глобальну frontend-архітектуру після переходу до project-owned CSS, спільного layout-контракту, контрольованого header, компонентної homepage, нового hero slider, price modes і стабільного локального preview runtime.

Пакет не є записом публікації або deployment.

## Нові canonical документи

```text
docs/architecture/frontend_css_ownership_and_layout_strategy_v0_3.md
docs/architecture/home_frontend_structure_and_slider_architecture_v0_1.md
docs/decisions/2026-07-20__canonical_frontend_css_ownership_and_homepage_layout.md
docs/development/local_preview_systemd_service_v0_6_20.md
docs/plans/frontend_next_stage_plan_v0_2.md
docs/status/snapshots/2026-07-20_frontend_working_state_v0_1.md
docs/documentation/package_manifest_v0_2.md
scripts/inspection/check_website_frontend_architecture_docs.php
```

## Оновлювані індекси

Інсталятор додає bounded marker-блоки до:

```text
docs/README.md
docs/decisions/README.md
docs/decisions/architecture_decision_register_v0_1.md
docs/status/README.md
docs/reference/repository_map_v0_1.md
docs/reference/critical_files_and_responsibilities_v0_1.md
docs/documentation/documentation_versioning_policy_v0_1.md
```

Повторний запуск не дублює marker-блоки.

## Архітектурні теми

- єдиний власник глобальної ширини;
- fixed rail reservation;
- canonical shared header;
- homepage component map;
- hero slider data/presentation/runtime;
- заборона постійного override stacking;
- exact/range/request price presentation;
- slider media namespace direction;
- local systemd preview;
- responsive baseline;
- known debt and next-stage order.

## Валідація

Після застосування:

```bash
php scripts/inspection/check_website_frontend_architecture_docs.php
git diff --check -- docs scripts/inspection/check_website_frontend_architecture_docs.php
```

Інсталятор не виконує `git add`, commit, push або deployment.
