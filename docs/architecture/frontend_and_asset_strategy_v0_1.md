# Стратегія frontend і assets v0.1

**ID:** `FP-WEB-ARCH-004`

## 1. Проблема

Legacy frontend має великі global CSS/JS. Ризики:

- широкі selectors;
- cascade overrides;
- залежність від include order;
- regression на unrelated pages;
- нечіткий ownership.

## 2. Цільова модель

Новий завершений компонент має:

- semantic markup;
- префіксовані classes;
- окремий CSS;
- окремий JS;
- явне підключення;
- cache-busting version.

Приклад namespace: `fp-product-communication-*`.

## 3. Ізоляція

Компонент не повинен:

- підписуватися на всі `input`, `button` або `form`;
- змінювати DOM інших components;
- дублювати server rules;
- залежати від випадкового legacy class;
- використовувати inline-style як постійну архітектуру.

## 4. Forms

Потрібні:

- labels;
- `autocomplete` і `inputmode`;
- visible focus;
- status region;
- disabled submit state;
- clear error/success;
- graceful server fallback.

Soft phone warning показується біля поля, не окремим агресивним modal.

## 5. Images

### Cards

- стабільний ratio;
- cover-crop;
- без геометричної деформації;
- головний об’єкт не притиснутий до країв.

### Gallery

- пріоритет повного кадру;
- окрема optimized gallery version;
- thumbnail не замінює source.

### Editor

- safe MIME і size;
- контрольований target;
- коректні URLs;
- відсутність запису поза дозволеним каталогом.

## 6. Visual acceptance

Перевіряються desktop/mobile product page, cards, modal, header/footer, focus/hover/disabled/error/success і відсутність horizontal scroll.

<!-- FP_DUAL_TRACK_ASSET_ALIGNMENT_V0_1 -->
## Dual-track alignment — 2026-07-18

Legacy publication continues to use the inherited asset graph with only high-value stabilization.

The modern preview must use separately addressable assets, provisionally:

```text
base/templates/default/assets/css/surfaces/home-v2.css
base/templates/default/assets/js/surfaces/home-v2.js
```

Modern CSS must not be implemented as an uncontrolled extension of `assets/css/style.css`. Cross-track shared assets require an explicit contract.
