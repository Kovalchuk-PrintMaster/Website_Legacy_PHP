/* FP_MOBILE_RUNTIME_START */
(() => {
    "use strict";

    const mobileQuery = window.matchMedia(
        "(max-width: 48em), "
        + "(orientation: landscape) and "
        + "(max-width: 80em) and (max-height: 36rem)"
    );

    let searchPlaceholder = null;
    const qs = (selector, root = document) => root.querySelector(selector);

    const moveHomeSearchToHeader = () => {
        const search = qs(
            '[data-fp-surface="home"] .fp-search-strip--home'
        );
        const container = qs(
            "header.fp-site-header > .fp-site-header__container"
        );

        if (!search || !container) {
            return;
        }

        if (!searchPlaceholder) {
            searchPlaceholder = document.createComment(
                "fp-home-search-origin"
            );
            search.parentNode.insertBefore(searchPlaceholder, search);
        }

        container.insertAdjacentElement("afterend", search);
    };

    const restoreHomeSearch = () => {
        const search = qs(
            "header.fp-site-header > .fp-search-strip--home"
        );

        if (!search || !searchPlaceholder || !searchPlaceholder.parentNode) {
            return;
        }

        searchPlaceholder.parentNode.insertBefore(search, searchPlaceholder);
        searchPlaceholder.remove();
        searchPlaceholder = null;
    };

    const applyCompactState = () => {
        if (mobileQuery.matches) {
            moveHomeSearchToHeader();
            return;
        }

        restoreHomeSearch();
    };

    if (document.readyState === "loading") {
        document.addEventListener(
            "DOMContentLoaded",
            applyCompactState,
            { once: true }
        );
    } else {
        applyCompactState();
    }

    if (typeof mobileQuery.addEventListener === "function") {
        mobileQuery.addEventListener("change", applyCompactState);
    } else if (typeof mobileQuery.addListener === "function") {
        mobileQuery.addListener(applyCompactState);
    }
})();
/* FP_MOBILE_RUNTIME_END */

/* FP_COMPACT_BREADCRUMB_SCROLL_RUNTIME_V2_START */
(function () {
    'use strict';

    var compactQuery = window.matchMedia(
        '(max-width: 48em), '
        + '(orientation: landscape) and (max-width: 64em) and (max-height: 36rem)'
    );

    function moveToCurrent() {
        if (!compactQuery.matches) {
            return;
        }

        document.querySelectorAll(
            '.breadcrumbs.fp-breadcrumbs'
        ).forEach(function (breadcrumbs) {
            window.requestAnimationFrame(function () {
                window.requestAnimationFrame(function () {
                    breadcrumbs.scrollLeft = Math.max(
                        0,
                        breadcrumbs.scrollWidth - breadcrumbs.clientWidth
                    );
                });
            });
        });
    }

    function schedule() {
        window.setTimeout(moveToCurrent, 60);
    }

    document.addEventListener('DOMContentLoaded', schedule);
    window.addEventListener('load', schedule);
    window.addEventListener('pageshow', schedule);
    window.addEventListener('orientationchange', function () {
        window.setTimeout(moveToCurrent, 140);
    });

    if (typeof compactQuery.addEventListener === 'function') {
        compactQuery.addEventListener('change', schedule);
    }
})();
/* FP_COMPACT_BREADCRUMB_SCROLL_RUNTIME_V2_END */
