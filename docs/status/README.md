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
