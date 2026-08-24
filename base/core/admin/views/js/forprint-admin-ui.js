/**
 * ForPrint admin shared UI behavior.
 *
 * This checkpoint owns only sidebar wheel routing. Ordering behavior remains
 * audit-only until the current server endpoints are identified.
 */
(function () {
    "use strict";

    function findSidebar() {
        return document.querySelector(
            ".fp-admin-sidebar, "
            + "[data-fp-admin-sidebar], "
            + ".vg-menu"
        );
    }

    function canScroll(sidebar, deltaY) {
        if (deltaY < 0) {
            return sidebar.scrollTop > 0;
        }

        return (
            sidebar.scrollTop + sidebar.clientHeight
            < sidebar.scrollHeight - 1
        );
    }

    function bindSidebarWheel() {
        var sidebar = findSidebar();

        if (!sidebar) {
            return;
        }

        sidebar.addEventListener(
            "wheel",
            function (event) {
                if (!event.deltaY || !canScroll(sidebar, event.deltaY)) {
                    return;
                }

                sidebar.scrollTop += event.deltaY;
                event.preventDefault();
                event.stopPropagation();
            },
            { passive: false }
        );
    }

    if (document.readyState === "loading") {
        document.addEventListener(
            "DOMContentLoaded",
            bindSidebarWheel,
            { once: true }
        );
    } else {
        bindSidebarWheel();
    }
}());

/**
 * Shared admin flash lifecycle.
 *
 * Persistent errors remain visible. Other flash messages auto-hide after the
 * accepted short delay and may also be dismissed by clicking the message.
 */
(function () {
    "use strict";

    function hideFlashMessage(message) {
        if (
            !message
            || message.dataset.forprintHidden === "1"
        ) {
            return;
        }

        message.dataset.forprintHidden = "1";
        message.classList.add("forprint-admin-flash_hide");

        window.setTimeout(function () {
            if (message.parentNode) {
                message.parentNode.removeChild(message);
            }
        }, 350);
    }

    function bindFlashMessages() {
        document
            .querySelectorAll(".success, .error")
            .forEach(function (message) {
                if (
                    message.dataset.forprintFlashBound === "1"
                ) {
                    return;
                }

                message.dataset.forprintFlashBound = "1";

                message.addEventListener(
                    "click",
                    function () {
                        hideFlashMessage(message);
                    }
                );

                if (
                    message.classList.contains(
                        "forprint-admin-persistent-error"
                    )
                ) {
                    return;
                }

                window.setTimeout(
                    function () {
                        hideFlashMessage(message);
                    },
                    1600
                );
            });
    }

    function initFlashMessages() {
        bindFlashMessages();
        window.setTimeout(bindFlashMessages, 100);
    }

    if (document.readyState === "loading") {
        document.addEventListener(
            "DOMContentLoaded",
            initFlashMessages,
            { once: true }
        );
    } else {
        initFlashMessages();
    }
}());

/**
 * Canonical shared checkboxlist runtime.
 *
 * Modern data hooks own behavior. Historical select_wrap / option_wrap /
 * select_all classes remain markup/CSS compatibility hooks only.
 */
(function () {
    "use strict";

    var ROOT_SELECTOR = "[data-fp-admin-checkboxlist]";

    function checkboxes(options) {
        return Array.prototype.slice.call(
            options.querySelectorAll('input[type="checkbox"]')
        );
    }

    function syncSelectAllState(control, options) {
        var inputs = checkboxes(options);
        var checked = inputs.filter(function (input) {
            return input.checked;
        }).length;

        control.setAttribute(
            "aria-pressed",
            inputs.length > 0 && checked === inputs.length
                ? "true"
                : "false"
        );
    }

    function setExpanded(header, options, expanded) {
        header.setAttribute(
            "aria-expanded",
            expanded ? "true" : "false"
        );
        options.hidden = !expanded;
    }

    function bindCheckboxlist(root) {
        if (
            !root
            || root.dataset.fpAdminCheckboxlistBound === "1"
        ) {
            return;
        }

        var headers = Array.prototype.slice.call(
            root.querySelectorAll(
                "[data-fp-admin-checkboxlist-header]"
            )
        );

        if (!headers.length) {
            return;
        }

        root.dataset.fpAdminCheckboxlistBound = "1";

        headers.forEach(function (header) {
            var options = header.nextElementSibling;

            if (
                !options
                || !options.matches(
                    "[data-fp-admin-checkboxlist-options]"
                )
            ) {
                return;
            }

            var selectAll = header.querySelector(
                "[data-fp-admin-checkboxlist-select-all]"
            );

            setExpanded(header, options, false);

            if (selectAll) {
                selectAll.setAttribute("role", "button");
                selectAll.setAttribute("tabindex", "0");
                syncSelectAllState(selectAll, options);
            }

            header.addEventListener(
                "click",
                function (event) {
                    if (
                        event.target.closest(
                            "[data-fp-admin-checkboxlist-select-all]"
                        )
                    ) {
                        return;
                    }

                    setExpanded(
                        header,
                        options,
                        options.hidden
                    );
                }
            );

            if (selectAll) {
                function toggleAll(event) {
                    event.preventDefault();
                    event.stopPropagation();

                    var inputs = checkboxes(options);
                    var shouldCheck = !(
                        inputs.length > 0
                        && inputs.every(function (input) {
                            return input.checked;
                        })
                    );

                    inputs.forEach(function (input) {
                        if (input.checked === shouldCheck) {
                            return;
                        }

                        input.checked = shouldCheck;
                        input.dispatchEvent(
                            new Event(
                                "change",
                                { bubbles: true }
                            )
                        );
                    });

                    syncSelectAllState(
                        selectAll,
                        options
                    );
                }

                selectAll.addEventListener(
                    "click",
                    toggleAll
                );

                selectAll.addEventListener(
                    "keydown",
                    function (event) {
                        if (
                            event.key === "Enter"
                            || event.key === " "
                        ) {
                            toggleAll(event);
                        }
                    }
                );
            }

            options.addEventListener(
                "change",
                function (event) {
                    if (
                        selectAll
                        && event.target.matches(
                            'input[type="checkbox"]'
                        )
                    ) {
                        syncSelectAllState(
                            selectAll,
                            options
                        );
                    }
                }
            );
        });
    }

    function initCheckboxlists() {
        document
            .querySelectorAll(ROOT_SELECTOR)
            .forEach(bindCheckboxlist);
    }

    if (document.readyState === "loading") {
        document.addEventListener(
            "DOMContentLoaded",
            initCheckboxlists,
            { once: true }
        );
    } else {
        initCheckboxlists();
    }
}());

