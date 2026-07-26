(function () {
    "use strict";

    var THRESHOLD = 7;

    function ajaxJson(data) {
        if (typeof Ajax !== "function") {
            return Promise.reject(new Error("Admin Ajax helper unavailable"));
        }

        return Ajax({data: data}).then(function (body) {
            if (body && typeof body === "object") {
                return body;
            }

            return JSON.parse(String(body || "{}"));
        });
    }

    function setState(group, text, error) {
        group.dataset.saveState = text || "";
        group.classList.toggle("has-save-error", Boolean(error));
    }

    function initPositionControl(input) {
        if (input.dataset.fpPositionBound === "1" || !input.form) {
            return;
        }

        input.dataset.fpPositionBound = "1";

        var form = input.form;
        var parent = form.querySelector('select[name="parent_id"]');
        var defaultParent = parent ? String(parent.value) : "";
        var wrapper = document.createElement("div");
        var select = document.createElement("select");
        var originalParent = input.parentElement;

        wrapper.className = "fp-admin-position-composite";
        select.className = "fp-admin-position-composite__select vg-input";
        select.setAttribute("aria-label", "Швидкий вибір позиції");

        originalParent.insertBefore(wrapper, input);
        wrapper.appendChild(input);
        wrapper.appendChild(select);

        function fallbackLimit() {
            return Math.max(
                Number(input.max) || 0,
                Number(input.value) || 1,
                1
            );
        }

        function loadLimit() {
            if (!parent || typeof Ajax !== "function") {
                return Promise.resolve(fallbackLimit());
            }

            var tableField = form.querySelector('input[name="table"]');

            if (!tableField || !tableField.value) {
                return Promise.resolve(fallbackLimit());
            }

            var editing = Boolean(form.querySelector("#tableId"));
            var sameParent = String(parent.value) === defaultParent;

            return Ajax({
                data: {
                    ajax: "change_parent",
                    table: tableField.value,
                    parent_id: parent.value,
                    iteration: editing ? (sameParent ? 0 : 1) : 1
                }
            }).then(function (body) {
                return Number(body) || fallbackLimit();
            }).catch(fallbackLimit);
        }

        function renderOptions(maximum) {
            var current = Math.max(1, Number(input.value) || 1);
            maximum = Math.max(
                current,
                Math.min(999, Number(maximum) || 1)
            );

            select.replaceChildren();

            for (var i = 1; i <= maximum; i += 1) {
                var option = document.createElement("option");
                option.value = String(i);
                option.textContent = String(i);
                select.appendChild(option);
            }

            select.value = String(current);
            select.disabled = false;
        }

        function rebuild() {
            select.disabled = true;
            loadLimit().then(renderOptions);
        }

        select.addEventListener("change", function () {
            input.value = select.value;
            input.dispatchEvent(new Event("input", {bubbles: true}));
            input.dispatchEvent(new Event("change", {bubbles: true}));
        });

        input.addEventListener("input", function () {
            var value = String(Math.max(1, Number(input.value) || 1));

            if (!select.querySelector('option[value="' + value + '"]')) {
                var option = document.createElement("option");
                option.value = value;
                option.textContent = value;
                select.appendChild(option);
            }

            select.value = value;
        });

        if (parent) {
            parent.addEventListener("change", rebuild);
        }

        rebuild();
    }

    function initPositionControls() {
        document.querySelectorAll(
            '#main-form input[type="number"][name="menu_position"]'
        ).forEach(initPositionControl);
    }

    function directItems(group, selector) {
        return Array.prototype.filter.call(
            group.children,
            function (item) {
                return item.matches(selector);
            }
        );
    }

    function itemIds(group, selector, attribute) {
        return directItems(group, selector).map(function (item) {
            return String(item.getAttribute(attribute) || "");
        }).filter(Boolean);
    }

    function restore(group, order, selector, attribute) {
        var items = {};

        directItems(group, selector).forEach(function (item) {
            items[String(item.getAttribute(attribute) || "")] = item;
        });

        order.forEach(function (id) {
            if (items[id]) {
                group.appendChild(items[id]);
            }
        });
    }

    function targetAt(group, selector, x, y) {
        var element = document.elementFromPoint(x, y);
        var item = element ? element.closest(selector) : null;

        return item && item.parentElement === group ? item : null;
    }

    function insertAtPointer(group, dragged, target, x, y, vertical) {
        if (!target || target === dragged) {
            return false;
        }

        var rect = target.getBoundingClientRect();
        var before = vertical
            ? y < rect.top + rect.height / 2
            : (
                y >= rect.top && y <= rect.bottom
                    ? x < rect.left + rect.width / 2
                    : y < rect.top + rect.height / 2
            );
        var reference = before ? target : target.nextElementSibling;

        if (reference === dragged) {
            return false;
        }

        group.insertBefore(dragged, reference);
        return true;
    }

    function suppressClick(item) {
        item.addEventListener("click", function (event) {
            event.preventDefault();
            event.stopPropagation();
        }, {once: true, capture: true});
    }

    function bindSort(config) {
        var group = config.group;

        if (!group || group.dataset.fpPointerSortBound === "1") {
            return;
        }

        group.dataset.fpPointerSortBound = "1";
        group.setAttribute("data-fp-order-group", config.type);

        directItems(group, config.selector).forEach(function (item) {
            item.draggable = false;
            item.setAttribute("draggable", "false");
            item.setAttribute("data-fp-order-item", "");
        });

        var candidate = null;
        var dragged = null;
        var pointerId = null;
        var startX = 0;
        var startY = 0;
        var changed = false;
        var original = [];

        function clear() {
            if (dragged) {
                dragged.classList.remove("fp-admin-ordering--dragging");
            }

            candidate = null;
            dragged = null;
            pointerId = null;
            changed = false;
            original = [];
        }

        group.addEventListener("pointerdown", function (event) {
            if (
                event.button !== 0
                || event.target.closest("input, textarea, select, button")
            ) {
                return;
            }

            var item = event.target.closest(config.selector);

            if (!item || item.parentElement !== group) {
                return;
            }

            candidate = item;
            pointerId = event.pointerId;
            startX = event.clientX;
            startY = event.clientY;
            original = itemIds(
                group,
                config.selector,
                config.attribute
            );
        });

        group.addEventListener("pointermove", function (event) {
            if (!candidate || event.pointerId !== pointerId) {
                return;
            }

            if (!dragged) {
                var distance = Math.hypot(
                    event.clientX - startX,
                    event.clientY - startY
                );

                if (distance < THRESHOLD) {
                    return;
                }

                dragged = candidate;
                dragged.classList.add("fp-admin-ordering--dragging");

                try {
                    dragged.setPointerCapture(pointerId);
                } catch (error) {
                    // Optional enhancement.
                }
            }

            var target = targetAt(
                group,
                config.selector,
                event.clientX,
                event.clientY
            );

            if (insertAtPointer(
                group,
                dragged,
                target,
                event.clientX,
                event.clientY,
                config.vertical
            )) {
                changed = true;
            }

            event.preventDefault();
        });

        function finish(event) {
            if (!candidate || event.pointerId !== pointerId) {
                return;
            }

            var moved = dragged;
            var didChange = changed;
            var oldOrder = original.slice();

            if (moved) {
                suppressClick(moved);
            }

            clear();

            if (!didChange) {
                return;
            }

            var newOrder = itemIds(
                group,
                config.selector,
                config.attribute
            );

            group.classList.add("is-saving");
            setState(group, "Збереження порядку…", false);

            config.persist(newOrder).then(function (response) {
                if (!response || Number(response.success) !== 1) {
                    throw new Error(
                        response && response.message
                            ? response.message
                            : "Unknown response"
                    );
                }

                setState(group, "Порядок збережено.", false);
                window.setTimeout(function () {
                    if (!group.classList.contains("has-save-error")) {
                        group.dataset.saveState = "";
                    }
                }, 1300);
            }).catch(function (error) {
                console.error("ForPrint order save failed", error);
                restore(
                    group,
                    oldOrder,
                    config.selector,
                    config.attribute
                );
                setState(
                    group,
                    "Не вдалося зберегти. Порядок повернуто.",
                    true
                );
            }).finally(function () {
                group.classList.remove("is-saving");
            });
        }

        group.addEventListener("pointerup", finish);
        group.addEventListener("pointercancel", finish);
    }

    function initGoods() {
        document.querySelectorAll(
            "[data-fp-admin-sortable-entity-group]"
        ).forEach(function (group) {
            bindSort({
                group: group,
                type: "goods",
                vertical: false,
                selector: "[data-fp-admin-entity-id]",
                attribute: "data-fp-admin-entity-id",
                persist: function (order) {
                    return ajaxJson({
                        ajax: "sort_entity_positions",
                        table: group.dataset.table || "goods",
                        parent_id: group.dataset.parentId || "0",
                        ids: order.join(",")
                    });
                }
            });
        });
    }

    function initFilters() {
        document.querySelectorAll(
            "[data-fp-admin-sortable-filter-group]"
        ).forEach(function (group) {
            bindSort({
                group: group,
                type: "filters",
                vertical: false,
                selector: "[data-fp-admin-filter-id]",
                attribute: "data-fp-admin-filter-id",
                persist: function (order) {
                    return ajaxJson({
                        ajax: "sort_filter_positions",
                        parent_id: group.dataset.parentId || "0",
                        ids: order.join(",")
                    });
                }
            });
        });
    }

    function initMenu() {
        var group = document.querySelector(
            "[data-fp-admin-menu-sortable]"
        );

        if (!group) {
            return;
        }

        bindSort({
            group: group,
            type: "admin-menu",
            vertical: true,
            selector: "[data-fp-admin-menu-table]",
            attribute: "data-fp-admin-menu-table",
            persist: function (order) {
                return ajaxJson({
                    ajax: "sort_admin_menu",
                    tables: order.join(",")
                }).then(function (response) {
                    if (response && Number(response.success) === 1) {
                        try {
                            localStorage.setItem(
                                "fp-admin-menu-order",
                                JSON.stringify(order)
                            );
                        } catch (error) {
                            // Server remains source of truth.
                        }
                    }

                    return response;
                });
            }
        });
    }

    function init() {
        initPositionControls();
        initGoods();
        initFilters();
        initMenu();
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init, {once: true});
    } else {
        init();
    }
}());

