<?php
$relatedRaw = isset($_SESSION['res'][$row]) ? $_SESSION['res'][$row] : ($this->data[$row] ?? '');
$relatedRaw = is_string($relatedRaw) ? $relatedRaw : '';

$currentGoodsId = (int)($this->data['id'] ?? 0);
$relatedGoodsCatalog = [];

try {
    if (
        defined('HOST') &&
        defined('USER') &&
        defined('PASSWORD') &&
        defined('DB_NAME')
    ) {
        $relatedDb = @new mysqli(HOST, USER, PASSWORD, DB_NAME);

        if (!$relatedDb->connect_errno) {
            $relatedDb->set_charset('utf8');

            $whereCurrent = $currentGoodsId > 0 ? ' AND id <> ' . $currentGoodsId : '';
            $relatedRes = $relatedDb->query(
                "SELECT id, name, alias, img, price, discount
                 FROM goods
                 WHERE visible = 1{$whereCurrent}
                 ORDER BY name ASC
                 LIMIT 500"
            );

            if ($relatedRes) {
                while ($relatedItem = $relatedRes->fetch_assoc()) {
                    $relatedGoodsCatalog[] = [
                        'id' => (int)$relatedItem['id'],
                        'name' => (string)$relatedItem['name'],
                        'alias' => (string)$relatedItem['alias'],
                        'img' => (string)($relatedItem['img'] ?? ''),
                        'price' => (string)($relatedItem['price'] ?? ''),
                        'discount' => (string)($relatedItem['discount'] ?? ''),
                    ];
                }
            }
        }
    }
} catch (Throwable $e) {
    $relatedGoodsCatalog = [];
}