/* FP_PHASE7_PRICE_MODE_RUNTIME_START */
(function () {
    "use strict";

    var panel = document.querySelector("[data-price-mode-panel]");

    if (!panel || panel.dataset.priceModeReady === "1") {
        return;
    }

    panel.dataset.priceModeReady = "1";

    var form = panel.closest("form");
    var modeInputs = panel.querySelectorAll("[data-price-mode]");
    var groups = panel.querySelectorAll("[data-price-group]");
    var rangeOnly = panel.querySelectorAll("[data-price-range-only]");
    var exactPrice = panel.querySelector('[name="price"]');
    var priceFrom = panel.querySelector('[name="price_from"]');
    var priceTo = panel.querySelector('[name="price_to"]');

    function selectedMode() {
        var checked = panel.querySelector(
            "[data-price-mode]:checked"
        );

        return checked ? checked.value : "request";
    }

    function updatePriceMode() {
        var mode = selectedMode();

        groups.forEach(function (group) {
            var active = (
                group.dataset.priceGroup
                    .split(/\s+/)
                    .indexOf(mode) !== -1
            );

            group.hidden = !active;

            group.querySelectorAll("input").forEach(function (input) {
                input.disabled = !active;
            });
        });

        rangeOnly.forEach(function (element) {
            var active = mode === "range";

            element.hidden = !active;

            element.querySelectorAll("input").forEach(function (input) {
                input.disabled = !active;
            });
        });

        if (exactPrice) {
            exactPrice.required = mode === "exact";
            exactPrice.setCustomValidity("");
        }

        if (priceFrom) {
            priceFrom.required = (
                mode === "starting"
                || mode === "range"
            );
            priceFrom.setCustomValidity("");
        }

        if (priceTo) {
            priceTo.required = mode === "range";
            priceTo.setCustomValidity("");
        }
    }

    modeInputs.forEach(function (input) {
        input.addEventListener(
            "change",
            updatePriceMode
        );
    });

    if (form) {
        form.addEventListener("submit", function (event) {
            var mode = selectedMode();

            if (mode === "starting") {
                var startingValue = priceFrom
                    ? Number(priceFrom.value || 0)
                    : 0;

                if (startingValue <= 0) {
                    event.preventDefault();

                    if (priceFrom) {
                        priceFrom.setCustomValidity(
                            "Вкажи реальну мінімальну ціну більше нуля."
                        );
                        priceFrom.reportValidity();
                    }
                }

                return;
            }

            if (mode !== "range") {
                return;
            }

            var fromValue = priceFrom
                ? Number(priceFrom.value || 0)
                : 0;
            var toValue = priceTo
                ? Number(priceTo.value || 0)
                : 0;

            if (fromValue <= 0 || toValue <= 0) {
                event.preventDefault();

                if (priceFrom) {
                    priceFrom.setCustomValidity(
                        "Вкажи обидві межі діапазону ціни більше нуля."
                    );
                    priceFrom.reportValidity();
                }
            }
        });
    }

    updatePriceMode();
}());
/* FP_PHASE7_PRICE_MODE_RUNTIME_END */

/* FP_PHASE5_CREATE_SITEMAP_RUNTIME_START */
(() => {
    "use strict";

    document.querySelector("[data-fp-admin-create-sitemap]").onclick = (e) => {

        e.preventDefault();

            createSitemap();
    };

    let links_counter = 0;

    function createSitemap(){

        links_counter++;

        Ajax({data:{ajax:'sitemap', links_counter: links_counter}})
            .then ((res) => {
                console.log('success - ' + res);
                // console.log(res)
            })
            .catch((res) => {
                console.log('error - ' + res);
                createSitemap();
            });
    }
})();
/* FP_PHASE5_CREATE_SITEMAP_RUNTIME_END */