/* FP_ADMIN_CARD_DRAG_INPUT_FIX_START */
/**
 * Remove native HTML5 drag only inside Goods/Filters cards.
 * The existing pointer sorter remains the ordering owner.
 */
(function () {
    "use strict";

    var collections = [
        {
            name: "goods",
            group: "[data-fp-admin-sortable-entity-group]",
            item: "[data-fp-admin-entity-id]"
        },
        {
            name: "filters",
            group: "[data-fp-admin-sortable-filter-group]",
            item: "[data-fp-admin-filter-id]"
        }
    ];

    function directItems(group, selector) {
        return Array.prototype.filter.call(
            group.children,
            function (item) {
                return item.matches(selector);
            }
        );
    }

    function disableNativeDrag(item) {
        item.draggable = false;
        item.setAttribute("draggable", "false");

        item.querySelectorAll(
            "a, img, [draggable]"
        ).forEach(function (node) {
            node.draggable = false;
            node.setAttribute("draggable", "false");
        });
    }

    function bind(config, group) {
        if (group.dataset.fpCardDragInputFix === "1") {
            return;
        }

        group.dataset.fpCardDragInputFix = "1";

        var items = directItems(group, config.item);
        items.forEach(disableNativeDrag);

        group.dataset.fpOrderDetectedCollection = config.name;
        group.dataset.fpOrderDetectedItems = String(items.length);

        group.addEventListener(
            "dragstart",
            function (event) {
                var item = event.target.closest(config.item);

                if (item && item.parentElement === group) {
                    event.preventDefault();
                    event.stopPropagation();

                    if (
                        typeof event.stopImmediatePropagation
                        === "function"
                    ) {
                        event.stopImmediatePropagation();
                    }
                }
            },
            {capture: true}
        );

        if (typeof MutationObserver === "function") {
            var observer = new MutationObserver(function () {
                var current = directItems(group, config.item);
                current.forEach(disableNativeDrag);
                group.dataset.fpOrderDetectedItems =
                    String(current.length);
            });

            observer.observe(group, {childList: true});
        }
    }

    function init() {
        collections.forEach(function (config) {
            document.querySelectorAll(config.group)
                .forEach(function (group) {
                    bind(config, group);
                });
        });

        window.ForPrintAdminOrderingDiagnostics =
            function () {
                return collections.map(function (config) {
                    var groups = Array.prototype.slice.call(
                        document.querySelectorAll(config.group)
                    );

                    return {
                        collection: config.name,
                        groups: groups.length,
                        items: groups.reduce(
                            function (total, group) {
                                return total + directItems(
                                    group,
                                    config.item
                                ).length;
                            },
                            0
                        ),
                        pointerSorterBound: groups.every(
                            function (group) {
                                return (
                                    group.dataset.fpPointerSortBound
                                    === "1"
                                );
                            }
                        ),
                        nativeDragNormalized: groups.every(
                            function (group) {
                                return (
                                    group.dataset.fpCardDragInputFix
                                    === "1"
                                );
                            }
                        )
                    };
                });
            };
    }

    if (document.readyState === "loading") {
        document.addEventListener(
            "DOMContentLoaded",
            init,
            {once: true}
        );
    } else {
        init();
    }
}());
/* FP_ADMIN_CARD_DRAG_INPUT_FIX_END */

