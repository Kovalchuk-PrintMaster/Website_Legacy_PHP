/**
 * ForPrint catalog progressive navigation.
 *
 * The server remains the source of truth. JavaScript progressively enhances
 * same-origin catalog links and GET filters by fetching the complete HTML,
 * parsing the canonical catalog surface and exchanging only the required
 * catalog fragment. Ordinary navigation remains the fallback.
 */
(function () {
    "use strict";

    var SURFACE_SELECTOR =
        '.fp-catalog-page[data-fp-surface="catalog"]';
    var DESKTOP_QUERY = "(min-width: 62.01em)";
    var FLOATING_TOP = 12;
    var FLOATING_BOTTOM_GUARD = 18;
    var PANEL_DURATION = 420;

    var surfaceCleanup = function () {};
    var navigationController = null;
    var navigationSequence = 0;

    function afterLayout(callback) {
        window.requestAnimationFrame(function () {
            window.requestAnimationFrame(callback);
        });
    }

    function prefersReducedMotion() {
        return (
            typeof window.matchMedia === "function"
            && window.matchMedia(
                "(prefers-reduced-motion: reduce)"
            ).matches
        );
    }

    function getSurface() {
        return document.querySelector(SURFACE_SELECTOR);
    }

    function getFilterBoxes(scope) {
        return Array.prototype.slice.call(
            scope.querySelectorAll(
                'input[type="checkbox"][name="filters[]"]'
            )
        );
    }

    function synchroniseSelectAll(control, boxes) {
        var checkedCount = boxes.filter(function (box) {
            return box.checked;
        }).length;

        control.checked = (
            boxes.length > 0
            && checkedCount === boxes.length
        );
        control.indeterminate = (
            checkedCount > 0
            && checkedCount < boxes.length
        );
    }

    function bindFilterScope(scope) {
        var control = scope.querySelector(
            "[data-fp-filter-select-all]"
        );

        if (!control) {
            return;
        }

        var boxes = getFilterBoxes(scope);

        control.addEventListener("change", function () {
            boxes.forEach(function (box) {
                box.checked = control.checked;
            });

            control.indeterminate = false;
        });

        boxes.forEach(function (box) {
            box.addEventListener("change", function () {
                synchroniseSelectAll(control, boxes);
            });
        });

        synchroniseSelectAll(control, boxes);
    }

    function getPrimarySubmit(surface) {
        return surface.querySelector(
            ".fp-catalog-filter__submit--top"
        );
    }

    function getUsableViewportTop(surface) {
        var safeTop = 16;
        var submit = getPrimarySubmit(surface);

        if (!submit) {
            return safeTop;
        }

        var submitRect = submit.getBoundingClientRect();

        if (
            submitRect.bottom > 0
            && submitRect.top < window.innerHeight
        ) {
            safeTop = Math.max(
                safeTop,
                Math.ceil(submitRect.bottom + 14)
            );
        }

        return safeTop;
    }

    function ensureExpandedCategoryVisible(
        details,
        forceAlignment
    ) {
        if (!details || !details.open) {
            return;
        }

        var surface = details.closest(SURFACE_SELECTOR);
        var summary = details.querySelector(
            ":scope > .fp-catalog-category-node__summary"
        );
        var panel = details.querySelector(
            ":scope > .fp-catalog-category-filter-panel"
        );

        if (!surface || !summary || !panel) {
            return;
        }

        var safeTop = getUsableViewportTop(surface);
        var viewportBottom = window.innerHeight - 18;
        var summaryRect = summary.getBoundingClientRect();
        var panelRect = panel.getBoundingClientRect();
        var needsAlignment = (
            forceAlignment
            || summaryRect.top < safeTop
            || panelRect.bottom > viewportBottom
        );

        if (!needsAlignment) {
            return;
        }

        window.scrollTo({
            top: Math.max(
                0,
                Math.round(
                    window.scrollY
                    + summaryRect.top
                    - safeTop
                )
            ),
            behavior: prefersReducedMotion()
                ? "auto"
                : "smooth"
        });
    }

    function bindCategoryViewportFocus(
        surface,
        cleanupCallbacks
    ) {
        surface
            .querySelectorAll(".fp-catalog-category-node")
            .forEach(function (details) {
                var onToggle = function () {
                    if (!details.open) {
                        return;
                    }

                    afterLayout(function () {
                        ensureExpandedCategoryVisible(
                            details,
                            true
                        );
                    });
                };

                details.addEventListener("toggle", onToggle);
                cleanupCallbacks.push(function () {
                    details.removeEventListener(
                        "toggle",
                        onToggle
                    );
                });
            });

        var activeOpenCategory = surface.querySelector(
            ".fp-catalog-category-item.is-active "
            + ".fp-catalog-category-node[open]"
        );

        if (activeOpenCategory) {
            afterLayout(function () {
                ensureExpandedCategoryVisible(
                    activeOpenCategory,
                    false
                );
            });
        }
    }

    function bindFloatingSubmit(
        surface,
        cleanupCallbacks
    ) {
        var submit = getPrimarySubmit(surface);
        var form = submit ? submit.closest("form") : null;

        if (!submit || !form) {
            return;
        }

        var placeholder = document.createElement("div");
        var mediaQuery = window.matchMedia(DESKTOP_QUERY);
        var frameRequested = false;
        var resizeObserver = null;

        placeholder.className =
            "fp-catalog-filter__submit-placeholder";
        placeholder.setAttribute("aria-hidden", "true");
        submit.parentNode.insertBefore(placeholder, submit);

        function clearFloatingState() {
            submit.classList.remove("is-floating");
            placeholder.classList.remove("is-active");
            submit.style.removeProperty(
                "--fp-catalog-floating-top"
            );
            submit.style.removeProperty(
                "--fp-catalog-floating-left"
            );
            submit.style.removeProperty(
                "--fp-catalog-floating-width"
            );
            placeholder.style.removeProperty("height");
            placeholder.style.removeProperty(
                "margin-bottom"
            );
        }

        function updateFloatingState() {
            frameRequested = false;

            if (!mediaQuery.matches) {
                clearFloatingState();
                return;
            }

            var anchorRect =
                placeholder.classList.contains("is-active")
                    ? placeholder.getBoundingClientRect()
                    : submit.getBoundingClientRect();
            var surfaceRect = surface.getBoundingClientRect();
            var submitHeight = submit.offsetHeight;
            var shouldFloat = (
                anchorRect.top < FLOATING_TOP
                && surfaceRect.bottom
                    > FLOATING_TOP
                    + submitHeight
                    + FLOATING_BOTTOM_GUARD
            );

            if (!shouldFloat) {
                clearFloatingState();
                return;
            }

            var computed = window.getComputedStyle(submit);

            placeholder.style.height = submitHeight + "px";
            placeholder.style.marginBottom =
                computed.marginBottom;
            placeholder.classList.add("is-active");

            var placeholderRect =
                placeholder.getBoundingClientRect();

            submit.style.setProperty(
                "--fp-catalog-floating-top",
                FLOATING_TOP + "px"
            );
            submit.style.setProperty(
                "--fp-catalog-floating-left",
                Math.round(placeholderRect.left) + "px"
            );
            submit.style.setProperty(
                "--fp-catalog-floating-width",
                Math.round(placeholderRect.width) + "px"
            );
            submit.classList.add("is-floating");
        }

        function requestFloatingUpdate() {
            if (frameRequested) {
                return;
            }

            frameRequested = true;
            window.requestAnimationFrame(
                updateFloatingState
            );
        }

        window.addEventListener(
            "scroll",
            requestFloatingUpdate,
            { passive: true }
        );
        window.addEventListener(
            "resize",
            requestFloatingUpdate,
            { passive: true }
        );

        if (typeof mediaQuery.addEventListener === "function") {
            mediaQuery.addEventListener(
                "change",
                requestFloatingUpdate
            );
        } else if (
            typeof mediaQuery.addListener === "function"
        ) {
            mediaQuery.addListener(
                requestFloatingUpdate
            );
        }

        if (typeof ResizeObserver === "function") {
            resizeObserver = new ResizeObserver(
                requestFloatingUpdate
            );
            resizeObserver.observe(surface);
            resizeObserver.observe(form);
        }

        cleanupCallbacks.push(function () {
            window.removeEventListener(
                "scroll",
                requestFloatingUpdate
            );
            window.removeEventListener(
                "resize",
                requestFloatingUpdate
            );

            if (
                typeof mediaQuery.removeEventListener
                === "function"
            ) {
                mediaQuery.removeEventListener(
                    "change",
                    requestFloatingUpdate
                );
            } else if (
                typeof mediaQuery.removeListener
                === "function"
            ) {
                mediaQuery.removeListener(
                    requestFloatingUpdate
                );
            }

            if (resizeObserver) {
                resizeObserver.disconnect();
            }

            clearFloatingState();
            placeholder.remove();
        });

        afterLayout(requestFloatingUpdate);
    }

    function initialiseSurface(surface) {
        surfaceCleanup();

        var cleanupCallbacks = [];

        surface
            .querySelectorAll("[data-fp-filter-scope]")
            .forEach(bindFilterScope);

        bindFloatingSubmit(surface, cleanupCallbacks);
        bindCategoryViewportFocus(
            surface,
            cleanupCallbacks
        );

        surfaceCleanup = function () {
            cleanupCallbacks.forEach(function (cleanup) {
                cleanup();
            });
            cleanupCallbacks = [];
        };
    }

    function beginLoading(surface) {
        surface.classList.add("is-loading");
        surface.setAttribute("aria-busy", "true");
    }

    function finishLoading(surface) {
        surface.classList.remove("is-loading");
        surface.removeAttribute("aria-busy");
    }

    function buildFormUrl(form) {
        var url = new URL(form.action, window.location.href);
        var query = new URLSearchParams(
            new FormData(form)
        ).toString();

        url.search = query;
        return url;
    }

    function isPlainLeftClick(event) {
        return (
            event.button === 0
            && !event.metaKey
            && !event.ctrlKey
            && !event.shiftKey
            && !event.altKey
        );
    }

    function getNavigationMode(link) {
        if (
            link.closest(".fp-catalog-category-list")
        ) {
            return "category";
        }

        if (
            link.closest(".catalog-section-top")
            || link.closest(".catalog-section-pagination")
            || link.closest(".qtyItems")
        ) {
            return "listing";
        }

        return "";
    }

    function copySurfaceState(
        currentSurface,
        incomingSurface
    ) {
        [
            "data-fp-catalog-url",
            "data-fp-catalog-alias"
        ].forEach(function (attribute) {
            if (incomingSurface.hasAttribute(attribute)) {
                currentSurface.setAttribute(
                    attribute,
                    incomingSurface.getAttribute(attribute)
                );
            } else {
                currentSurface.removeAttribute(attribute);
            }
        });
    }

    function markEntering(element) {
        element.classList.add("fp-catalog-swap-enter");
        window.requestAnimationFrame(function () {
            window.setTimeout(function () {
                element.classList.remove(
                    "fp-catalog-swap-enter"
                );
            }, 320);
        });
    }

    function prepareActivePanel(incomingSurface) {
        var panel = incomingSurface.querySelector(
            ".fp-catalog-category-item.is-active "
            + ".fp-catalog-category-filter-panel"
        );

        if (!panel || prefersReducedMotion()) {
            return panel;
        }

        panel.classList.add("is-fp-revealing");
        panel.style.height = "0px";
        panel.style.opacity = "0";
        return panel;
    }

    function revealActivePanel(panel, surface) {
        if (!panel) {
            return;
        }

        var details = panel.closest(
            ".fp-catalog-category-node"
        );

        if (prefersReducedMotion()) {
            ensureExpandedCategoryVisible(
                details,
                true
            );
            return;
        }

        var targetHeight = panel.scrollHeight;

        panel.style.transition =
            "height "
            + PANEL_DURATION
            + "ms cubic-bezier(.2,.75,.25,1), "
            + "opacity 220ms ease";
        panel.style.height = targetHeight + "px";
        panel.style.opacity = "1";

        window.setTimeout(function () {
            panel.style.removeProperty("height");
            panel.style.removeProperty("opacity");
            panel.style.removeProperty("transition");
            panel.classList.remove("is-fp-revealing");
        }, PANEL_DURATION + 40);

        afterLayout(function () {
            ensureExpandedCategoryVisible(
                details,
                true
            );
        });
    }

    function swapListing(
        currentSurface,
        incomingSurface
    ) {
        var currentSection = currentSurface.querySelector(
            ".catalog-section"
        );
        var incomingSection = incomingSurface.querySelector(
            ".catalog-section"
        );

        if (!currentSection || !incomingSection) {
            return false;
        }

        markEntering(incomingSection);
        currentSection.replaceWith(incomingSection);
        copySurfaceState(currentSurface, incomingSurface);
        return true;
    }

    function swapCatalogWrap(
        currentSurface,
        incomingSurface,
        revealPanel
    ) {
        var currentBreadcrumbs =
            currentSurface.querySelector(
                ".fp-catalog-page__breadcrumbs"
            );
        var incomingBreadcrumbs =
            incomingSurface.querySelector(
                ".fp-catalog-page__breadcrumbs"
            );
        var currentWrap = currentSurface.querySelector(
            ".catalog-internal-wrap"
        );
        var incomingWrap = incomingSurface.querySelector(
            ".catalog-internal-wrap"
        );

        if (!currentWrap || !incomingWrap) {
            return false;
        }

        var panel = revealPanel
            ? prepareActivePanel(incomingSurface)
            : null;

        surfaceCleanup();

        if (currentBreadcrumbs && incomingBreadcrumbs) {
            currentBreadcrumbs.replaceWith(
                incomingBreadcrumbs
            );
        }

        markEntering(incomingWrap);
        currentWrap.replaceWith(incomingWrap);
        copySurfaceState(currentSurface, incomingSurface);
        initialiseSurface(currentSurface);

        if (panel) {
            afterLayout(function () {
                revealActivePanel(
                    currentSurface.querySelector(
                        ".fp-catalog-category-item.is-active "
                        + ".fp-catalog-category-filter-panel"
                    ),
                    currentSurface
                );
            });
        }

        return true;
    }

    async function navigateCatalog(
        requestedUrl,
        mode,
        options
    ) {
        var settings = options || {};
        var currentSurface = getSurface();

        if (!currentSurface) {
            window.location.assign(
                requestedUrl.toString()
            );
            return;
        }

        if (navigationController) {
            navigationController.abort();
        }

        navigationController = new AbortController();
        navigationSequence += 1;

        var sequence = navigationSequence;
        beginLoading(currentSurface);

        try {
            var response = await fetch(
                requestedUrl.toString(),
                {
                    method: "GET",
                    credentials: "same-origin",
                    signal: navigationController.signal,
                    headers: {
                        "Accept": "text/html",
                        "X-Requested-With": "XMLHttpRequest"
                    }
                }
            );

            if (!response.ok) {
                throw new Error(
                    "Catalog request failed: "
                    + response.status
                );
            }

            var html = await response.text();

            if (sequence !== navigationSequence) {
                return;
            }

            var parsedDocument = new DOMParser()
                .parseFromString(html, "text/html");
            var incomingSurface =
                parsedDocument.querySelector(
                    SURFACE_SELECTOR
                );

            if (!incomingSurface) {
                throw new Error(
                    "Catalog surface is missing in response"
                );
            }

            var swapped = false;

            if (mode === "listing") {
                swapped = swapListing(
                    currentSurface,
                    incomingSurface
                );
            }

            if (!swapped) {
                swapped = swapCatalogWrap(
                    currentSurface,
                    incomingSurface,
                    mode === "category"
                );
            }

            if (!swapped) {
                throw new Error(
                    "Catalog fragments are incompatible"
                );
            }

            document.title = parsedDocument.title
                || document.title;

            if (settings.pushHistory !== false) {
                window.history.pushState(
                    {
                        fpCatalog: true,
                        mode: mode
                    },
                    "",
                    requestedUrl.toString()
                );
            }

            finishLoading(currentSurface);
        } catch (error) {
            if (
                error
                && error.name === "AbortError"
            ) {
                return;
            }

            finishLoading(currentSurface);

            if (settings.fallback !== false) {
                window.location.assign(
                    requestedUrl.toString()
                );
                return;
            }

            window.location.reload();
        }
    }

    document.addEventListener(
        "click",
        function (event) {
            if (!isPlainLeftClick(event)) {
                return;
            }

            var surface = event.target.closest(
                SURFACE_SELECTOR
            );

            if (!surface) {
                return;
            }

            var clearButton = event.target.closest(
                "[data-fp-catalog-clear-url]"
            );

            if (clearButton) {
                event.preventDefault();
                navigateCatalog(
                    new URL(
                        clearButton.getAttribute(
                            "data-fp-catalog-clear-url"
                        ),
                        window.location.href
                    ),
                    "filters"
                );
                return;
            }

            var link = event.target.closest("a[href]");

            if (!link || link.target === "_blank") {
                return;
            }

            var mode = getNavigationMode(link);

            if (!mode) {
                return;
            }

            var url = new URL(
                link.href,
                window.location.href
            );

            if (url.origin !== window.location.origin) {
                return;
            }

            event.preventDefault();
            navigateCatalog(url, mode);
        }
    );

    document.addEventListener(
        "submit",
        function (event) {
            var form = event.target.closest(
                "[data-fp-catalog-filter]"
            );

            if (!form) {
                return;
            }

            event.preventDefault();
            navigateCatalog(
                buildFormUrl(form),
                "filters"
            );
        }
    );

    window.addEventListener(
        "popstate",
        function () {
            if (!getSurface()) {
                return;
            }

            navigateCatalog(
                new URL(
                    window.location.href
                ),
                "history",
                {
                    pushHistory: false,
                    fallback: false
                }
            );
        }
    );

    var initialSurface = getSurface();

    if (initialSurface) {
        initialiseSurface(initialSurface);
    }
}());
