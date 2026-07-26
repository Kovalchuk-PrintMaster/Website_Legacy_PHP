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
