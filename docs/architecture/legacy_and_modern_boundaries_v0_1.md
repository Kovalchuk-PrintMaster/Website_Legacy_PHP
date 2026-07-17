# Межі legacy і модернізованої частини v0.1

**ID:** `FP-WEB-ARCH-002`
**Дата:** 2026-07-16

## 1. Legacy-зона

До legacy-зони належать:

- `base/core/base/`;
- значна частина `base/core/user/` і `base/core/admin/`;
- великий `assets/css/style.css`;
- глобальний `assets/js/script.js`;
- старі helpers, включно з `ValidationHelper.php`;
- історичні templates і card components.

Legacy не означає автоматично «видалити». Це означає: змінювати обережно, знати залежності, перевіряти ширше за один екран.

## 2. Модернізована зона

До неї належать:

- component-specific `forprint-*.css` і `forprint-*.js`;
- product communication buttons і forms;
- `communication-request.php`;
- gallery, related goods, configurable tabs;
- managed content pages;
- image optimizer pipelines;
- local smoke/preview tooling;
- версійні migrations;
- структурована документація.

## 3. Вибір способу зміни

Дрібний patch доречний, коли проблема локальна й не створює нового дубля.

Цілісна заміна блоку потрібна, коли:

- логіка розкидана по багатьох файлах;
- CSS складається з каскаду override-ів;
- global JS втручається в новий компонент;
- старий HTML важко адаптувати;
- потрібна чітка відповідальність markup/CSS/JS/server.

## 4. Заборона подвійної бізнес-логіки

Frontend забезпечує UX, але server є джерелом істини. Дві незалежні server-side реалізації однієї перевірки не повинні існувати довгостроково.

## 5. Поточний приклад: телефон

Стан на 2026-07-16:

- `script.js` застосовує `phoneValidate()` до всіх `input[type="tel"]`;
- formatter знає лише код `380`;
- нова форма повинна приймати міжнародні номери;
- `ValidationHelper.php` використовується `LoginController.php` і `OrdersController.php`;
- helper не є правильним dependency для standalone endpoint;
- погоджено `giggsey/libphonenumber-for-php-lite`;
- сумнівний номер підтверджується другим натисканням;
- malformed syntax блокується;
- old formatter не повинен обробляти нове поле.

## 6. CSS boundary

Нові завершені блоки мають окремі assets. У `style.css` додаються тільки справді global або тимчасово необхідні правила з чітким marker.

## 7. Блок вважається модернізованим, якщо

1. визначені markup, CSS, JS і server ownership;
2. global legacy handlers не втручаються;
3. немає двох server-side sources of truth;
4. виконані checks і visual review;
5. є feature document/completion report;
6. блок закомічений окремо.
