# Знімки стану розробки

- Новий суттєвий етап — новий файл у `snapshots/`.
- Старий snapshot не переписується під новий стан.
- Active plan зберігається в `plans/`.
- Completion evidence зберігається в `coordination/reports/`.

Формат:

```text
YYYY-MM-DD_development_state_v0_1.md
```

<!-- FP_STATUS_DUAL_TRACK_V0_1 -->
## Поточний frontend-стан — 2026-07-18

- останній frontend checkpoint: `9a64a12 Extract home search component`;
- legacy home має сім винесених компонентів;
- frontend profile resolver працює через `FP_WEB_FRONTEND_PROFILE`;
- legacy готується до практичної публікації;
- modern frontend переходить до ізольованого preview;
- product-page зміни, які не входять до frontend strategy checkpoint, залишаються окремою незавершеною роботою.

Канонічний стан: `docs/reference/legacy_frontend_current_state_v0_1.md`.

<!-- FP-FRONTEND-DOCS-V02-START -->
## Frontend working snapshot — 2026-07-20

- [Global layout, header, homepage hero, price presentation, CSS ownership, and local preview state](snapshots/2026-07-20_frontend_working_state_v0_1.md)
<!-- FP-FRONTEND-DOCS-V02-END -->

<!-- FP_STATUS_2026_08_06_FRONTEND_CHECKPOINT_START -->
## 2026-08-06

- `snapshots/2026-08-06_frontend_foundation_stable_checkpoint_v0_1.md`
  records the accepted local frontend/runtime checkpoint before the next
  responsive and component-refinement stage.
<!-- FP_STATUS_2026_08_06_FRONTEND_CHECKPOINT_END -->
