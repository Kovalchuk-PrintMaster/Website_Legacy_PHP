# Реєстр рішень v0.1

**ID:** `FP-WEB-ADR-REGISTER-001`
**Дата:** 2026-07-16

| ID | Рішення | Статус |
|---|---|---|
| `FP-WEB-ADR-001` | `base/` залишається webroot | accepted |
| `FP-WEB-ADR-002` | Safe publication до фундаментального rewrite | accepted |
| `FP-WEB-ADR-003` | Stable legacy зберігається; проблемний block можна замінити цілісно | accepted |
| `FP-WEB-ADR-004` | Нові frontend components мають isolated prefixed CSS/JS | accepted |
| `FP-WEB-ADR-005` | Server є source of truth для validation | accepted |
| `FP-WEB-ADR-006` | Communication request — standalone endpoint | accepted |
| `FP-WEB-ADR-007` | Old `ValidationHelper` не видаляти без migration consumers | accepted |
| `FP-WEB-ADR-008` | International phones через `libphonenumber-for-php-lite` | accepted, pending |
| `FP-WEB-ADR-009` | Unusual phone — soft warning + second submit | accepted, pending |
| `FP-WEB-ADR-010` | `tmp/work/tmp.php`/`tmp/work/tmp.py` — scratch entrypoints | accepted |
| `FP-WEB-ADR-011` | Snapshots і plans версіюються окремими files | accepted |
| `FP-WEB-ADR-012` | Reports — evidence, docs — explanation | accepted |
| `FP-WEB-ADR-013` | Cards use cover-crop; gallery preserves fuller image | accepted |
| `FP-WEB-ADR-014` | Secrets не потрапляють у docs/patch output/Git | accepted |
| `FP-WEB-ADR-015` | Block завершується checks, visual, explicit staging, commit | accepted |
| `FP-WEB-ADR-016` | Communication success modal auto-close ≈ 1 second | accepted, pending |

## Phone classification

1. valid — normalize/send;
2. unusual — warn and allow second submit;
3. malformed — block.

Точна поведінка закріплюється targeted tests.

<!-- FP_DUAL_TRACK_DECISION_REGISTER_V0_1 -->
## Frontend dual-track strategy

- Date: 2026-07-18
- Status: accepted
- Decision: prepare the inherited frontend for practical publication while developing a separate modern preview with project-owned HTML, CSS and JavaScript.
- Record: `docs/decisions/2026-07-18__dual_track_legacy_stabilization_and_modern_frontend.md`

<!-- FP-FRONTEND-DOCS-V02-START -->
## FP-WEB-ADR-2026-07-20-001

**Decision:** canonical frontend CSS ownership and homepage layout
**Status:** accepted working architecture decision
**Record:** `docs/decisions/2026-07-20__canonical_frontend_css_ownership_and_homepage_layout.md`
<!-- FP-FRONTEND-DOCS-V02-END -->

<!-- FP-PRODUCTION-RELEASE-ADR-V0-1-START -->
## FP-WEB-ADR-2026-07-30-001

**Decision:** s01 source of truth and controlled production mirror
**Status:** accepted
**Record:** `docs/decisions/2026-07-30__s01_source_of_truth_and_controlled_production_mirror.md`

Normal publication requires a reviewed local commit, exact release archive,
production baseline verification, private backup, manifest-scoped deployment,
production validation and rollback on failure.
<!-- FP-PRODUCTION-RELEASE-ADR-V0-1-END -->
