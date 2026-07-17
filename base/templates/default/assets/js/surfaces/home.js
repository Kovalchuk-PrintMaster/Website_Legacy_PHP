(function () {
    'use strict';

    var homeRoot = document.querySelector(
        '[data-fp-surface="home"]'
    );

    if (!homeRoot) {
        return;
    }

    /*
     * Structural readiness marker only.
     *
     * Home behavior will move here block by block. This marker has no
     * visual effect and does not replace any legacy interaction yet.
     */
    homeRoot.setAttribute(
        'data-fp-home-script',
        'ready'
    );
}());
