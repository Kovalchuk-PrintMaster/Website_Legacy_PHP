(function () {
    'use strict';

    var formSelector =
        'form.fp-search-form[data-fp-search-suggestions]';
    var historyKey = 'forprint_search_history_v1';
    var historyLimit = 8;

    function normalize(value) {
        return String(value || '')
            .replace(/\s+/g, ' ')
            .trim();
    }

    function historyRead() {
        try {
            var decoded = JSON.parse(
                window.localStorage.getItem(historyKey)
                || '[]'
            );

            if (!Array.isArray(decoded)) {
                return [];
            }

            return decoded
                .map(normalize)
                .filter(Boolean)
                .slice(0, historyLimit);
        } catch (error) {
            return [];
        }
    }

    function historySave(value) {
        var query = normalize(value);

        if (!query) {
            return;
        }

        var key = query.toLocaleLowerCase('uk-UA');
        var next = [query];

        historyRead().forEach(function (item) {
            if (
                item.toLocaleLowerCase('uk-UA')
                !== key
            ) {
                next.push(item);
            }
        });

        try {
            window.localStorage.setItem(
                historyKey,
                JSON.stringify(
                    next.slice(0, historyLimit)
                )
            );
        } catch (error) {
            /* Storage can be unavailable in private modes. */
        }
    }

    function createList(input) {
        var list = document.createElement('div');

        list.className = 'fp-search-suggestions fp-suggestion-surface';
        list.hidden = true;
        list.setAttribute('role', 'listbox');
        list.setAttribute(
            'aria-label',
            'Пошук товарів'
        );

        document.body.appendChild(list);

        input.setAttribute(
            'aria-autocomplete',
            'list'
        );
        input.setAttribute(
            'aria-expanded',
            'false'
        );

        return list;
    }

    function bindForm(form) {
        if (
            !form
            || form.dataset.fpSearchSuggestionsBound === '1'
        ) {
            return;
        }

        var input = form.querySelector(
            'input[type="search"][name="search"]'
        );
        var endpoint = normalize(
            form.dataset.fpSearchSuggestions
        );

        if (!input || !endpoint) {
            return;
        }

        form.dataset.fpSearchSuggestionsBound = '1';

        input.setAttribute('autocomplete', 'off');
        input.setAttribute('spellcheck', 'false');
        input.setAttribute('autocapitalize', 'off');

        var list = createList(input);
        var debounceTimer = null;
        var activeRequest = null;
        var activeIndex = -1;
        var latestQuery = '';
        var currentActions = [];

        function setExpanded(expanded) {
            input.setAttribute(
                'aria-expanded',
                expanded ? 'true' : 'false'
            );
        }

        function positionList() {
            if (list.hidden) {
                return;
            }

            var rect = input.getBoundingClientRect();
            var width = Math.min(
                Math.max(rect.width * 0.75, 300),
                510
            );
            var left = rect.left;
            var viewportPadding = 12;

            if (
                left + width
                > window.innerWidth - viewportPadding
            ) {
                left = Math.max(
                    viewportPadding,
                    window.innerWidth
                    - viewportPadding
                    - width
                );
            }

            list.style.left = Math.round(left) + 'px';
            list.style.width = Math.round(width) + 'px';

            var preferredTop = rect.bottom + 5;
            var estimatedHeight = Math.min(
                list.scrollHeight || 260,
                360
            );
            var fitsBelow =
                preferredTop + estimatedHeight
                <= window.innerHeight - viewportPadding;

            if (fitsBelow || rect.top < estimatedHeight) {
                list.style.top =
                    Math.round(preferredTop) + 'px';
            } else {
                list.style.top =
                    Math.round(
                        Math.max(
                            viewportPadding,
                            rect.top
                            - estimatedHeight
                            - 5
                        )
                    )
                    + 'px';
            }
        }

        function closeList() {
            list.hidden = true;
            list.innerHTML = '';
            currentActions = [];
            activeIndex = -1;
            setExpanded(false);
        }

        function openList() {
            if (currentActions.length === 0) {
                closeList();
                return;
            }

            list.hidden = false;
            setExpanded(true);

            window.requestAnimationFrame(
                positionList
            );
        }

        function section(title) {
            var wrapper = document.createElement('div');
            var heading = document.createElement('div');

            wrapper.className =
                'fp-search-suggestions__section';
            heading.className =
                'fp-search-suggestions__heading';
            heading.textContent = title;

            wrapper.appendChild(heading);
            list.appendChild(wrapper);

            return wrapper;
        }

        function action(
            wrapper,
            label,
            className,
            onSelect,
            options
        ) {
            var button = document.createElement('button');
            var settings = options || {};

            button.type = 'button';
            button.className =
                'fp-search-suggestions__action fp-suggestion-row '
                + className;
            button.setAttribute('role', 'option');
            button.setAttribute(
                'aria-selected',
                'false'
            );

            if (settings.product === true) {
                var imageUrl = normalize(
                    settings.image
                );

                if (imageUrl) {
                    var image =
                        document.createElement('img');

                    image.className =
                        'fp-search-suggestions__product-image fp-suggestion-row__image';
                    image.src = imageUrl;
                    image.alt = '';
                    image.loading = 'lazy';
                    button.appendChild(image);
                } else {
                    var fallback =
                        document.createElement('span');

                    fallback.className =
                        'fp-search-suggestions__product-fallback';
                    fallback.setAttribute(
                        'aria-hidden',
                        'true'
                    );
                    button.appendChild(fallback);
                }

                var productName =
                    document.createElement('span');

                productName.className =
                    'fp-search-suggestions__product-name fp-suggestion-row__name';
                productName.textContent = label;
                button.appendChild(productName);
            } else {
                button.textContent = label;
            }

            button.addEventListener(
                'pointerdown',
                function (event) {
                    event.preventDefault();
                    onSelect();
                }
            );

            button.addEventListener(
                'click',
                function (event) {
                    event.preventDefault();
                    onSelect();
                }
            );

            wrapper.appendChild(button);
            currentActions.push(button);
        }

        function setActive(index) {
            currentActions.forEach(function (item) {
                item.classList.remove(
                    'fp-search-suggestions__action--active'
                );
                item.setAttribute(
                    'aria-selected',
                    'false'
                );
            });

            if (
                index < 0
                || index >= currentActions.length
            ) {
                activeIndex = -1;
                return;
            }

            activeIndex = index;
            currentActions[index].classList.add(
                'fp-search-suggestions__action--active'
            );
            currentActions[index].setAttribute(
                'aria-selected',
                'true'
            );
            currentActions[index].scrollIntoView({
                block: 'nearest'
            });
        }

        function submitQuery(query) {
            var prepared = normalize(query);

            if (!prepared) {
                return;
            }

            input.value = prepared;
            historySave(prepared);
            closeList();

            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit();
                return;
            }

            form.submit();
        }

        function openProduct(item) {
            var url = normalize(item && item.url);

            if (!url) {
                submitQuery(item && item.value);
                return;
            }

            historySave(item.value || item.name || '');
            closeList();
            window.location.assign(url);
        }

        function renderHistory(filter) {
            var normalizedFilter = normalize(filter)
                .toLocaleLowerCase('uk-UA');
            var history = historyRead().filter(
                function (item) {
                    return !normalizedFilter
                        || item
                            .toLocaleLowerCase('uk-UA')
                            .indexOf(normalizedFilter)
                            !== -1;
                }
            );

            list.innerHTML = '';
            currentActions = [];
            activeIndex = -1;

            if (history.length === 0) {
                closeList();
                return;
            }

            var wrapper = section('Останні пошуки');

            history.forEach(function (item) {
                action(
                    wrapper,
                    item,
                    'fp-search-suggestions__action--recent',
                    function () {
                        submitQuery(item);
                    }
                );
            });

            openList();
        }

        function renderPayload(payload, query) {
            if (
                query !== latestQuery
                || !payload
                || payload.ok !== true
            ) {
                return;
            }

            list.innerHTML = '';
            currentActions = [];
            activeIndex = -1;

            var allWrapper = section('Пошук');

            action(
                allWrapper,
                'Показати всі результати за запитом «'
                    + query
                    + '»',
                'fp-search-suggestions__action--all',
                function () {
                    submitQuery(query);
                }
            );

            var recent = historyRead().filter(
                function (item) {
                    return item
                        .toLocaleLowerCase('uk-UA')
                        .indexOf(
                            query.toLocaleLowerCase('uk-UA')
                        )
                        !== -1;
                }
            );

            if (recent.length > 0) {
                var recentWrapper =
                    section('Останні пошуки');

                recent.slice(0, 3).forEach(
                    function (item) {
                        action(
                            recentWrapper,
                            item,
                            'fp-search-suggestions__action--recent',
                            function () {
                                submitQuery(item);
                            }
                        );
                    }
                );
            }

            var products = Array.isArray(payload.items)
                ? payload.items
                : [];

            if (products.length > 0) {
                var productWrapper =
                    section('Товари');

                products.forEach(function (item) {
                    action(
                        productWrapper,
                        normalize(item.name),
                        'fp-search-suggestions__action--product',
                        function () {
                            openProduct(item);
                        },
                        {
                            product: true,
                            image: item.image
                        }
                    );
                });
            } else {
                var empty = document.createElement('div');

                empty.className =
                    'fp-search-suggestions__empty';
                empty.textContent =
                    'Окремих товарів не знайдено. '
                    + 'Можна виконати загальний пошук.';

                list.appendChild(empty);
            }

            openList();
        }

        function requestSuggestions(query) {
            latestQuery = query;

            if (activeRequest) {
                activeRequest.abort();
            }

            activeRequest =
                typeof AbortController !== 'undefined'
                    ? new AbortController()
                    : null;

            var separator =
                endpoint.indexOf('?') === -1
                    ? '?'
                    : '&';
            var url =
                endpoint
                + separator
                + 'q='
                + encodeURIComponent(query);
            var options = {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json'
                }
            };

            if (activeRequest) {
                options.signal = activeRequest.signal;
            }

            fetch(url, options)
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error(
                            'Suggestion endpoint returned '
                            + response.status
                        );
                    }

                    return response.json();
                })
                .then(function (payload) {
                    renderPayload(payload, query);
                })
                .catch(function (error) {
                    if (
                        error
                        && error.name === 'AbortError'
                    ) {
                        return;
                    }

                    console.error(
                        'ForPrint search suggestions:',
                        error
                    );
                    closeList();
                });
        }

        input.addEventListener(
            'input',
            function () {
                window.clearTimeout(debounceTimer);

                var query = normalize(input.value);

                if (query.length < 2) {
                    latestQuery = query;

                    if (activeRequest) {
                        activeRequest.abort();
                    }

                    renderHistory(query);
                    return;
                }

                debounceTimer = window.setTimeout(
                    function () {
                        requestSuggestions(query);
                    },
                    180
                );
            }
        );

        input.addEventListener(
            'focus',
            function () {
                var query = normalize(input.value);

                if (query.length < 2) {
                    renderHistory(query);
                    return;
                }

                requestSuggestions(query);
            }
        );

        input.addEventListener(
            'keydown',
            function (event) {
                if (
                    list.hidden
                    || currentActions.length === 0
                ) {
                    return;
                }

                if (event.key === 'ArrowDown') {
                    event.preventDefault();
                    setActive(
                        activeIndex + 1
                        >= currentActions.length
                            ? 0
                            : activeIndex + 1
                    );
                    return;
                }

                if (event.key === 'ArrowUp') {
                    event.preventDefault();
                    setActive(
                        activeIndex - 1 < 0
                            ? currentActions.length - 1
                            : activeIndex - 1
                    );
                    return;
                }

                if (
                    event.key === 'Enter'
                    && activeIndex >= 0
                ) {
                    event.preventDefault();
                    currentActions[activeIndex].click();
                    return;
                }

                if (event.key === 'Escape') {
                    event.preventDefault();
                    closeList();
                }
            }
        );

        form.addEventListener(
            'submit',
            function (event) {
                var query = normalize(input.value);

                if (!query) {
                    event.preventDefault();
                    input.focus();
                    return;
                }

                historySave(query);
                closeList();
            }
        );

        document.addEventListener(
            'pointerdown',
            function (event) {
                if (
                    event.target !== input
                    && !list.contains(event.target)
                ) {
                    closeList();
                }
            }
        );

        window.addEventListener(
            'resize',
            positionList
        );
        window.addEventListener(
            'scroll',
            positionList,
            true
        );
    }

    function rememberCurrentPageQuery() {
        try {
            var query = normalize(
                new URL(
                    window.location.href
                ).searchParams.get('search')
            );

            if (query) {
                historySave(query);
            }
        } catch (error) {
            /* URL API is available in supported browsers. */
        }
    }

    function bindAll() {
        rememberCurrentPageQuery();

        document.querySelectorAll(formSelector)
            .forEach(bindForm);
    }

    if (document.readyState === 'loading') {
        document.addEventListener(
            'DOMContentLoaded',
            bindAll,
            {once: true}
        );
    } else {
        bindAll();
    }
}());