$relatedGoodsJson = json_encode(
    $relatedGoodsCatalog,
    JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
);
?>
<div class="vg-wrap vg-full vg-related-goods-panel vg-admin-related-goods-half" data-related-goods-widget>
    <style>
        .vg-related-goods-panel {
            padding: 20px 24px;
            background: #fff;
            border-top: 1px solid #d7e0e5;
            border-bottom: 1px solid #d7e0e5;
        }

        .vg-related-goods-panel__title {
            font-size: 24px;
            line-height: 1.25;
            margin-bottom: 6px;
            color: #173042;
        }

        .vg-related-goods-panel__hint {
            margin-bottom: 14px;
            color: #607481;
            font-size: 14px;
        }

        .vg-related-goods-panel__search {
            display: flex;
            gap: 10px;
            align-items: center;
            max-width: 860px;
            margin-bottom: 12px;
        }

        .vg-related-goods-panel__search input {
            flex: 1 1 auto;
            min-height: 40px;
            padding: 8px 12px;
            border: 1px solid #9fb0ba;
            font-size: 16px;
        }

        .vg-related-goods-panel__search button,
        .vg-related-goods-panel__item button {
            min-height: 38px;
            padding: 7px 14px;
            border: 1px solid #173042;
            background: #eef4f7;
            color: #173042;
            cursor: pointer;
        }

        .vg-related-goods-panel__selected,
        .vg-related-goods-panel__results {
            display: grid;
            gap: 8px;
            max-width: 980px;
        }

        .vg-related-goods-panel__caption {
            margin: 14px 0 8px;
            font-weight: 700;
            color: #173042;
        }

        .vg-related-goods-panel__item {
            display: grid;
            grid-template-columns: 54px minmax(0, 1fr) auto;
            gap: 10px;
            align-items: center;
            padding: 8px;
            border: 1px solid #d7e0e5;
            background: #f7fafb;
        }

        .vg-related-goods-panel__item img {
            width: 54px;
            height: 42px;
            object-fit: cover;
            background: #e7eef2;
        }

        .vg-related-goods-panel__name {
            font-weight: 700;
            color: #173042;
        }

        .vg-related-goods-panel__meta {
            font-size: 12px;
            color: #607481;
        }

        .vg-related-goods-panel__empty {
            color: #607481;
            font-style: italic;
            padding: 8px 0;
        }
    </style>

    <div class="vg-related-goods-panel__title">З цим товаром використовується</div>
    <div class="vg-related-goods-panel__hint">
        Почни вводити назву або ID товару, натисни “Додати”. Порядок можна буде уточнити пізніше; зараз товари зберігаються у вибраному порядку.
    </div>

    <textarea name="<?=$row?>" data-related-goods-input hidden><?=htmlspecialchars($relatedRaw, ENT_QUOTES, 'UTF-8')?></textarea>
    <script type="application/json" data-related-goods-catalog><?=$relatedGoodsJson ?: '[]'?></script>

    <div class="vg-related-goods-panel__search">
        <input type="text" placeholder="Пошук товару: назва, alias або ID" data-related-goods-search>
        <button type="button" data-related-goods-clear>Очистити пошук</button>
    </div>

    <div class="vg-related-goods-panel__caption">Вибрані супутні товари</div>
    <div class="vg-related-goods-panel__selected" data-related-goods-selected></div>

    <div class="vg-related-goods-panel__caption">Результати пошуку</div>
    <div class="vg-related-goods-panel__results" data-related-goods-results></div>

    <script>
        (function () {
            const root = document.currentScript.closest('[data-related-goods-widget]');

            if (!root || root.dataset.relatedGoodsReady === '1') {
                return;
            }

            root.dataset.relatedGoodsReady = '1';

            const input = root.querySelector('[data-related-goods-input]');
            const catalogNode = root.querySelector('[data-related-goods-catalog]');
            const searchInput = root.querySelector('[data-related-goods-search]');
            const clearButton = root.querySelector('[data-related-goods-clear]');
            const selectedNode = root.querySelector('[data-related-goods-selected]');
            const resultsNode = root.querySelector('[data-related-goods-results]');

            let catalog = [];

            try {
                catalog = JSON.parse(catalogNode.textContent || '[]');
            } catch (error) {
                catalog = [];
            }

            let selectedIds = parseIds(input.value);

            function parseIds(value) {
                return String(value || '')
                    .split(/[,\s;]+/)
                    .map((item) => parseInt(item, 10))
                    .filter((item, index, arr) => Number.isInteger(item) && item > 0 && arr.indexOf(item) === index);
            }

            function saveIds() {
                input.value = selectedIds.join(',');
            }

            function findById(id) {
                return catalog.find((item) => parseInt(item.id, 10) === parseInt(id, 10));
            }

            function makeItem(item, actionText, action) {
                const row = document.createElement('div');
                row.className = 'vg-related-goods-panel__item';

                const img = document.createElement('img');
                img.alt = item.name || '';
                img.src = item.img ? ('/userfiles/' + item.img) : '/assets/img/additional_offer.png';
                img.onerror = function () { this.onerror = null; this.src = '/assets/img/additional_offer.png'; };

                const body = document.createElement('div');

                const name = document.createElement('div');
                name.className = 'vg-related-goods-panel__name';
                name.textContent = item.name || ('Товар #' + item.id);

                const meta = document.createElement('div');
                meta.className = 'vg-related-goods-panel__meta';
                meta.textContent = 'ID ' + item.id + (item.alias ? ' · ' + item.alias : '');

                body.appendChild(name);
                body.appendChild(meta);

                const button = document.createElement('button');
                button.type = 'button';
                button.textContent = actionText;
                button.addEventListener('click', action);

                row.appendChild(img);
                row.appendChild(body);
                row.appendChild(button);

                return row;
            }

            function renderSelected() {
                selectedNode.innerHTML = '';

                if (!selectedIds.length) {
                    const empty = document.createElement('div');
                    empty.className = 'vg-related-goods-panel__empty';
                    empty.textContent = 'Супутні товари ще не вибрані.';
                    selectedNode.appendChild(empty);
                    return;
                }

                selectedIds.forEach((id) => {
                    const item = findById(id) || {id: id, name: 'Товар #' + id, alias: ''};

                    selectedNode.appendChild(makeItem(item, 'Прибрати', function () {
                        selectedIds = selectedIds.filter((selectedId) => selectedId !== id);
                        saveIds();
                        renderSelected();
                        renderResults();
                    }));
                });
            }

            function renderResults() {
                const query = String(searchInput.value || '').trim().toLowerCase();
                resultsNode.innerHTML = '';

                if (!query) {
                    const empty = document.createElement('div');
                    empty.className = 'vg-related-goods-panel__empty';
                    empty.textContent = 'Введи частину назви або ID товару.';
                    resultsNode.appendChild(empty);
                    return;
                }

                const found = catalog
                    .filter((item) => !selectedIds.includes(parseInt(item.id, 10)))
                    .filter((item) => {
                        const haystack = [
                            item.id,
                            item.name,
                            item.alias
                        ].join(' ').toLowerCase();

                        return haystack.indexOf(query) !== -1;
                    })
                    .slice(0, 20);

                if (!found.length) {
                    const empty = document.createElement('div');
                    empty.className = 'vg-related-goods-panel__empty';
                    empty.textContent = 'Нічого не знайдено.';
                    resultsNode.appendChild(empty);
                    return;
                }

                found.forEach((item) => {
                    resultsNode.appendChild(makeItem(item, 'Додати', function () {
                        const id = parseInt(item.id, 10);

                        if (!selectedIds.includes(id)) {
                            selectedIds.push(id);
                        }

                        saveIds();
                        renderSelected();
                        renderResults();
                    }));
                });
            }

            searchInput.addEventListener('input', renderResults);
            clearButton.addEventListener('click', function () {
                searchInput.value = '';
                renderResults();
                searchInput.focus();
            });

            renderSelected();
            renderResults();
        }());
    </script>
    <script>
        /* v0.6.18 move related widget bottom */
        (function () {
            var root = document.currentScript.closest('[data-related-goods-widget]');

            if (!root || root.dataset.relatedGoodsMoved === '1') {
                return;
            }

            root.dataset.relatedGoodsMoved = '1';

            window.setTimeout(function () {
                var form = root.closest('form');

                if (!form || !root.parentNode) {
                    return;
                }

                form.appendChild(root);
            }, 0);
        }());
    </script>
</div>