/* FP_ADMIN_MANAGED_COLLECTION_ORDERING_START */
/**
 * Reusable ordering for current flat/hierarchical admin card collections.
 *
 * The server owns eligibility and scope. The DOM layer binds only when every
 * ID returned by the server is present in one coherent card container.
 */
(function () {
    "use strict";

    var threshold = 7;
    var clientAllowlist = [
        "catalog",
        "filters_categories",
        "sales",
        "news",
        "advantages",
        "information",
        "socials"
    ];

    var diagnostics = {
        table: "",
        manifestLoaded: false,
        eligible: false,
        boundScopes: [],
        skippedScopes: [],
        error: ""
    };

    function resolveAjaxHelper() {
        if (typeof Ajax === "function") {
            return Ajax;
        }

        if (typeof window.Ajax === "function") {
            return window.Ajax;
        }

        return null;
    }

    function parseResponse(value) {
        if (value && typeof value === "object") {
            return value;
        }

        try {
            return JSON.parse(String(value || "{}"));
        } catch (error) {
            return {};
        }
    }

    function ajaxJson(data) {
        var helper = resolveAjaxHelper();

        if (!helper) {
            return Promise.reject(
                new Error("Admin Ajax helper unavailable")
            );
        }

        return helper({data: data}).then(function (body) {
            var response = parseResponse(body);

            if (!response || Number(response.success) !== 1) {
                throw new Error(
                    response && response.message
                        ? String(response.message)
                        : "Server rejected the ordering request"
                );
            }

            return response;
        });
    }

    function currentTable() {
        var match = window.location.pathname.match(
            /\/admin\/show\/([A-Za-z0-9_]+)(?:\/|$)/
        );

        return match ? match[1] : "";
    }

    function escapeRegExp(value) {
        return value.replace(
            /[.*+?^${}()|[\]\\]/g,
            "\\$&"
        );
    }

    function editAnchorMap(table) {
        var pattern = new RegExp(
            "/admin/edit/"
            + escapeRegExp(table)
            + "/([0-9]+)(?:[/?#]|$)"
        );
        var result = {};

        document.querySelectorAll("a[href]").forEach(function (anchor) {
            var href = anchor.getAttribute("href") || "";
            var match = href.match(pattern);

            if (!match) {
                return;
            }

            var id = String(Number(match[1]));

            if (id !== "0" && !result[id]) {
                result[id] = anchor;
            }
        });

        return result;
    }

    function closestCard(anchor) {
        return (
            anchor.closest("[data-fp-admin-managed-sort-item]")
            || anchor.closest("[data-fp-admin-entity-id]")
            || anchor.closest(".fp-admin-entity-card")
            || anchor.closest(".show_element")
            || anchor
        );
    }

    function commonAncestor(elements) {
        if (!elements.length) {
            return null;
        }

        var ancestor = elements[0].parentElement;

        while (
            ancestor
            && !elements.every(function (element) {
                return ancestor.contains(element);
            })
        ) {
            ancestor = ancestor.parentElement;
        }

        if (
            ancestor === document.body
            || ancestor === document.documentElement
        ) {
            return null;
        }

        return ancestor;
    }

    function directChildUnder(container, element) {
        var current = element;

        while (
            current
            && current.parentElement !== container
        ) {
            current = current.parentElement;
        }

        return (
            current
            && current.parentElement === container
        )
            ? current
            : null;
    }

    function resolveScopeLayout(group, anchorMap) {
        var ids = Array.isArray(group.ids)
            ? group.ids.map(String)
            : [];
        var baseCards = [];

        for (var index = 0; index < ids.length; index += 1) {
            var anchor = anchorMap[ids[index]];

            if (!anchor) {
                return {
                    success: false,
                    reason: "not_all_ids_are_visible"
                };
            }

            baseCards.push(closestCard(anchor));
        }

        var container = commonAncestor(baseCards);

        if (!container) {
            return {
                success: false,
                reason: "common_card_container_not_found"
            };
        }

        var items = baseCards.map(function (card) {
            return directChildUnder(container, card);
        });

        if (items.some(function (item) {
            return !item;
        })) {
            return {
                success: false,
                reason: "cards_are_not_coherent_children"
            };
        }

        if (
            new Set(items).size !== ids.length
        ) {
            return {
                success: false,
                reason: "duplicate_card_wrapper"
            };
        }

        return {
            success: true,
            container: container,
            ids: ids,
            items: items
        };
    }

    function directManagedItems(container) {
        return Array.prototype.filter.call(
            container.children,
            function (item) {
                return item.hasAttribute(
                    "data-fp-admin-managed-sort-id"
                );
            }
        );
    }

    function scopeItems(container, scope) {
        return directManagedItems(container).filter(
            function (item) {
                return (
                    item.dataset.fpAdminManagedSortScope
                    === scope
                );
            }
        );
    }

    function scopeOrder(container, scope) {
        return scopeItems(container, scope).map(
            function (item) {
                return String(
                    item.dataset.fpAdminManagedSortId || ""
                );
            }
        ).filter(Boolean);
    }

    function setState(container, text, isError) {
        container.dataset.saveState = text || "";
        container.classList.toggle(
            "has-save-error",
            Boolean(isError)
        );
    }

    function clearStateLater(container) {
        window.setTimeout(function () {
            if (!container.classList.contains("has-save-error")) {
                container.dataset.saveState = "";
            }
        }, 1400);
    }

    function restoreChildren(container, children) {
        children.forEach(function (child) {
            container.appendChild(child);
        });
    }

    function targetAtPoint(
        container,
        scope,
        x,
        y
    ) {
        var element = document.elementFromPoint(x, y);
        var target = element
            ? element.closest(
                "[data-fp-admin-managed-sort-id]"
            )
            : null;

        return (
            target
            && target.parentElement === container
            && target.dataset.fpAdminManagedSortScope
                === scope
        )
            ? target
            : null;
    }

    function placeBeforePointer(
        container,
        dragged,
        target,
        x,
        y
    ) {
        if (!target || target === dragged) {
            return false;
        }

        var rect = target.getBoundingClientRect();
        var sameRow = (
            y >= rect.top
            && y <= rect.bottom
        );
        var before = sameRow
            ? x < rect.left + rect.width / 2
            : y < rect.top + rect.height / 2;
        var reference = before
            ? target
            : target.nextElementSibling;

        if (reference === dragged) {
            return false;
        }

        container.insertBefore(dragged, reference);
        return true;
    }

    function autoScroll(y) {
        var edge = 48;

        if (y < edge) {
            window.scrollBy(0, -18);
        } else if (y > window.innerHeight - edge) {
            window.scrollBy(0, 18);
        }
    }

    function suppressNextClick(item) {
        item.addEventListener(
            "click",
            function (event) {
                event.preventDefault();
                event.stopPropagation();
            },
            {
                capture: true,
                once: true
            }
        );
    }

    function persistScope(
        table,
        scope,
        container,
        previousChildren,
        previousOrder
    ) {
        var currentOrder = scopeOrder(
            container,
            scope
        );

        if (
            currentOrder.length === previousOrder.length
            && currentOrder.every(
                function (id, index) {
                    return id === previousOrder[index];
                }
            )
        ) {
            return;
        }

        container.classList.add("is-saving");
        setState(
            container,
            "Збереження порядку…",
            false
        );

        ajaxJson({
            ajax: "sort_managed_collection_positions",
            table: table,
            scope: scope,
            ids: currentOrder.join(",")
        }).then(function () {
            setState(
                container,
                "Порядок збережено.",
                false
            );
            clearStateLater(container);
        }).catch(function (error) {
            restoreChildren(
                container,
                previousChildren
            );
            setState(
                container,
                error && error.message
                    ? error.message
                    : "Не вдалося зберегти. "
                        + "Попередній порядок відновлено.",
                true
            );
        }).finally(function () {
            container.classList.remove("is-saving");
        });
    }

    function bindContainer(table, container) {
        if (
            container.dataset.fpManagedSortBound
            === "1"
        ) {
            return;
        }

        container.dataset.fpManagedSortBound = "1";
        container.setAttribute(
            "data-fp-admin-managed-sort-group",
            table
        );
        container.setAttribute(
            "data-fp-order-group",
            "managed-collection"
        );

        directManagedItems(container).forEach(
            function (item) {
                item.draggable = false;
                item.setAttribute("draggable", "false");
                item.setAttribute("data-fp-order-item", "");

                item.querySelectorAll(
                    "a, img, [draggable]"
                ).forEach(function (node) {
                    node.draggable = false;
                    node.setAttribute("draggable", "false");
                });
            }
        );

        container.addEventListener(
            "dragstart",
            function (event) {
                var item = event.target.closest(
                    "[data-fp-admin-managed-sort-id]"
                );

                if (
                    item
                    && item.parentElement === container
                ) {
                    event.preventDefault();
                    event.stopPropagation();
                }
            },
            true
        );

        var pointerId = null;
        var candidate = null;
        var dragged = null;
        var scope = "";
        var startX = 0;
        var startY = 0;
        var previousChildren = [];
        var previousOrder = [];
        var changed = false;

        container.addEventListener(
            "pointerdown",
            function (event) {
                if (
                    event.button !== 0
                    || container.classList.contains("is-saving")
                    || event.target.closest(
                        "input, textarea, select, button"
                    )
                ) {
                    return;
                }

                var item = event.target.closest(
                    "[data-fp-admin-managed-sort-id]"
                );

                if (
                    !item
                    || item.parentElement !== container
                ) {
                    return;
                }

                pointerId = event.pointerId;
                candidate = item;
                dragged = null;
                scope = (
                    item.dataset.fpAdminManagedSortScope
                    || ""
                );
                startX = event.clientX;
                startY = event.clientY;
                previousChildren = Array.prototype.slice.call(
                    container.children
                );
                previousOrder = scopeOrder(
                    container,
                    scope
                );
                changed = false;
            }
        );

        container.addEventListener(
            "pointermove",
            function (event) {
                if (
                    !candidate
                    || event.pointerId !== pointerId
                ) {
                    return;
                }

                if (!dragged) {
                    var distance = Math.hypot(
                        event.clientX - startX,
                        event.clientY - startY
                    );

                    if (distance < threshold) {
                        return;
                    }

                    dragged = candidate;
                    dragged.classList.add(
                        "fp-admin-ordering--dragging"
                    );
                    container.classList.add(
                        "fp-admin-ordering--active"
                    );

                    try {
                        dragged.setPointerCapture(
                            pointerId
                        );
                    } catch (error) {
                        /* Pointer capture is an enhancement. */
                    }
                }

                autoScroll(event.clientY);

                var target = targetAtPoint(
                    container,
                    scope,
                    event.clientX,
                    event.clientY
                );

                if (
                    placeBeforePointer(
                        container,
                        dragged,
                        target,
                        event.clientX,
                        event.clientY
                    )
                ) {
                    changed = true;
                }

                event.preventDefault();
            }
        );

        function finish(event, cancelled) {
            if (
                !candidate
                || event.pointerId !== pointerId
            ) {
                return;
            }

            var movedItem = dragged;
            var didChange = changed;
            var savedScope = scope;
            var oldChildren = previousChildren.slice();
            var oldOrder = previousOrder.slice();

            if (dragged) {
                dragged.classList.remove(
                    "fp-admin-ordering--dragging"
                );
            }
            container.classList.remove(
                "fp-admin-ordering--active"
            );

            pointerId = null;
            candidate = null;
            dragged = null;
            scope = "";
            previousChildren = [];
            previousOrder = [];
            changed = false;

            if (!movedItem) {
                return;
            }

            suppressNextClick(movedItem);

            if (cancelled) {
                restoreChildren(
                    container,
                    oldChildren
                );
                return;
            }

            if (didChange) {
                persistScope(
                    table,
                    savedScope,
                    container,
                    oldChildren,
                    oldOrder
                );
            }
        }

        container.addEventListener(
            "pointerup",
            function (event) {
                finish(event, false);
            }
        );
        container.addEventListener(
            "pointercancel",
            function (event) {
                finish(event, true);
            }
        );
    }

    function markScope(
        table,
        group,
        layout
    ) {
        var scope = String(group.scope || "__flat__");

        layout.items.forEach(function (item, index) {
            item.setAttribute(
                "data-fp-admin-managed-sort-item",
                ""
            );
            item.dataset.fpAdminManagedSortId =
                layout.ids[index];
            item.dataset.fpAdminManagedSortScope =
                scope;
            item.dataset.fpAdminManagedSortTable =
                table;
        });

        bindContainer(
            table,
            layout.container
        );

        diagnostics.boundScopes.push({
            scope: scope,
            ids: layout.ids.slice(),
            itemCount: layout.items.length
        });
    }

    function initManagedCollections() {
        var table = currentTable();
        diagnostics.table = table;

        if (
            !table
            || clientAllowlist.indexOf(table) < 0
        ) {
            return;
        }

        ajaxJson({
            ajax: "managed_sortable_manifest",
            table: table
        }).then(function (manifest) {
            diagnostics.manifestLoaded = true;
            diagnostics.eligible = true;

            var anchorMap = editAnchorMap(table);
            var groups = Array.isArray(manifest.groups)
                ? manifest.groups
                : [];

            groups.forEach(function (group) {
                if (
                    !Array.isArray(group.ids)
                    || group.ids.length < 2
                ) {
                    diagnostics.skippedScopes.push({
                        scope: String(
                            group.scope || "__flat__"
                        ),
                        reason: "fewer_than_two_records"
                    });
                    return;
                }

                var layout = resolveScopeLayout(
                    group,
                    anchorMap
                );

                if (!layout.success) {
                    diagnostics.skippedScopes.push({
                        scope: String(
                            group.scope || "__flat__"
                        ),
                        reason: layout.reason
                    });
                    return;
                }

                markScope(
                    table,
                    group,
                    layout
                );
            });
        }).catch(function (error) {
            diagnostics.error = (
                error && error.message
                    ? error.message
                    : String(error)
            );
        });
    }

    window.ForPrintAdminManagedOrderingDiagnostics =
        function () {
            return JSON.parse(
                JSON.stringify(diagnostics)
            );
        };

    if (document.readyState === "loading") {
        document.addEventListener(
            "DOMContentLoaded",
            initManagedCollections,
            {once: true}
        );
    } else {
        initManagedCollections();
    }
}());
/* FP_ADMIN_MANAGED_COLLECTION_ORDERING_END */