/**
 * ForPrint homepage search/footer hard docking.
 *
 * The fixed homepage search strip sits at the viewport bottom until the
 * managed footer enters the viewport. Its bottom offset is then updated
 * synchronously to the exact visible footer intersection, with no delayed
 * transition or trailing animation.
 */
(function () {
    "use strict";

    var strip = document.querySelector(
        ".fp-search-strip--home"
    );
    var footer = document.querySelector(
        ".fp-site-footer"
    );

    if (!strip || !footer) {
        return;
    }

    var resizeObserver = null;

    function getVisibleFooterHeight() {
        var footerRect = footer.getBoundingClientRect();
        var visibleTop = Math.max(
            0,
            footerRect.top
        );
        var visibleBottom = Math.min(
            window.innerHeight,
            footerRect.bottom
        );

        return Math.max(
            0,
            visibleBottom - visibleTop
        );
    }

    function updateFooterOffset() {
        strip.style.setProperty(
            "--fp-search-strip-footer-offset",
            Math.round(
                getVisibleFooterHeight()
            ) + "px"
        );
    }

    window.addEventListener(
        "scroll",
        updateFooterOffset,
        { passive: true }
    );
    window.addEventListener(
        "resize",
        updateFooterOffset,
        { passive: true }
    );
    window.addEventListener(
        "orientationchange",
        updateFooterOffset,
        { passive: true }
    );

    if (typeof ResizeObserver === "function") {
        resizeObserver = new ResizeObserver(
            updateFooterOffset
        );
        resizeObserver.observe(footer);
    }

    updateFooterOffset();
}());
