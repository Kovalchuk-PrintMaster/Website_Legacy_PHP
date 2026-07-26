/*
 * ForPrint catalog popover alignment v0.6.49.
 *
 * The header menu owns opening/closing. This module publishes geometry
 * variables so the catalog surface aligns with the homepage hero or, on
 * internal pages, with the internal search strip.
 */
(function () {
    'use strict';

    var DESKTOP_QUERY = '(min-width: 64.0625em)';

    function px(value) {
        return Math.max(0, Math.round(value * 100) / 100) + 'px';
    }

    function clearGeometry(popover) {
        popover.style.removeProperty('--fp-catalog-popover-top');
        popover.style.removeProperty('--fp-catalog-popover-height');
        popover.style.removeProperty('--fp-catalog-popover-bridge');
    }

    function alignCatalogPopover() {
        var popover = document.querySelector('.fp-catalog-popover');
        var trigger = popover ? popover.closest('.header__nav-parent') : null;
        var hero = document.querySelector('.fp-home-hero');
        var internalSearch = document.querySelector('.search.search-internal');
        var alignmentSurface = hero || internalSearch;

        if (!popover || !trigger) {
            return;
        }

        if (
            !alignmentSurface
            || !window.matchMedia(DESKTOP_QUERY).matches
        ) {
            clearGeometry(popover);
            return;
        }

        var triggerRect = trigger.getBoundingClientRect();
        var surfaceRect = alignmentSurface.getBoundingClientRect();
        var top = surfaceRect.top - triggerRect.top;
        var bridge = Math.max(0, top - triggerRect.height);

        popover.style.setProperty('--fp-catalog-popover-top', px(top));
        popover.style.setProperty('--fp-catalog-popover-bridge', px(bridge));

        if (hero) {
            popover.style.setProperty(
                '--fp-catalog-popover-height',
                px(surfaceRect.height)
            );
        } else {
            popover.style.removeProperty('--fp-catalog-popover-height');
        }
    }

    var queued = false;

    function queueAlignment() {
        if (queued) {
            return;
        }

        queued = true;
        window.requestAnimationFrame(function () {
            queued = false;
            alignCatalogPopover();
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        alignCatalogPopover();

        var alignmentSurface = document.querySelector(
            '.fp-home-hero, .search.search-internal'
        );

        if (
            alignmentSurface
            && typeof window.ResizeObserver === 'function'
        ) {
            new window.ResizeObserver(queueAlignment).observe(
                alignmentSurface
            );
        }
    });

    window.addEventListener('load', queueAlignment);
    window.addEventListener('resize', queueAlignment, {passive: true});
    window.addEventListener('scroll', queueAlignment, {passive: true});
}());
