(function (window, document) {
    'use strict';

    var STORAGE_KEY = 'fp_supplier_quote_list_v1';
    var MAX_ITEMS = 60;
    var SUMMARY_START = '--- Список на прорахунок ---';
    var SUMMARY_END = '--- Кінець списку ---';

    function clean(value, limit) {
        var normalized = String(value || '')
            .replace(/\s+/g, ' ')
            .trim();

        if (normalized.length > limit) {
            normalized = normalized.slice(0, limit);
        }

        return normalized;
    }

    function normalizeQuantity(value) {
        var number = parseInt(value, 10);

        if (!Number.isFinite(number) || number < 1) {
            return 1;
        }

        return Math.min(number, 99999);
    }

    function normalizeMethods(value) {
        if (!Array.isArray(value)) {
            return [];
        }

        return value
            .map(function (item) {
                return clean(item, 120);
            })
            .filter(Boolean)
            .slice(0, 20);
    }

    function normalizeItem(item) {
        if (!item || typeof item !== 'object') {
            return null;
        }

        var supplier = clean(item.supplier, 40);
        var externalId = clean(item.external_id, 120);
        var sku = clean(item.sku, 120);
        var name = clean(item.name, 220);

        if (!supplier || (!externalId && !sku) || !name) {
            return null;
        }

        return {
            supplier: supplier,
            external_id: externalId,
            sku: sku,
            name: name,
            category: clean(item.category, 160),
            image: clean(item.image, 1000),
            methods: normalizeMethods(item.methods),
            selected_method: clean(item.selected_method, 120),
            quantity: normalizeQuantity(item.quantity)
        };
    }

    function keyFor(item) {
        return (
            clean(item.supplier, 40)
            + ':'
            + clean(item.external_id || item.sku, 120)
        );
    }

    function loadItems() {
        try {
            var raw = window.sessionStorage.getItem(STORAGE_KEY);

            if (!raw) {
                return [];
            }

            var parsed = JSON.parse(raw);

            if (!Array.isArray(parsed)) {
                return [];
            }

            var unique = {};
            var result = [];

            parsed.forEach(function (item) {
                var normalized = normalizeItem(item);

                if (!normalized) {
                    return;
                }

                var key = keyFor(normalized);

                if (!key || unique[key]) {
                    return;
                }

                unique[key] = true;
                result.push(normalized);
            });

            return result.slice(0, MAX_ITEMS);
        } catch (error) {
            return [];
        }
    }

    function saveItems(items) {
        try {
            window.sessionStorage.setItem(
                STORAGE_KEY,
                JSON.stringify(items.slice(0, MAX_ITEMS))
            );
        } catch (error) {
            return false;
        }

        return true;
    }

    function createElement(tagName, className, text) {
        var element = document.createElement(tagName);

        if (className) {
            element.className = className;
        }

        if (typeof text !== 'undefined') {
            element.textContent = text;
        }

        return element;
    }

    function allCounts() {
        return document.querySelectorAll('[data-fp-quote-count]');
    }

    function updateCounts(items) {
        var count = items.length;

        allCounts().forEach(function (node) {
            node.textContent = String(count);
            node.setAttribute(
                'aria-label',
                count === 1
                    ? '1 позиція у списку на прорахунок'
                    : String(count) + ' позицій у списку на прорахунок'
            );
        });
    }

    function setAddButtonState(button, items) {
        var supplier = clean(
            button.getAttribute('data-fp-quote-supplier'),
            40
        );
        var externalId = clean(
            button.getAttribute('data-fp-quote-external-id'),
            120
        );
        var sku = clean(
            button.getAttribute('data-fp-quote-sku'),
            120
        );
        var key = supplier + ':' + (externalId || sku);
        var exists = items.some(function (item) {
            return keyFor(item) === key;
        });

        button.classList.toggle('is-added', exists);
        button.setAttribute('aria-pressed', exists ? 'true' : 'false');
        button.textContent = exists
            ? 'У списку ✓'
            : 'На прорахунок';
    }

    function syncAddButtons(items) {
        document
            .querySelectorAll('[data-fp-quote-add]')
            .forEach(function (button) {
                setAddButtonState(button, items);
            });
    }

    function parseMethods(button) {
        var raw = button.getAttribute('data-fp-quote-methods') || '[]';

        try {
            return normalizeMethods(JSON.parse(raw));
        } catch (error) {
            return [];
        }
    }

    function itemFromButton(button) {
        return normalizeItem({
            supplier: button.getAttribute('data-fp-quote-supplier'),
            external_id: button.getAttribute('data-fp-quote-external-id'),
            sku: button.getAttribute('data-fp-quote-sku'),
            name: button.getAttribute('data-fp-quote-name'),
            category: button.getAttribute('data-fp-quote-category'),
            image: button.getAttribute('data-fp-quote-image'),
            methods: parseMethods(button),
            selected_method: '',
            quantity: 1
        });
    }

    function stripGeneratedSummary(value) {
        var text = String(value || '');
        var start = text.indexOf(SUMMARY_START);
        var end = text.indexOf(SUMMARY_END);

        if (start < 0 || end < start) {
            return text.trim();
        }

        end += SUMMARY_END.length;

        return (
            text.slice(0, start)
            + '\n'
            + text.slice(end)
        ).trim();
    }

    function buildSummary(items) {
        var lines = [
            SUMMARY_START,
            'Запит на прорахунок брендування',
            ''
        ];

        items.forEach(function (item, index) {
            lines.push(
                String(index + 1) + '. ' + item.name
            );

            if (item.sku) {
                lines.push('   SKU: ' + item.sku);
            }

            if (item.external_id && item.external_id !== item.sku) {
                lines.push('   Код позиції: ' + item.external_id);
            }

            lines.push(
                '   Кількість: ' + String(item.quantity)
            );

            lines.push(
                '   Нанесення: '
                + (
                    item.selected_method
                    || 'не вибрано'
                )
            );

            lines.push('');
        });

        lines.push(SUMMARY_END);

        return lines.join('\n');
    }

    function syncRequestForm(items) {
        var request = document.querySelector(
            '[data-fp-quote-request]'
        );

        if (!request) {
            return;
        }

        request.hidden = items.length === 0;

        var form = request.querySelector(
            '[data-fp-comm-form]'
        );

        if (!form) {
            return;
        }

        var productName = form.querySelector(
            'input[name="product_name"]'
        );
        var quantity = form.querySelector(
            'input[name="quantity_requested"]'
        );

        if (productName) {
            productName.value = (
                'Список на прорахунок — '
                + String(items.length)
                + (
                    items.length === 1
                        ? ' позиція'
                        : ' позицій'
                )
            );
        }

        if (quantity) {
            quantity.value = String(
                items.reduce(function (sum, item) {
                    return sum + normalizeQuantity(item.quantity);
                }, 0)
            );
        }
    }

    function renderItem(item, items) {
        var row = createElement(
            'div',
            'fp-supplier-quote__item'
        );
        row.setAttribute(
            'data-fp-quote-item',
            keyFor(item)
        );

        var media = createElement(
            'div',
            'fp-supplier-quote__item-media'
        );

        if (item.image) {
            var image = document.createElement('img');
            image.src = item.image;
            image.alt = '';
            image.loading = 'lazy';
            image.decoding = 'async';
            image.referrerPolicy = 'no-referrer';
            media.appendChild(image);
        } else {
            media.appendChild(
                createElement(
                    'span',
                    'fp-supplier-quote__item-media-placeholder',
                    'FP'
                )
            );
        }

        row.appendChild(media);

        var title = createElement(
            'div',
            'fp-supplier-quote__item-title'
        );
        title.appendChild(
            createElement(
                'strong',
                '',
                item.name
            )
        );

        var meta = [];

        if (item.category) {
            meta.push(item.category);
        }

        if (item.sku) {
            meta.push('SKU: ' + item.sku);
        }

        if (meta.length) {
            title.appendChild(
                createElement(
                    'small',
                    '',
                    meta.join(' · ')
                )
            );
        }

        row.appendChild(title);

        var quantityLabel = createElement(
            'label',
            'fp-supplier-quote__field'
        );
        quantityLabel.appendChild(
            createElement(
                'span',
                '',
                'Кількість'
            )
        );

        var quantityInput = document.createElement('input');
        quantityInput.type = 'number';
        quantityInput.min = '1';
        quantityInput.max = '99999';
        quantityInput.step = '1';
        quantityInput.value = String(item.quantity);
        quantityInput.setAttribute(
            'data-fp-quote-quantity',
            keyFor(item)
        );
        quantityLabel.appendChild(quantityInput);
        row.appendChild(quantityLabel);

        var methodLabel = createElement(
            'label',
            'fp-supplier-quote__field fp-supplier-quote__field--method'
        );
        methodLabel.appendChild(
            createElement(
                'span',
                '',
                'Спосіб нанесення'
            )
        );

        var select = document.createElement('select');
        select.setAttribute(
            'data-fp-quote-method',
            keyFor(item)
        );

        var emptyOption = document.createElement('option');
        emptyOption.value = '';
        emptyOption.textContent = 'Не вибрано';
        select.appendChild(emptyOption);

        item.methods.forEach(function (method) {
            var option = document.createElement('option');
            option.value = method;
            option.textContent = method;

            if (item.selected_method === method) {
                option.selected = true;
            }

            select.appendChild(option);
        });

        methodLabel.appendChild(select);
        row.appendChild(methodLabel);

        var remove = createElement(
            'button',
            'fp-supplier-quote__remove',
            'Видалити'
        );
        remove.type = 'button';
        remove.setAttribute(
            'data-fp-quote-remove',
            keyFor(item)
        );
        row.appendChild(remove);

        return row;
    }

    function render(items) {
        updateCounts(items);
        syncQuoteRailUtility(items);
        syncAddButtons(items);
        syncRequestForm(items);

        var empty = document.querySelector(
            '[data-fp-quote-empty]'
        );
        var clear = document.querySelector(
            '[data-fp-quote-clear]'
        );
        var container = document.querySelector(
            '[data-fp-quote-items]'
        );

        if (empty) {
            empty.hidden = items.length > 0;
        }

        if (clear) {
            clear.hidden = items.length === 0;
        }

        if (!container) {
            return;
        }

        container.replaceChildren();

        items.forEach(function (item) {
            container.appendChild(
                renderItem(
                    item,
                    items
                )
            );
        });
    }

    function commit(items) {
        saveItems(items);
        render(items);
    }

    function addItem(button) {
        var items = loadItems();
        var item = itemFromButton(button);

        if (!item) {
            return;
        }

        var key = keyFor(item);
        var existingIndex = items.findIndex(function (existing) {
            return keyFor(existing) === key;
        });

        if (existingIndex >= 0) {
            items.splice(existingIndex, 1);
            commit(items);
            return;
        }

        if (items.length >= MAX_ITEMS) {
            return;
        }

        items.push(item);
        commit(items);
    }

    function updateQuantity(key, value) {
        var items = loadItems();

        items.forEach(function (item) {
            if (keyFor(item) === key) {
                item.quantity = normalizeQuantity(value);
            }
        });

        commit(items);
    }

    function updateMethod(key, value) {
        var items = loadItems();

        items.forEach(function (item) {
            if (keyFor(item) === key) {
                item.selected_method = clean(value, 120);
            }
        });

        commit(items);
    }

    function removeItem(key) {
        var items = loadItems().filter(function (item) {
            return keyFor(item) !== key;
        });

        commit(items);
    }

    function clearItems() {
        commit([]);
    }

    document.addEventListener('click', function (event) {
        var galleryThumb = event.target.closest('[data-fp-gallery-thumb]');

        if (galleryThumb) {
            event.preventDefault();

            var gallery = galleryThumb.closest('[data-fp-supplier-gallery]');
            var mainImage = gallery
                ? gallery.querySelector('[data-fp-gallery-main]')
                : null;
            var nextSource = clean(
                galleryThumb.getAttribute('data-fp-gallery-src'),
                1000
            );

            if (mainImage && nextSource) {
                mainImage.src = nextSource;

                gallery
                    .querySelectorAll('[data-fp-gallery-thumb]')
                    .forEach(function (button) {
                        button.classList.toggle(
                            'is-active',
                            button === galleryThumb
                        );
                    });
            }

            return;
        }

        var add = event.target.closest('[data-fp-quote-add]');

        if (add) {
            event.preventDefault();
            addItem(add);
            return;
        }

        var remove = event.target.closest('[data-fp-quote-remove]');

        if (remove) {
            event.preventDefault();
            removeItem(
                remove.getAttribute('data-fp-quote-remove') || ''
            );
            return;
        }

        var clear = event.target.closest('[data-fp-quote-clear]');

        if (clear) {
            event.preventDefault();
            clearItems();
        }
    });

    document.addEventListener('change', function (event) {
        var quantity = event.target.closest('[data-fp-quote-quantity]');

        if (quantity) {
            updateQuantity(
                quantity.getAttribute('data-fp-quote-quantity') || '',
                quantity.value
            );
            return;
        }

        var method = event.target.closest('[data-fp-quote-method]');

        if (method) {
            updateMethod(
                method.getAttribute('data-fp-quote-method') || '',
                method.value
            );
        }
    });

    document.addEventListener(
        'submit',
        function (event) {
            var form = event.target.closest(
                '[data-fp-quote-request] [data-fp-comm-form]'
            );

            if (!form) {
                return;
            }

            var items = loadItems();

            if (!items.length) {
                event.preventDefault();
                return;
            }

            var message = form.querySelector(
                'textarea[name="message"]'
            );

            if (!message) {
                return;
            }

            var comment = stripGeneratedSummary(
                message.value
            );
            var summary = buildSummary(items);

            message.value = (
                summary
                + (
                    comment
                        ? '\n\nКоментар клієнта:\n' + comment
                        : ''
                )
            );

            syncRequestForm(items);
        },
        true
    );

    function watchAcceptedSuccess() {
        var request = document.querySelector(
            '[data-fp-quote-request]'
        );

        if (!request || typeof MutationObserver !== 'function') {
            return;
        }

        var status = request.querySelector(
            '[data-fp-comm-status]'
        );

        if (!status) {
            return;
        }

        var observer = new MutationObserver(function () {
            if (
                status.classList.contains(
                    'fp-product-communication-form__status--success'
                )
                && status.textContent.trim() !== ''
            ) {
                clearItems();
            }
        });

        observer.observe(
            status,
            {
                childList: true,
                characterData: true,
                subtree: true,
                attributes: true,
                attributeFilter: ['class']
            }
        );
    }


    function findSearchControl() {
        var inputs = Array.prototype.slice.call(
            document.querySelectorAll('input')
        );

        for (var index = 0; index < inputs.length; index += 1) {
            var input = inputs[index];
            var descriptor = [
                input.getAttribute('type') || '',
                input.getAttribute('name') || '',
                input.getAttribute('id') || '',
                input.getAttribute('class') || '',
                input.getAttribute('placeholder') || '',
                input.getAttribute('aria-label') || ''
            ]
                .join(' ')
                .toLowerCase();

            if (
                descriptor.indexOf('search') !== -1
                || descriptor.indexOf('пошук') !== -1
            ) {
                return input;
            }
        }

        var roleSearch = document.querySelector('[role="search"]');

        if (roleSearch) {
            return roleSearch.querySelector('input') || roleSearch;
        }

        return null;
    }

    function findSearchBand(control) {
        if (!control) {
            return null;
        }

        var viewportWidth = Math.max(
            document.documentElement.clientWidth || 0,
            window.innerWidth || 0
        );
        var node = control.parentElement;
        var fallback = null;

        while (
            node
            && node !== document.body
            && node !== document.documentElement
        ) {
            var rect = node.getBoundingClientRect();
            var descriptor = [
                node.getAttribute('id') || '',
                node.getAttribute('class') || '',
                node.getAttribute('role') || ''
            ]
                .join(' ')
                .toLowerCase();

            if (descriptor.indexOf('search') !== -1) {
                fallback = node;
            }

            if (
                rect.width >= viewportWidth * 0.55
                && rect.height >= 42
                && rect.height <= 170
            ) {
                return node;
            }

            node = node.parentElement;
        }

        return fallback;
    }

    function createQuoteRailUtility() {
        var existing = document.querySelector(
            '.fp-quote-rail-utility'
        );

        if (existing) {
            return existing;
        }

        var supplierNav = document.querySelector(
            '.fp-supplier-catalog-nav a'
        );

        if (!supplierNav) {
            return null;
        }

        /*
         * FP_QUOTE_DIRECT_VIEW_AND_HEADER_STATE_V1
         *
         * This stays on the existing supplier page. `view=quote-list`
         * controls presentation only; the hash targets the existing list.
         */
        var href = supplierNav.getAttribute('href') || '/supplier-catalog/';

        try {
            var quoteUrl = new URL(
                href,
                window.location.href
            );
            quoteUrl.searchParams.set(
                'view',
                'quote-list'
            );
            quoteUrl.hash = 'fp-quote-list';
            href = quoteUrl.toString();
        } catch (error) {
            href = href.split('#')[0];
            href += (
                href.indexOf('?') === -1
                    ? '?'
                    : '&'
            ) + 'view=quote-list#fp-quote-list';
        }

        var link = document.createElement('a');
        link.className = 'fp-quote-rail-utility';
        link.href = href;
        link.hidden = true;
        link.setAttribute(
            'aria-label',
            'Відкрити список на прорахунок'
        );
        link.setAttribute(
            'title',
            'Список на прорахунок'
        );

        var icon = document.createElement('span');
        icon.className = 'fp-quote-rail-utility__icon';
        icon.setAttribute('aria-hidden', 'true');

        var svgNamespace = 'http://www.w3.org/2000/svg';
        var svg = document.createElementNS(
            svgNamespace,
            'svg'
        );
        svg.setAttribute('viewBox', '0 0 24 24');
        svg.setAttribute('focusable', 'false');
        svg.setAttribute('aria-hidden', 'true');

        var board = document.createElementNS(
            svgNamespace,
            'path'
        );
        board.setAttribute(
            'd',
            'M9 2h6l1 2h3v18H5V4h3l1-2Zm1.2 2-.45 1h4.5l-.45-1h-3.6Z'
        );
        svg.appendChild(board);

        ['M8 9h8v1.8H8z', 'M8 12.8h8v1.8H8z', 'M8 16.6h8v1.8H8z'].forEach(
            function (shape) {
                var path = document.createElementNS(
                    svgNamespace,
                    'path'
                );
                path.setAttribute('d', shape);
                svg.appendChild(path);
            }
        );

        icon.appendChild(svg);
        link.appendChild(icon);

        var count = document.createElement('span');
        count.className = 'fp-quote-rail-utility__count';
        count.setAttribute('data-fp-quote-count', '');
        count.setAttribute(
            'aria-label',
            '0 позицій у списку на прорахунок'
        );
        count.textContent = '0';
        link.appendChild(count);

        return link;
    }

    function mountQuoteRailUtility() {
        var utility = createQuoteRailUtility();
        var rail;

        if (!utility) {
            return null;
        }

        rail = document.querySelector(
            '.header__sidebar'
        );

        if (!rail) {
            return null;
        }

        if (
            utility.parentNode !== rail
            || rail.firstElementChild !== utility
        ) {
            rail.insertBefore(
                utility,
                rail.firstElementChild
            );
        }

        return utility;
    }

    function syncQuoteRailUtility(items) {
        var utility = mountQuoteRailUtility();

        if (!utility) {
            return;
        }

        var hasItems = Array.isArray(items) && items.length > 0;
        utility.hidden = !hasItems;
        utility.classList.toggle('is-visible', hasItems);
        utility.setAttribute(
            'aria-hidden',
            hasItems ? 'false' : 'true'
        );

        var header = utility.closest(
            '.fp-site-header'
        );

        if (header) {
            header.classList.toggle(
                'fp-site-header--quote-visible',
                hasItems
            );
        }
    }


    var supplierSearchTimer = null;
    var supplierSearchRequest = 0;

    function supplierSearchBaseUrl() {
        var supplierNav = document.querySelector(
            '.fp-supplier-catalog-nav a'
        );
        if (!supplierNav) {
            return '';
        }
        return (
            supplierNav.getAttribute('href')
            || '/supplier-catalog/'
        ).split('#')[0];
    }

    function supplierSearchInputs() {
        var candidates = Array.prototype.slice.call(
            document.querySelectorAll(
                'input[type="search"], '
                + 'input[class*="search"], '
                + 'input[id*="search"], '
                + 'input[name*="search"]'
            )
        );
        return candidates.filter(function (input, index) {
            if (candidates.indexOf(input) !== index) {
                return false;
            }
            var descriptor = [
                input.getAttribute('placeholder') || '',
                input.getAttribute('aria-label') || '',
                input.getAttribute('name') || '',
                input.getAttribute('id') || '',
                input.getAttribute('class') || ''
            ].join(' ').toLowerCase();
            return (
                descriptor.indexOf('search') !== -1
                || descriptor.indexOf('пошук') !== -1
            );
        });
    }

    function supplierSearchSurfaces() {
        return Array.prototype.slice.call(
            document.querySelectorAll(
                '.fp-search-suggestions, .fp-suggestion-surface'
            )
        );
    }

    function clearSupplierSearchGroups() {
        document.querySelectorAll(
            '[data-fp-supplier-search-group]'
        ).forEach(function (node) {
            node.remove();
        });
    }

    function renderSupplierSearchGroup(query, results) {
        clearSupplierSearchGroups();
        if (!query || !Array.isArray(results) || results.length === 0) {
            return;
        }

        supplierSearchSurfaces().forEach(function (surface) {
            var group = document.createElement('section');
            group.className = 'fp-supplier-search-group';
            group.setAttribute('data-fp-supplier-search-group', '');
            group.setAttribute(
                'aria-label',
                'Товари для брендування'
            );

            var title = document.createElement('div');
            title.className = 'fp-supplier-search-group__title';
            title.textContent = 'Товари для брендування';
            group.appendChild(title);

            results.forEach(function (item) {
                var link = document.createElement('a');
                link.className = (
                    'fp-suggestion-row '
                    + 'fp-supplier-search-group__item'
                );
                link.href = item.url || '#';

                var name = document.createElement('strong');
                name.textContent = item.name || '';
                link.appendChild(name);

                var metaText = [
                    item.category || '',
                    item.sku ? 'SKU: ' + item.sku : ''
                ].filter(Boolean).join(' · ');

                if (metaText) {
                    var meta = document.createElement('span');
                    meta.className = 'fp-supplier-search-group__meta';
                    meta.textContent = metaText;
                    link.appendChild(meta);
                }

                group.appendChild(link);
            });

            surface.appendChild(group);
            surface.hidden = false;
        });
    }

    function runSupplierSearch(query) {
        var baseUrl = supplierSearchBaseUrl();
        if (!baseUrl) {
            return;
        }

        query = String(query || '').trim();
        if (query.length < 2) {
            clearSupplierSearchGroups();
            return;
        }

        supplierSearchRequest += 1;
        var requestId = supplierSearchRequest;
        var separator = baseUrl.indexOf('?') === -1 ? '?' : '&';
        var url = (
            baseUrl
            + separator
            + 'fp_supplier_search=1&q='
            + encodeURIComponent(query)
        );

        window.fetch(
            url,
            {
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json'
                }
            }
        )
            .then(function (response) {
                if (!response.ok) {
                    throw new Error(
                        'supplier search HTTP ' + response.status
                    );
                }
                return response.json();
            })
            .then(function (payload) {
                if (requestId !== supplierSearchRequest) {
                    return;
                }
                renderSupplierSearchGroup(
                    query,
                    payload && payload.results
                        ? payload.results
                        : []
                );
            })
            .catch(function () {
                if (requestId === supplierSearchRequest) {
                    clearSupplierSearchGroups();
                }
            });
    }

    function bindSupplierSearchBridge() {
        if (!supplierSearchBaseUrl()) {
            return;
        }

        supplierSearchInputs().forEach(function (input) {
            if (
                input.getAttribute(
                    'data-fp-supplier-search-bound'
                ) === '1'
            ) {
                return;
            }

            input.setAttribute(
                'data-fp-supplier-search-bound',
                '1'
            );

            input.addEventListener(
                'input',
                function () {
                    var value = input.value || '';
                    window.clearTimeout(supplierSearchTimer);
                    supplierSearchTimer = window.setTimeout(
                        function () {
                            runSupplierSearch(value);
                        },
                        220
                    );
                }
            );
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        mountQuoteRailUtility();
        render(loadItems());
        watchAcceptedSuccess();
        bindSupplierSearchBridge();
    });
})(window, document);
