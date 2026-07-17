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
| `FP-WEB-ADR-010` | `tmp.php`/`tmp.py` — scratch entrypoints | accepted |
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
